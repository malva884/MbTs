<?php

namespace App\Jobs;

use App\Models\WfOrder;
use App\Services\GoogleDrive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;

class ProcessQualityPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Il numero di volte che il job può essere tentato in caso di errore (es. timeout API).
     */
    public $tries = 3;

    /**
     * Il numero di secondi per cui il job può girare prima di andare in timeout.
     */
    public $timeout = 120;

    protected $percorsoTransito;
    protected $generaPdfScartiSeparato;

    /**
     * Crea una nuova istanza del Job passando il percorso del file da elaborare.
     */
    public function __construct($percorsoTransito, $generaPdfScartiSeparato = false)
    {
        $this->percorsoTransito = $percorsoTransito;
        $this->generaPdfScartiSeparato = $generaPdfScartiSeparato;
    }

    /**
     * Esecuzione del Job.
     */
    public function handle()
    {
        $nomeFileOriginale = basename($this->percorsoTransito);
        $percorsoAssolutoFile = storage_path('app/' . $this->percorsoTransito);

        // Se per qualche motivo il file non esiste più, interrompi
        if (!Storage::exists($this->percorsoTransito)) {
            Log::error("Job fallito: Il file {$this->percorsoTransito} non esiste nello storage.");
            return;
        }

        $cartellaOutput = 'quality_pdf/temp/';
        $cartellaArchivio = 'quality_pdf/archivio/';

        try {
            // --- PASSO 1: CHIAMATA ALL'API DI GEMINI ---
            $risultatoGemini = $this->chiediAGemini($percorsoAssolutoFile);

            if (!$risultatoGemini || $risultatoGemini === 'NON TROVATO') {
                Log::warning("Gemini non ha trovato corrispondenze per il file: {$nomeFileOriginale}");
                Storage::move($this->percorsoTransito, $cartellaArchivio . 'non_riconosciuti_' . $nomeFileOriginale);
                return;
            }

            //Log::info("[ProcessQualityPdf] Risposta Gemini: " . json_encode($risultatoGemini));

            // --- PASSO 2: DIVISIONE DEL PDF ---

            // Sicurezza: rimuovi dalle pagine_scartate qualsiasi pagina già presente in documenti_validi
            if (!empty($risultatoGemini['pagine_scartate']) && !empty($risultatoGemini['documenti_validi'])) {
                $pagineValide = array_column($risultatoGemini['documenti_validi'], 'pagina');
                $risultatoGemini['pagine_scartate'] = array_values(
                    array_filter($risultatoGemini['pagine_scartate'], fn($p) => !in_array($p, $pagineValide))
                );
            }

            // PARTE A: RAGGRUPPAMENTO DELLE PAGINE PER DDT E GENERAZIONE PDF
            if (!empty($risultatoGemini['documenti_validi'])) {

                // Raggruppa le pagine per chiave DDT: pagine con stesso ddt+commessa formano un unico documento
                $gruppiDdt = [];
                foreach ($risultatoGemini['documenti_validi'] as $paginaDoc) {
                    $chiave = $paginaDoc['commessa'] . '_' . $paginaDoc['ddt'];
                    if (!isset($gruppiDdt[$chiave])) {
                        $gruppiDdt[$chiave] = [
                            'commessa' => $paginaDoc['commessa'],
                            'ddt'      => $paginaDoc['ddt'],
                            'pagine'   => [],
                        ];
                    }
                    $gruppiDdt[$chiave]['pagine'][] = $paginaDoc['pagina'];
                }

                foreach ($gruppiDdt as $gruppo) {
                    // Ordina le pagine per numero crescente
                    sort($gruppo['pagine']);

                    $pdfSingolo = new Fpdi();
                    $pdfSingolo->setSourceFile($percorsoAssolutoFile);

                    // Aggiunge tutte le pagine del documento (es. pag 1 e pag 2 se era "1/2")
                    foreach ($gruppo['pagine'] as $numPagina) {
                        $templateId = $pdfSingolo->importPage($numPagina);
                        $dimensioni = $pdfSingolo->getTemplateSize($templateId);
                        $pdfSingolo->AddPage($dimensioni['orientation'], [$dimensioni['width'], $dimensioni['height']]);
                        $pdfSingolo->useTemplate($templateId);
                    }

                    // Accoda le pagine scartate in fondo al file singolo
                    if (!empty($risultatoGemini['pagine_scartate'])) {
                        foreach ($risultatoGemini['pagine_scartate'] as $numPaginaScartata) {
                            $templateIdScartata = $pdfSingolo->importPage($numPaginaScartata);
                            $dimScartata = $pdfSingolo->getTemplateSize($templateIdScartata);
                            $pdfSingolo->AddPage($dimScartata['orientation'], [$dimScartata['width'], $dimScartata['height']]);
                            $pdfSingolo->useTemplate($templateIdScartata);
                        }
                    }

                    // Nome file: ddt.pdf (es: 5161234567.pdf)
                    $nomeFileValido = $gruppo['ddt'] . " -Test- " . ".pdf";
                    $percorsoSalvataggioValido = storage_path('app/' . $cartellaOutput . $nomeFileValido);

                    $pdfSingolo->Output('F', $percorsoSalvataggioValido);

                    $filePerDrive = new \Illuminate\Http\File($percorsoSalvataggioValido);

                    $workflow = WfOrder::where('commessa', $gruppo['commessa'])->where('tipologia', 1)->first();

                    $documentId = GoogleDrive::add_file($workflow->folder_drive, $nomeFileValido, $filePerDrive, true);
                    //Log::info("[ProcessQualityPdf] File caricato su Drive - Cartella: {$workflow->folder_drive} | File: {$nomeFileValido} | DocumentId: {$documentId}");

                    $typologieDocuments = $workflow::$typologieDocuments;
                    \App\Models\WfDocument::addDocument(
                        $workflow::$modelName,
                        $workflow->id,
                        $gruppo['commessa'],
                        $nomeFileValido,
                        $typologieDocuments['DDT'],
                        $documentId,
                        $workflow->id
                    );

                    Storage::delete($cartellaOutput . $nomeFileValido);
                }
            }

            // PARTE B: CREAZIONE DI UN UNICO PDF PER LE PAGINE SCARTATE (disattivabile via booleano)
            if ($this->generaPdfScartiSeparato && !empty($risultatoGemini['pagine_scartate'])) {
                $pdfScartatiUnito = new Fpdi();
                $pdfScartatiUnito->setSourceFile($percorsoAssolutoFile);

                foreach ($risultatoGemini['pagine_scartate'] as $numPagina) {
                    $templateId = $pdfScartatiUnito->importPage($numPagina);
                    $dimensioni = $pdfScartatiUnito->getTemplateSize($templateId);

                    $pdfScartatiUnito->AddPage($dimensioni['orientation'], [$dimensioni['width'], $dimensioni['height']]);
                    $pdfScartatiUnito->useTemplate($templateId);
                }

                $nomeFileScarti = "scarti_" . pathinfo($nomeFileOriginale, PATHINFO_FILENAME) . "_unito.pdf";
                $percorsoSalvataggioScarti = storage_path('app/' . $cartellaOutput . $nomeFileScarti);

                $pdfScartatiUnito->Output('F', $percorsoSalvataggioScarti);

                $commessaScarti = $risultatoGemini['documenti_validi'][0]['commessa'] ?? null;
                $workflow = $commessaScarti ? WfOrder::where('commessa', $commessaScarti)->where('tipologia',1)->first() : null;
                // 2. CARICAMENTO DEL FILE SCARTI SU GOOGLE DRIVE
                if ($workflow) {
                    $fileScartiPerDrive = new \Illuminate\Http\File($percorsoSalvataggioScarti);

                    $documentId = GoogleDrive::add_file($workflow->folder_drive, $nomeFileScarti, $fileScartiPerDrive, true);

                    $typologieDocuments = $workflow::$typologieDocuments;
                    \App\Models\WfDocument::addDocument(
                        $workflow::$modelName,
                        $workflow->id,
                        $commessaScarti,
                        $nomeFileScarti,
                        $typologieDocuments['Distinte'],
                        $documentId,
                        $workflow->id
                    );
                } else {
                    Log::warning("Nessun workflow trovato per le pagine scartate, file: {$nomeFileScarti}");
                }
            }

            // --- PASSO 3: ELIMINAZIONE DEL FILE ORIGINALE (solo se tutto ok) ---
            Storage::delete($this->percorsoTransito);
            //Log::info("Job completato con successo per il file: {$nomeFileOriginale}");

        } catch (\Exception $e) {
            Log::error("Errore nel Job sul file {$nomeFileOriginale}: " . $e->getMessage());
            // Rilancia l'eccezione per far capire a Laravel che il job è fallito e va riprovato (fino a 3 volte)
            throw $e;
        }
    }

    /**
     * Logica di chiamata API effettiva a Gemini.
     */
    private function chiediAGemini($percorsoFile)
    {
        // Il tuo prompt strutturato
        $promptCommessa = 'Sei un assistente di estrazione dati strutturati. Analizza i documenti forniti seguendo queste istruzioni tassative:

1. **Filtro Pagine e Riconoscimento**:
   * Una pagina e considerata la PRIMA pagina di un documento valido se contiene la dicitura esatta "DOCUMENTO DI TRASPORTO" insieme al Numero di Commessa (10 cifre, inizia con 46) e al Numero del DDT (10 cifre, inizia con 516).
   * Cerca sempre l\'indicatore di paginazione del documento (es. "1/2", "1/3", "2/3"). Il campo "pagina_corrente" e il numeratore, "pagine_totali" e il denominatore. Se non e presente alcuna indicazione di paginazione, usa 1 per entrambi.
   * Se una pagina riporta "1/3", le due pagine fisiche immediatamente successive nel file fanno parte dello STESSO documento DDT e devono essere incluse in "documenti_validi" con gli stessi ddt e commessa, anche se non ripetono la dicitura "DOCUMENTO DI TRASPORTO". Inseriscile con "pagina_corrente" 2 e 3.
   * Una pagina e da scartare SOLO se non appartiene ad alcun documento DDT valido (ne come prima pagina, ne come pagina successiva di un documento multi-pagina).

2. **Formato della Risposta**:
   * Restituisci esclusivamente un oggetto JSON con due liste distinte strutturato esattamente cosi:
     {
       "documenti_validi": [
         {"pagina": 1, "commessa": "4612345678", "ddt": "5161234567", "pagina_corrente": 1, "pagine_totali": 3},
         {"pagina": 2, "commessa": "4612345678", "ddt": "5161234567", "pagina_corrente": 2, "pagine_totali": 3},
         {"pagina": 3, "commessa": "4612345678", "ddt": "5161234567", "pagina_corrente": 3, "pagine_totali": 3},
         {"pagina": 4, "commessa": "4699999999", "ddt": "5169999999", "pagina_corrente": 1, "pagine_totali": 1}
       ],
       "pagine_scartate": [5]
     }
   * IMPORTANTE: una pagina non puo apparire sia in "documenti_validi" che in "pagine_scartate".
   * Se l\'intero file non contiene assolutamente nulla (nessun documento valido e nessuna pagina diversa), rispondi unicamente con la stringa: NON TROVATO.
   * Non includere i markdown del codice (no ```json o ```text), non aggiungere introduzioni, spiegazioni o testo di contorno. Sii totalmente sintetico.';

        // Inizializza il tuo servizio personalizzato
        $documentReaderService = new \App\Services\GeminiAiService(); // Assicurati che il namespace sia corretto

        // Esegui l'analisi del file passandogli il percorso assoluto sul server
        $rispostaRaw = $documentReaderService->analizzaFile(
            filePath: $percorsoFile,
            prompt: $promptCommessa,
            mimeType: 'application/pdf'
        );

        // Pulisci la risposta da eventuali spazi bianchi o ritorni a capo indesiderati
        $rispostaPulita = trim($rispostaRaw);

        // Se Gemini ha risposto esplicitamente "NON TROVATO", restituisci la stringa
        if ($rispostaPulita === 'NON TROVATO') {
            return 'NON TROVATO';
        }

        // Altrimenti proviamo a decodificare il JSON ricevuto
        $datiDecodificati = json_decode($rispostaPulita, true);

        // Se il JSON è valido, lo restituiamo al Job, altrimenti gestiamo l'errore di formattazione
        if (json_last_error() === JSON_ERROR_NONE) {
            return $datiDecodificati;
        }

        // Logga l'errore se Gemini risponde con un testo non JSON o formattato male
        \Illuminate\Support\Facades\Log::error("Gemini ha risposto con un formato invalido: " . $rispostaRaw);
        return 'NON TROVATO';
    }
}
