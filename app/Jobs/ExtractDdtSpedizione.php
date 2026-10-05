<?php

namespace App\Jobs;

use App\Models\DdtSpedizione;
use App\Models\JobLog;
use App\Models\WfOrder;
use App\Services\CalcoloCostoSpedizioneService;
use App\Services\GeminiAiService;
use App\Services\GoogleDrive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class ExtractDdtSpedizione implements ShouldQueue, ShouldBeUnique
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

    /**
     * Il numero di secondi per cui mantenere il lock di unicità del Job.
     */
    public $uniqueFor = 600;

    protected $percorsoTransito;

    /**
     * ID univoco del Job per prevenire duplicati in coda.
     */
    public function uniqueId(): string
    {
        return md5($this->percorsoTransito);
    }

    /**
     * Crea una nuova istanza del Job passando il percorso del file da elaborare.
     */
    public function __construct($percorsoTransito)
    {
        $this->percorsoTransito = $percorsoTransito;
    }

    /**
     * Esecuzione del Job.
     */
    public function handle()
    {
        $nomeFileOriginale = basename($this->percorsoTransito);
        $disk = Storage::disk('ddt_spedizioni_drive');

        // Crea log entry per questo job
        $jobLog = JobLog::create([
            'job_name' => 'ExtractDdtSpedizione',
            'job_type' => 'queue',
            'status' => 'running',
            'started_at' => now(),
            'payload' => [
                'file' => $nomeFileOriginale,
                'path' => $this->percorsoTransito,
            ],
        ]);

        // Se per qualche motivo il file non esiste più su Drive, cancella il job senza errore
        if (!$disk->exists($this->percorsoTransito)) {
            $jobLog->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => "Il file {$this->percorsoTransito} non esiste più su Drive (probabilmente già processato).",
            ]);
            Log::warning("[ExtractDdtSpedizione] Job cancellato: Il file {$this->percorsoTransito} non esiste più su Drive.");
            $this->delete();
            return;
        }

        // Scarica il file temporaneamente per inviarlo a Gemini
        $contenuto = $disk->get($this->percorsoTransito);
        $nomeFileTempLocale = 'temp_ddt_' . uniqid() . '_' . $nomeFileOriginale;
        Storage::disk('local')->put($nomeFileTempLocale, $contenuto);
        $percorsoAssolutoFile = storage_path('app/' . $nomeFileTempLocale);

        try {
            // --- PASSO 1: CHIAMATA ALL'API DI GEMINI ---
            $jobLog->update(['output' => "Chiamata a Gemini in corso..."]);
            $risultatoGemini = $this->chiediAGemini($percorsoAssolutoFile);

            if (!$risultatoGemini || $risultatoGemini === 'NON TROVATO') {
                $jobLog->update([
                    'status' => 'success',
                    'finished_at' => now(),
                    'output' => "Gemini non ha trovato un DDT valido, file spostato in errori",
                ]);
                Log::warning("[ExtractDdtSpedizione] Gemini non ha trovato un DDT valido per il file: {$nomeFileOriginale}");

                DdtSpedizione::create([
                    'file_name' => $nomeFileOriginale,
                    'drive_path' => $this->percorsoTransito,
                    'status' => 'non_riconosciuto',
                ]);

                $this->spostaSuDrive($disk, 'Bolle/errori/');
                Storage::disk('local')->delete($nomeFileTempLocale);
                return;
            }

            // --- PASSO 2: SALVATAGGIO DATI ESTRATTI ---
            $documenti = $risultatoGemini['documenti'] ?? [];

            // Deduplica: un DDT multi-pagina puo essere restituito piu volte da Gemini
            $visti = [];
            $documenti = array_values(array_filter($documenti, function ($doc) use (&$visti) {
                $chiave = implode('|', [
                    $doc['numero_ddt'] ?? '',
                    $doc['ns_ovd'] ?? '',
                ]);
                if ($chiave === '|') {
                    return true; // senza dati identificativi non possiamo deduplicare
                }
                if (isset($visti[$chiave])) {
                    return false;
                }
                $visti[$chiave] = true;
                return true;
            }));

            if (empty($documenti)) {
                $jobLog->update([
                    'status' => 'success',
                    'finished_at' => now(),
                    'output' => "Nessun documento DDT estratto, file spostato in errori",
                ]);

                DdtSpedizione::create([
                    'file_name' => $nomeFileOriginale,
                    'drive_path' => $this->percorsoTransito,
                    'status' => 'non_riconosciuto',
                    'raw_response' => $risultatoGemini,
                ]);

                $this->spostaSuDrive($disk, 'Bolle/errori/');
                Storage::disk('local')->delete($nomeFileTempLocale);
                return;
            }

            // --- PASSO 3: DIVISIONE PDF E UPLOAD SINGOLI DDT ---
            $pdfDrivePaths = $this->dividiECaricaPdf($disk, $percorsoAssolutoFile, $documenti);

            foreach ($documenti as $i => $doc) {
                $vettore = self::normalizzaVettore($doc['vettore'] ?? null);
                $testoVettore = mb_strtoupper((string) $vettore . ' ' . json_encode($doc));
                $infoPdf = $pdfDrivePaths[$i] ?? [];
                $statusRecord = $infoPdf['status'] ?? 'processed';

                $dataDdt = $this->parseData($doc['data_ddt'] ?? null);
                $annoDdt = $dataDdt ? (int) substr($dataDdt, 0, 4) : null;
                $nColli = $this->parseIntero($doc['n_colli'] ?? null);
                $pesoLordoKg = $this->parseDecimale($doc['peso_lordo_kg'] ?? null);
                $pesoNettoKg = $this->parseDecimale($doc['peso_netto_kg'] ?? null);
                $indirizzo = $doc['destinazione_indirizzo'] ?? null;

                // Calcolo costo spedizione da listino
                $calcoloCosto = CalcoloCostoSpedizioneService::calcola(
                    $vettore,
                    $indirizzo,
                    $pesoLordoKg,
                    $annoDdt,
                    $nColli
                );

                $isSusa = $vettore === 'SUSA' || str_contains($testoVettore, 'SUSA');
                $isPalletways = $vettore === 'PALLETWAYS' || str_contains($testoVettore, 'PALLETWAYS');

                DdtSpedizione::create([
                    'file_name' => $nomeFileOriginale,
                    'drive_path' => $this->percorsoTransito,
                    'pdf_path' => $infoPdf['pdf_path'] ?? null,
                    'wf_order_id' => $infoPdf['wf_order_id'] ?? null,
                    'numero_ddt' => $doc['numero_ddt'] ?? null,
                    'data_ddt' => $dataDdt,
                    'riferimento_interno' => $doc['riferimento_interno'] ?? null,
                    'ns_ovd' => $doc['ns_ovd'] ?? null,
                    'n_colli' => $nColli,
                    'peso_lordo_kg' => $pesoLordoKg,
                    'peso_netto_kg' => $pesoNettoKg,
                    'vettore' => $vettore,
                    'vettore_palletways' => $isPalletways,
                    'vettore_susa' => $isSusa,
                    'destinazione_nome' => $doc['destinazione_nome'] ?? null,
                    'destinazione_indirizzo' => $indirizzo,
                    'destinazione_provincia' => $calcoloCosto['provincia'] ?? null,
                    'destinazione_regione' => $calcoloCosto['regione'] ?? null,
                    'listino_id' => $calcoloCosto['listino_id'] ?? null,
                    'costo_spedizione' => $calcoloCosto['costo'] ?? null,
                    'costo_tipo_calcolo' => $calcoloCosto['tipo_calcolo'] ?? null,
                    'costo_note' => $calcoloCosto['note'] ?? null,
                    'costo_dettaglio' => $calcoloCosto['dettaglio'] ?? null,
                    'status' => $statusRecord,
                    'raw_response' => $doc,
                ]);
            }

            $jobLog->update(['output' => "Salvati " . count($documenti) . " record DDT"]);

            // --- PASSO 3: SPOSTA IL FILE IN processed ---
            $this->spostaSuDrive($disk, 'Bolle/processed/');

            // Pulisci file temporaneo locale
            Storage::disk('local')->delete($nomeFileTempLocale);

            $jobLog->update([
                'status' => 'success',
                'finished_at' => now(),
                'output' => "Job completato per il file: {$nomeFileOriginale} (DDT estratti: " . count($documenti) . ")",
            ]);

        } catch (\Exception $e) {
            // Pulisci file temporaneo locale in caso di errore
            if (isset($nomeFileTempLocale)) {
                Storage::disk('local')->delete($nomeFileTempLocale);
            }

            $jobLog->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
            Log::error("[ExtractDdtSpedizione] Errore nel Job sul file {$nomeFileOriginale}: " . $e->getMessage());
            // Rilancia l'eccezione per far capire a Laravel che il job è fallito e va riprovato (fino a 3 volte)
            throw $e;
        }
    }

    /**
     * Sposta il file di transito nella sottocartella di destinazione su Drive.
     */
    private function spostaSuDrive($disk, string $cartellaDestinazione): void
    {
        $nomeFile = basename($this->percorsoTransito);
        $destinazione = $cartellaDestinazione . $nomeFile;

        try {
            if (!$disk->exists($cartellaDestinazione)) {
                $disk->makeDirectory($cartellaDestinazione);
            }

            // Evita sovrascritture: aggiungi timestamp se il file esiste già
            if ($disk->exists($destinazione)) {
                $pathInfo = pathinfo($nomeFile);
                $destinazione = $cartellaDestinazione . $pathInfo['filename'] . '_' . time() . '.' . ($pathInfo['extension'] ?? 'pdf');
            }

            $disk->move($this->percorsoTransito, $destinazione);
            Log::info("[ExtractDdtSpedizione] File spostato in: {$destinazione}");
        } catch (\Exception $e) {
            Log::error("[ExtractDdtSpedizione] Errore spostamento file {$this->percorsoTransito} in {$cartellaDestinazione}: " . $e->getMessage());
        }
    }

    /**
     * Divide il PDF originale in un PDF per ogni DDT e li carica nella cartella Drive
     * della commessa (WfOrder.folder_drive), registrando un WfDocument.
     * Se il workflow non esiste, il file va in DDT/pending_workflow/ e viene inviata una notifica.
     * Restituisce un array [indice_documento => ['pdf_path' => ..., 'wf_order_id' => ...]].
     */
    private function dividiECaricaPdf($disk, string $percorsoFile, array $documenti): array
    {
        $risultati = [];
        $cartellaTemp = 'ddt_spedizioni_temp/';
        $cartellaPending = 'Bolle/pending_workflow/';

        try {
            if (!$disk->exists($cartellaPending)) {
                $disk->makeDirectory($cartellaPending);
            }
        } catch (\Exception $e) {
            Log::warning("[ExtractDdtSpedizione] Impossibile creare cartella {$cartellaPending}: " . $e->getMessage());
        }

        foreach ($documenti as $i => $doc) {
            $risultati[$i] = ['pdf_path' => null, 'wf_order_id' => null];
            $paginaInizio = max(1, (int) ($doc['pagina_inizio'] ?? 1));
            $paginaFine = max($paginaInizio, (int) ($doc['pagina_fine'] ?? $paginaInizio));

            try {
                $pdf = new Fpdi();
                $totalePagine = $pdf->setSourceFile($percorsoFile);
                $paginaFine = min($paginaFine, $totalePagine);

                for ($p = $paginaInizio; $p <= $paginaFine; $p++) {
                    $templateId = $pdf->importPage($p);
                    $dimensioni = $pdf->getTemplateSize($templateId);
                    $pdf->AddPage($dimensioni['orientation'], [$dimensioni['width'], $dimensioni['height']]);
                    $pdf->useTemplate($templateId);
                }

                $nomeBase = $doc['numero_ddt'] ?? ('ddt_' . ($i + 1));
                $nomeFile = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $nomeBase) . '.pdf';
                $nomeTemp = uniqid() . '_' . $nomeFile;
                $percorsoLocale = storage_path('app/' . $cartellaTemp . $nomeTemp);

                Storage::disk('local')->makeDirectory($cartellaTemp);
                $pdf->Output('F', $percorsoLocale);

                // Cerca la commessa (ns_ovd) nel workflow
                $commessa = $doc['ns_ovd'] ?? null;
                $workflow = $commessa ? WfOrder::where('commessa', $commessa)->where('tipologia', 1)->first() : null;

                if ($workflow && !empty($workflow->folder_drive)) {
                    // Upload nella cartella Drive della commessa + registrazione WfDocument
                    $filePerDrive = new \Illuminate\Http\File($percorsoLocale);
                    $documentId = GoogleDrive::add_file($workflow->folder_drive, $nomeFile, $filePerDrive, true);

                    $typologieDocuments = $workflow::$typologieDocuments;
                    \App\Models\WfDocument::addDocument(
                        $workflow::$modelName,
                        $workflow->id,
                        $commessa,
                        $nomeFile,
                        $typologieDocuments['DDT'],
                        $documentId,
                        $workflow->id
                    );

                    $risultati[$i]['pdf_path'] = $documentId;
                    $risultati[$i]['wf_order_id'] = $workflow->id;
                    $risultati[$i]['status'] = 'processed';
                    Log::info("[ExtractDdtSpedizione] DDT {$nomeBase} caricato nella commessa {$commessa} (file id: {$documentId})");
                } else {
                    // Workflow non trovato: salva in pending_workflow e notifica
                    $percorsoDrive = $cartellaPending . $nomeFile;
                    if ($disk->exists($percorsoDrive)) {
                        $pathInfo = pathinfo($nomeFile);
                        $percorsoDrive = $cartellaPending . $pathInfo['filename'] . '_' . time() . '.' . ($pathInfo['extension'] ?? 'pdf');
                    }

                    $disk->put($percorsoDrive, file_get_contents($percorsoLocale));
                    $risultati[$i]['pdf_path'] = $percorsoDrive;
                    $risultati[$i]['wf_order_id'] = null;
                    $risultati[$i]['status'] = 'pending_workflow';

                    Log::warning("[ExtractDdtSpedizione] Workflow non trovato per commessa {$commessa}, file in {$percorsoDrive}");
                    $this->sendMissingWorkflowNotification($commessa, $nomeBase, basename($percorsoFile));
                }

                Storage::disk('local')->delete($cartellaTemp . $nomeTemp);
            } catch (\Exception $e) {
                Log::warning("[ExtractDdtSpedizione] Errore divisione PDF per documento indice {$i}: " . $e->getMessage());
            }
        }

        return $risultati;
    }

    /**
     * Invia notifica email quando il workflow della commessa non e stato trovato.
     */
    private function sendMissingWorkflowNotification($commessa, $ddt, $nomeFileOriginale)
    {
        try {
            $users = \App\Models\Utility::users_notify(['qt_workflow_non_trovato']);

            if (empty($users)) {
                Log::warning("Nessun utente configurato per notifica qt_workflow_non_trovato");
                return;
            }

            $oggetto = "Workflow non trovato - Commessa: {$commessa}";
            $content = "Il sistema ha rilevato un documento DDT con workflow non trovato:<br><br>";
            $content .= "<strong>Commessa:</strong> {$commessa}<br>";
            $content .= "<strong>DDT:</strong> {$ddt}<br>";
            $content .= "<strong>File originale:</strong> {$nomeFileOriginale}<br>";
            $content .= "<strong>Data:</strong> " . now()->format('d/m/Y H:i:s') . "<br><br>";
            $content .= "Il file e stato spostato nella cartella pending per un successivo retry.";

            \Illuminate\Support\Facades\Mail::send('emails/email_white', compact('content'), function ($message) use ($users, $oggetto) {
                $message->to($users)->subject($oggetto);
            });

            Log::info("Notifica email inviata per workflow non trovato - Commessa: {$commessa}");
        } catch (\Exception $e) {
            Log::error("Errore nell'invio notifica email per workflow non trovato: " . $e->getMessage());
        }
    }

    /**
     * Converte una data dal formato del DDT (es. 15.09.2026 o 15/09/2026) in Y-m-d.
     */
    private function parseData(?string $data): ?string
    {
        if (empty($data)) {
            return null;
        }

        $data = trim($data);

        // Già in formato ISO
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return $data;
        }

        // Formati italiani: dd.mm.yyyy, dd/mm/yyyy, dd-mm-yyyy
        if (preg_match('/^(\d{1,2})[.\/\-](\d{1,2})[.\/\-](\d{4})$/', $data, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // Formato a 2 cifre per l'anno: dd.mm.yy
        if (preg_match('/^(\d{1,2})[.\/\-](\d{1,2})[.\/\-](\d{2})$/', $data, $m)) {
            return sprintf('20%02d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        return null;
    }

    /**
     * Converte un valore in intero gestendo separatori italiani (es. "1.000" -> 1000).
     */
    private function parseIntero($valore): ?int
    {
        if ($valore === null || $valore === '') {
            return null;
        }

        if (is_numeric($valore)) {
            return (int) $valore;
        }

        $pulito = preg_replace('/[^\d]/', '', (string) $valore);
        return $pulito !== '' ? (int) $pulito : null;
    }

    /**
     * Converte un peso in decimale gestendo il formato italiano (es. "203,000" o "203.000").
     */
    private function parseDecimale($valore): ?float
    {
        if ($valore === null || $valore === '') {
            return null;
        }

        if (is_numeric($valore)) {
            return (float) $valore;
        }

        $pulito = trim((string) $valore);
        // Rimuovi spazi e unità di misura
        $pulito = preg_replace('/[^\d,\.\-]/', '', $pulito);

        // Formato italiano: 1.234,56 -> 1234.56
        if (str_contains($pulito, ',') && str_contains($pulito, '.')) {
            $pulito = str_replace('.', '', $pulito);
            $pulito = str_replace(',', '.', $pulito);
        } elseif (str_contains($pulito, ',')) {
            $pulito = str_replace(',', '.', $pulito);
        }

        return is_numeric($pulito) ? (float) $pulito : null;
    }

    /**
     * Normalizza il nome del vettore estratto, rimuovendo termini generici
     * o parole duplicate (es. "SUSA SUSA SUSA" -> "SUSA").
     */
    public static function normalizzaVettore(?string $vettore): ?string
    {
        if ($vettore === null || trim($vettore) === '') {
            return null;
        }

        $vettoreTrim = trim($vettore);
        $vettoreUpper = mb_strtoupper($vettoreTrim);

        // Riconoscimento immediato dei vettori convenzionati principali
        if (str_contains($vettoreUpper, 'SUSA')) {
            return 'SUSA';
        }
        if (str_contains($vettoreUpper, 'PALLETWAYS')) {
            return 'PALLETWAYS';
        }

        // Parole che non identificano un corriere: clausole, campi ed etichette tipiche del DDT
        static $paroleGeneriche = [
            'VETTORE', 'VETTORI', 'MITTENTE', 'DESTINATARIO', 'DESTINATARIA', 'DESTINATARI',
            'FIRMA', 'CONDUCENTE', 'TRASPORTO', 'TRASPORTA', 'CURA', 'CARICO', 'SCARICO',
            'PORTO', 'FRANCO', 'ASSEGNATO', 'RESO', 'DAP', 'DDP', 'EXW', 'FOB', 'CIF',
            'DESTINAZIONE', 'LUOGO', 'NAZIONE', 'DITTA', 'RESID', 'DOM', 'COMUNE', 'VIA',
            'DATA', 'ORA', 'INIZIO', 'FINE',
        ];

        static $stopwords = [
            'A', 'AL', 'ALLA', 'DA', 'DE', 'DEL', 'DELLA', 'DI', 'E', 'IL', 'LA', 'LO',
            'PER', 'CON', 'IN', 'O', 'N',
        ];

        // Filtra i termini generici parola per parola: se non resta nulla di
        // significativo (es. "Destinataria", "Firma del destinatario") non e' un vettore
        $parole = preg_split('/\s+/', $vettoreTrim) ?: [];
        $significative = [];
        foreach ($parole as $parola) {
            $p = mb_strtoupper(trim($parola, " \t.,;:'\"()[]°"));
            if ($p === '' || in_array($p, $paroleGeneriche, true) || in_array($p, $stopwords, true)) {
                continue;
            }
            $significative[] = $parola;
        }

        if (empty($significative)) {
            return null;
        }

        // Parole ripetute (es. "SUSA SUSA SUSA" o duplicati per errore ERP)
        $uniche = array_values(array_unique($significative));
        if (count($uniche) === 1) {
            return $uniche[0];
        }

        return implode(' ', $significative);
    }

    /**
     * Logica di chiamata API effettiva a Gemini.
     */
    private function chiediAGemini($percorsoFile)
    {
        $prompt = 'Sei un assistente di estrazione dati strutturati. Analizza il PDF fornito: contiene uno o piu DOCUMENTI DI TRASPORTO (DDT) italiani, possibilmente di emittenti diversi.

Per OGNI documento DDT presente nel file estrai i seguenti campi:

1. "numero_ddt": il numero del DDT (campo "Nr DDT", "N. DDT", "Numero DDT" o simile). Riporta il numero ESATTAMENTE come stampato, comprese tutte le cifre e gli zeri iniziali (es. "8000009690", non "800009690"). NON interpretarlo come numero intero.
2. "data_ddt": la data del DDT (campo "Data DDT" o simile). Restituiscila nel formato ISO YYYY-MM-DD.
3. "riferimento_interno": il valore del campo "Riferimento interno" o "Rif. interno" o simile.
4. "ns_ovd": il valore del campo "Ns. odv", "Ns. OVD", "Ns. ordine", "Ns. ordine di vendita" o simile (ordine di vendita dell\'emittente).
5. "n_colli": il valore del campo "N colli", "N. colli", "Numero colli" o simile. Solo il numero intero.
6. "peso_lordo_kg": il valore del campo "Peso lordo" (in KG). Solo il numero, usa il punto come separatore decimale.
7. "peso_netto_kg": il valore del campo "Peso netto" (in KG). Solo il numero, usa il punto come separatore decimale.
8. "vettore": il nome dell\'azienda o corriere che effettua il trasporto (es. "SUSA", "PALLETWAYS", "DHL", "GLS", "FERCAM", ecc.).
   ATTENZIONE:
   - NON estrarre MAI la parola generica "Vettore", "Mittente" o "Destinatario": il campo "TRASPORTO A CURA DI: Vettore" indica solo la clausola di trasporto, NON il nome del vettore!
   - Cerca il nome del vettore nell\'apposita sezione in fondo/calce al documento intitolata "VETTORI: DITTA RESID. O DOM. COMUNE. VIA. N°" (oppure nel campo "Vettore", o nelle annotazioni).
   - Se nel riquadro vettori appare un testo ripetuto come "SUSA SUSA SUSA", estrai unicamente il nome dell\'azienda normalizzato: "SUSA".
   - Se non è presente il nome di un corriere/azienda di trasporto reale, restituisci null.
9. "destinazione_nome": il nome/ragione sociale del "Luogo Destinazione" (NON il "Destinatario": cerca esplicitamente il blocco "Luogo Destinazione" o "Luogo di destinazione").
10. "destinazione_indirizzo": l\'indirizzo completo del "Luogo Destinazione" (via, civico, CAP, citta, provincia).
11. "pagina_inizio": numero della pagina del file PDF in cui inizia questo DDT (la prima pagina del file e la numero 1).
12. "pagina_fine": numero della pagina del file PDF in cui termina questo DDT. Se il DDT occupa una sola pagina, pagina_fine = pagina_inizio.

Regole:
- Se un campo non e presente o non leggibile, usa null.
- Se il file contiene piu DDT (anche di emittenti diversi), restituisci un elemento per ciascuno.
- Se un DDT si estende su piu pagine (es. paginazione "Pag. 1/2", "1/3"), restituisci UN SOLO elemento per quel DDT: estrai i campi dalla pagina che li contiene e ignora le pagine di continuazione.
- Se il file non contiene alcun documento di trasporto, rispondi unicamente con la stringa: NON TROVATO.

Formato della risposta: restituisci ESCLUSIVAMENTE un oggetto JSON strutturato esattamente cosi, senza markdown, senza introduzioni e senza testo di contorno:
{
  "documenti": [
    {
      "numero_ddt": "8000009087",
      "data_ddt": "2026-09-15",
      "riferimento_interno": "5160082980",
      "ns_ovd": "4610044446",
      "n_colli": 1,
      "peso_lordo_kg": 203.000,
      "peso_netto_kg": 133.00,
      "vettore": "PALLETWAYS",
      "destinazione_nome": "SIRTI SPA",
      "destinazione_indirizzo": "VIA STADELLA, 17 - 31010 MARENO DI PIAVE (TV) - IT",
      "pagina_inizio": 1,
      "pagina_fine": 1
    }
  ]
}';

        $documentReaderService = new GeminiAiService();

        // Esegui l'analisi del file passandogli il percorso assoluto sul server
        $rispostaRaw = $documentReaderService->analizzaFile(
            filePath: $percorsoFile,
            prompt: $prompt,
            mimeType: 'application/pdf'
        );

        if ($rispostaRaw === null) {
            return null;
        }

        // Pulisci la risposta da eventuali spazi bianchi o ritorni a capo indesiderati
        $rispostaPulita = trim($rispostaRaw);

        // Se Gemini ha risposto esplicitamente "NON TROVATO", restituisci la stringa
        if ($rispostaPulita === 'NON TROVATO') {
            return 'NON TROVATO';
        }

        // Rimuovi eventuali fence markdown residui
        $rispostaPulita = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $rispostaPulita);

        // Altrimenti proviamo a decodificare il JSON ricevuto
        $datiDecodificati = json_decode($rispostaPulita, true);

        // Se il JSON è valido, lo restituiamo al Job, altrimenti gestiamo l'errore di formattazione
        if (json_last_error() === JSON_ERROR_NONE) {
            return $datiDecodificati;
        }

        // Logga l'errore se Gemini risponde con un testo non JSON o formattato male
        Log::error("[ExtractDdtSpedizione] Gemini ha risposto con un formato invalido: " . $rispostaRaw);
        return 'NON TROVATO';
    }

    /**
     * Recupera il contenuto binario del PDF del DDT.
     */
    public static function recuperaPdfContent(DdtSpedizione $ddt): ?string
    {
        $disk = Storage::disk('ddt_spedizioni_drive');

        if ($ddt->pdf_path) {
            if (str_contains($ddt->pdf_path, '/')) {
                if ($disk->exists($ddt->pdf_path)) {
                    return $disk->get($ddt->pdf_path);
                }
            } else {
                try {
                    return GoogleDrive::download($ddt->pdf_path);
                } catch (\Exception $e) {
                    Log::warning("[recuperaPdfContent] Errore download Google Drive ID {$ddt->pdf_path}: " . $e->getMessage());
                }
            }
        }

        if ($ddt->file_name) {
            foreach (['Bolle/processed', 'Bolle/errori', 'Bolle/processing', 'Bolle'] as $cartella) {
                $percorso = $cartella . '/' . $ddt->file_name;
                if ($disk->exists($percorso)) {
                    return $disk->get($percorso);
                }
            }
        }

        return null;
    }

    /**
     * Rielabora un DDT con vettore non riconosciuto ('Vettore' o null)
     * inviando il PDF a Gemini con prompt mirato sul riquadro vettori.
     */
    public static function riestraiVettoreDdt(DdtSpedizione $ddt): bool
    {
        $pdfContent = self::recuperaPdfContent($ddt);
        if (!$pdfContent) {
            Log::warning("[riestraiVettoreDdt] PDF non trovato per DDT {$ddt->numero_ddt}");
            return false;
        }

        $tempPath = storage_path('app/temp_recalc_' . uniqid() . '.pdf');
        file_put_contents($tempPath, $pdfContent);

        try {
            $prompt = 'Analizza questo Documento di Trasporto (DDT) ed estrai esclusivamente il nome dell\'azienda o corriere che effettua il trasporto (es. "SUSA", "PALLETWAYS", "DHL", "GLS", "FERCAM", ecc.).
ATTENZIONE:
- NON estrarre la parola "Vettore", "Mittente" o "Destinatario": il campo "TRASPORTO A CURA DI: Vettore" NON e il nome del vettore!
- Cerca il nome del vettore nell\'apposita sezione in fondo/calce al documento intitolata "VETTORI: DITTA RESID. O DOM. COMUNE. VIA. N°" (oppure nelle annotazioni).
- Se nel riquadro vettori appare un testo ripetuto come "SUSA SUSA SUSA", estrai unicamente il nome dell\'azienda normalizzato: "SUSA".
- Restituisci un nome SOLO se e letteralmente scritto nel documento. NON dedurre e NON inventare un corriere: se nessun nome di azienda di trasporto e visibile, rispondi {"vettore": null}.
- Rispondi ESCLUSIVAMENTE con un JSON: {"vettore": "NOME_VETTORE"} oppure {"vettore": null} se non individuabile.';

            $gemini = new GeminiAiService();
            $risposta = $gemini->analizzaFile(
                filePath: $tempPath,
                prompt: $prompt,
                mimeType: 'application/pdf'
            );

            if ($risposta) {
                $pulita = trim(preg_replace('/^```(?:json)?\s*|\s*```$/', '', trim($risposta)));
                $dati = json_decode($pulita, true);
                $vettore = self::normalizzaVettore($dati['vettore'] ?? null);

                if ($vettore) {
                    $testoUpper = mb_strtoupper((string) $vettore);
                    $isSusa = $vettore === 'SUSA' || str_contains($testoUpper, 'SUSA');
                    $isPalletways = $vettore === 'PALLETWAYS' || str_contains($testoUpper, 'PALLETWAYS');

                    $annoDdt = $ddt->data_ddt ? (int) $ddt->data_ddt->format('Y') : null;
                    $calcolo = CalcoloCostoSpedizioneService::calcola(
                        $vettore,
                        $ddt->destinazione_indirizzo,
                        $ddt->peso_lordo_kg ? (float) $ddt->peso_lordo_kg : null,
                        $annoDdt,
                        $ddt->n_colli
                    );

                    $ddt->update([
                        'vettore' => $vettore,
                        'vettore_susa' => $isSusa,
                        'vettore_palletways' => $isPalletways,
                        'destinazione_provincia' => $calcolo['provincia'] ?? $ddt->destinazione_provincia,
                        'destinazione_regione' => $calcolo['regione'] ?? $ddt->destinazione_regione,
                        'listino_id' => $calcolo['listino_id'] ?? $ddt->listino_id,
                        'costo_spedizione' => $calcolo['costo'] ?? $ddt->costo_spedizione,
                        'costo_tipo_calcolo' => $calcolo['tipo_calcolo'] ?? $ddt->costo_tipo_calcolo,
                        'costo_note' => $calcolo['note'] ?? $ddt->costo_note,
                        'costo_dettaglio' => $calcolo['dettaglio'] ?? $ddt->costo_dettaglio,
                    ]);

                    return true;
                }
            }
        } catch (\Exception $e) {
            Log::error("[riestraiVettoreDdt] Errore DDT {$ddt->numero_ddt}: " . $e->getMessage());
        } finally {
            @unlink($tempPath);
        }

        return false;
    }
}
