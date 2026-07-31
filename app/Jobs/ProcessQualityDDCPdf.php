<?php

namespace App\Jobs;

use App\Models\WfOrder;
use App\Models\WfDocument;
use App\Models\WfDocumentValidation;
use App\Services\GoogleDrive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessQualityDDCPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected $percorsoTransito;
    protected $nomeFileOriginale;

    public function __construct($percorsoTransito, $nomeFileOriginale)
    {
        $this->percorsoTransito  = $percorsoTransito;
        $this->nomeFileOriginale = $nomeFileOriginale;
    }

    public function handle()
    {
        $disk = Storage::disk('quality_ddc_drive');
        $nomeFileOriginale = basename($this->percorsoTransito);

        // Verifica che il file esista su Drive
        if (!$disk->exists($this->percorsoTransito)) {
            Log::error("[ProcessQualityDDCPdf] File non trovato su Drive: {$this->percorsoTransito}");
            return;
        }

        // Scarica il file temporaneamente per elaborarlo
        $contenuto = $disk->get($this->percorsoTransito);
        $percorsoTempLocale = storage_path('app/temp_ddc_' . time() . '_' . $nomeFileOriginale);
        Storage::disk('local')->put('temp_ddc_' . time() . '_' . $nomeFileOriginale, $contenuto);
        $percorsoAssoluto = storage_path('app/temp_ddc_' . time() . '_' . $nomeFileOriginale);

        try {
            // --- PASSO 1: ANALISI CON GEMINI ---
            $dati = $this->chiediAGemini($percorsoAssoluto);

            if (!$dati || $dati === 'NON TROVATO') {
                Log::warning("[ProcessQualityDDCPdf] Gemini non ha trovato DDC nel file: {$nomeFileOriginale}");
                // Sposta il file su Drive nella cartella archivio
                $disk->move($this->percorsoTransito, 'archivio/ddc_non_riconosciuti_' . $nomeFileOriginale);
                // Pulisci file temporaneo locale
                Storage::disk('local')->delete('temp_ddc_' . time() . '_' . $nomeFileOriginale);
                return;
            }

            $numeroDDC = $dati['numero_ddc'];
            $commessa  = $dati['commessa'];

            // --- PASSO 2: TROVA LA COMMESSA SU DB ---
            $workflow = WfOrder::where('commessa', $commessa)->where('tipologia', 1)->first();

            if (!$workflow) {
                Log::warning("[ProcessQualityDDCPdf] Nessun workflow trovato per commessa {$commessa}. File: {$nomeFileOriginale}");
                // Sposta il file su Drive nella cartella archivio
                $disk->move($this->percorsoTransito, 'archivio/ddc_commessa_non_trovata_' . $nomeFileOriginale);
                // Pulisci file temporaneo locale
                Storage::disk('local')->delete('temp_ddc_' . time() . '_' . $nomeFileOriginale);
                return;
            }

            // --- PASSO 3: RINOMINA E UPLOAD SU DRIVE ---
            // Estrai l'ultima lettera/carattere prima di .pdf dal nome originale
            $nomeSenzaExt = pathinfo($nomeFileOriginale, PATHINFO_FILENAME);
            $ultimaLettera = substr(trim($nomeSenzaExt), -1);
            $nomeFileDrive = $numeroDDC . '_DDC_' . $ultimaLettera . '.pdf';
            $filePerDrive  = new \Illuminate\Http\File($percorsoAssoluto);

            $documentId = GoogleDrive::add_file($workflow->folder_drive, $nomeFileDrive, $filePerDrive, true);

            Log::info("[ProcessQualityDDCPdf] DDC caricato - Cartella: {$workflow->folder_drive} | File: {$nomeFileDrive} | DocumentId: {$documentId}");

            // --- PASSO 4: SALVA IL DOCUMENTO SU DB ---
            $typologieDocuments = $workflow::$typologieDocuments;
            WfDocument::addDocument(
                $workflow::$modelName,
                $workflow->id,
                $commessa,
                $nomeFileDrive,
                $typologieDocuments['DDC'],
                $documentId,
                $workflow->id
            );

            // Recupera il WfDocument appena creato (DDC, tipologia 25)
            $ddcDocument = WfDocument::where('id_file_drive', $documentId)->first();

            // --- PASSO 4b: ASSOCIA IL DDC AL DDT CORRISPONDENTE ---
            // numero_ddc contiene in realtà il numero del DDT di riferimento
            $ddtDocument = WfDocument::where('tipologia', 20)
                ->where('riferimento', $commessa)
                ->where('nome_file', $numeroDDC . '.pdf')
                ->first();

            if ($ddtDocument && $ddcDocument) {
                // Cerca se esiste già una validazione per questo DDT
                $validation = WfDocumentValidation::where('wf_document_id', $ddtDocument->id)
                    ->where('reparto', 'Qualita')
                    ->first();

                if ($validation) {
                    // Aggiorna solo il campo wf_document_id_ddc se non già valorizzato
                    if (!$validation->wf_document_id_ddc) {
                        $validation->wf_document_id_ddc = $ddcDocument->id;
                        $validation->save();
                        Log::info("[ProcessQualityDDCPdf] Associato DDC {$ddcDocument->id} a DDT {$ddtDocument->id} (validazione esistente aggiornata)");
                    }
                } else {
                    // Crea una nuova riga di validazione con stato DA-FARE
                    WfDocumentValidation::create([
                        'wf_document_id' => $ddtDocument->id,
                        'wf_document_id_ddc' => $ddcDocument->id,
                        'reparto' => 'Qualita',
                        'stato' => 'DA-FARE',
                        'tipologia_validazione' => 'Qualita_Standard',
                    ]);
                    Log::info("[ProcessQualityDDCPdf] Creata nuova validazione: DDT {$ddtDocument->id} -> DDC {$ddcDocument->id}");
                }
            } else {
                Log::info("[ProcessQualityDDCPdf] Nessun DDT trovato per commessa {$commessa} con nome_file {$numeroDDC}.pdf - associazione DDC sospesa");
            }

            // --- PASSO 5: ELIMINA IL FILE ORIGINALE DA DRIVE ---
            $disk->delete($this->percorsoTransito);
            
            // Pulisci file temporaneo locale
            Storage::disk('local')->delete('temp_ddc_' . time() . '_' . $nomeFileOriginale);
            
            Log::info("[ProcessQualityDDCPdf] Job completato per: {$nomeFileOriginale}");

        } catch (\Exception $e) {
            // Pulisci file temporaneo locale in caso di errore
            Storage::disk('local')->delete('temp_ddc_' . time() . '_' . $nomeFileOriginale);
            
            Log::error("[ProcessQualityDDCPdf] Errore su {$nomeFileOriginale}: " . $e->getMessage());
            throw $e;
        }
    }

    private function chiediAGemini($percorsoFile): mixed
    {
        $prompt = 'Sei un assistente di estrazione dati strutturati. Analizza il documento PDF fornito seguendo queste istruzioni tassative:

1. **Identificazione del documento**:
   * Il documento deve contenere la dicitura "DICHIARAZIONE DI CONFORMITA\'" (o varianti come "DICHIARAZIONE DI CONFORMITÀ").
   * Estrai il campo "No" che rappresenta il numero della DDC: è un numero di 10 cifre che inizia con 516 o 517.
   * Estrai il campo "ORDINE INTERNO N" che rappresenta il numero di commessa: è un numero di 10 cifre che inizia sempre con 46.

2. **Formato della Risposta**:
   * Restituisci esclusivamente un oggetto JSON strutturato esattamente così:
     {"numero_ddc": "5161234567", "commessa": "4612345678"}
   * Se il documento NON contiene la dicitura "DICHIARAZIONE DI CONFORMITA\'" oppure non riesci a trovare il numero DDC o la commessa, rispondi unicamente con la stringa: NON TROVATO.
   * Non includere markdown (no ```json o ```text), niente introduzioni o testo aggiuntivo. Sii totalmente sintetico.';

        $geminiService = new \App\Services\GeminiAiService();

        $rispostaRaw = $geminiService->analizzaFile(
            filePath: $percorsoFile,
            prompt: $prompt,
            mimeType: 'application/pdf'
        );

        $rispostaPulita = trim($rispostaRaw);

        if ($rispostaPulita === 'NON TROVATO') {
            return 'NON TROVATO';
        }

        $dati = json_decode($rispostaPulita, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($dati['numero_ddc'], $dati['commessa'])) {
            return $dati;
        }

        Log::error("[ProcessQualityDDCPdf] Risposta Gemini non valida: " . $rispostaRaw);
        return 'NON TROVATO';
    }
}
