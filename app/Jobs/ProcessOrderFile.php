<?php

namespace App\Jobs;

use App\Jobs\FirmaCommesse;
use App\Models\WfDocument;
use App\Models\WfOrder;
use App\Services\GoogleDrive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessOrderFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $relativeFilePath;

    /**
     * Create a new job instance.
     */
    public function __construct($relativeFilePath)
    {
        // Riceve il percorso temporaneo del file (es. 'processing/file.pdf')
        $this->relativeFilePath = $relativeFilePath;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $disk = Storage::disk('commesse_drive');

        // Verifica se il file esiste ancora nel disco temporaneo
        if (!$disk->exists($this->relativeFilePath)) {
            return;
        }

        $file = basename($this->relativeFilePath);

        // Con Google Drive, dobbiamo scaricare il file in locale temporaneamente
        $tempPath = storage_path('app/pdf/');
        $fullLocalPath = $tempPath . $file;

        // Scarica il file da Google Drive
        $fileContents = $disk->get($this->relativeFilePath);
        file_put_contents($fullLocalPath, $fileContents);

        // Salva il nome originale per recuperare eventuali revisioni
        $nomeOriginale = $file;

        // Estrai commessa dal PDF se possibile
       /* $commessaEstratta = $this->estraiCommessaDaPDF($fullLocalPath);
        if ($commessaEstratta) {
            $estensione = pathinfo($file, PATHINFO_EXTENSION);
            // Estrai la parte di revisione dal nome originale se presente
            $tmpOriginale = explode('.', $nomeOriginale);
            $subsOriginale = explode(' ', $tmpOriginale[0]);
            $revisione = '';
            if (count($subsOriginale) > 1) {
                $revisione = ' ' . $subsOriginale[1];
            }
            $file = $commessaEstratta . $revisione . '.' . $estensione;
            Log::info("[ProcessOrderFile] Commessa estratta da PDF: {$commessaEstratta}, revisione: {$revisione}, nuovo nome file: {$file}");
        }*/

        try {
            $tmp = explode('.', $file);
            $subs = explode(' ', $tmp[0]);

            if (empty($subs[0])) {
                $this->cleanupFailedFile($disk);
                return;
            }

            $cat = substr($subs[0], 0, 3);
            $commesse = explode("-", $subs[0]);

            // Query DB mirata sul singolo record elaborato dal worker
            $workflow = WfOrder::checkFlow($commesse[0], 1);

            // Recupero Categoria
            $category = DB::table('wf_categories')->where('categoria', '=', $cat)->first();
            if (empty($category->id)) {
                Log::warning("Commesse Ingest - Categoria '{$cat}' non trovata nel DB per il file {$file}. Salto.");
                $this->cleanupFailedFile($disk);
                return;
            }

            // Generazione dinamica e isolata delle cartelle Drive per l'anno/mese corrente
            $folderYearId = GoogleDrive::add_folder([$category->folder_drive], date('Y'), null, true);
            $folderMonthId = GoogleDrive::add_folder([$folderYearId], date('M'), null, true);

            // CASO 1: Commessa singola o Range Iniziale
            if (count($subs) == 1) {
                if (!empty($workflow->id)) {
                    $disk->delete($this->relativeFilePath);
                    return;
                }

                $workflow = WfOrder::addWorkflow($subs[0], 1, $category, $subs[0], null, $folderMonthId, null, true);
                $workflow->folder_drive = GoogleDrive::add_folder([$folderMonthId], $workflow->commessa, null, true);
                $id_file = GoogleDrive::add_file($workflow->folder_drive, $file, $fullLocalPath, true);
                $workflow->id_file_drive = $id_file;
                $workflow->save();

                WfDocument::addDocument($workflow::$modelName, $workflow->id, $subs[0], $file, 1, $workflow->id_file_drive, $workflow->id);

                // Gestione Range di Commesse
                if (count($commesse) == 2) {
                    $commessa_t = $commesse[0];
                    $tmpStr = substr($commesse[0], 0, strlen($commesse[0]) - strlen($commesse[1]));
                    $ultima_commessa = $tmpStr . $commesse[1];

                    while ($commessa_t <= $ultima_commessa) {
                        $workflow_t = WfOrder::addWorkflow($commessa_t, 1, $category, $subs[0], null, $folderMonthId, $workflow->id, false, $workflow->folder_drive);
                        $workflow_t->folder_drive = GoogleDrive::add_folder([$folderMonthId], $workflow_t->commessa, null, true);
                        $id_log = GoogleDrive::add_file($workflow_t->folder_drive, $file, $fullLocalPath, true);
                        $workflow_t->id_file_drive = $id_log;
                        $workflow_t->save();

                        WfDocument::addDocument($workflow::$modelName, $workflow_t->id, $commessa_t, $file, 1, $workflow_t->id_file_drive, $workflow_t->id);
                        $commessa_t++;
                    }
                }

                if ($workflow->id_file_drive) {
                    $disk->delete($this->relativeFilePath);
                }
            } 
            // CASO 2: Gestione Revisioni
            else {
                $type = strtolower(substr($subs[1], 0, 1));

                if ($type == 'r') {
                    $rev = !empty($subs[2]) ? $subs[2] : substr($subs[1], 1, 1);

                    if (empty($workflow->id)) {
                        $workflow_old = WfOrder::checkFlowOld($subs[0]);
                        if (!empty($workflow_old->id)) {
                            $path_pdf = storage_path('app/pdf/');
                            $workflow = WfOrder::addWorkflow($subs[0], 1, $category, $subs[0], null, $folderMonthId, null, true, null, 'Approved');

                            $fileContents = GoogleDrive::download($workflow_old->id_file_drive);
                            Storage::disk('temp')->put($workflow_old->nomeFile, $fileContents);
                            $workflow->folder_drive = GoogleDrive::add_folder([$folderMonthId], $workflow->commessa, null, true);
                            $id_file = GoogleDrive::add_file($workflow->folder_drive, $workflow_old->nomeFile, $path_pdf . $workflow_old->nomeFile, true);
                            $workflow->id_file_drive = $id_file;

                            WfDocument::addDocument($workflow::$modelName, $workflow->id, $workflow->commessa, $workflow_old->nomeFile, 1, $workflow->id_file_drive, $workflow->id);

                            if ($workflow_old->status == 4) {
                                $logFile = GoogleDrive::search($workflow_old->path_folder_drive, 'google', 'file', $workflow_old->commessa . '_' . $workflow_old->end_date . '.pdf', false);
                                if ($logFile) {
                                    $fileContents = GoogleDrive::download($logFile);
                                    Storage::disk('temp')->put('Log_' . $workflow_old->commessa . '.pdf', $fileContents);
                                    $id_file_c = GoogleDrive::add_file($workflow->folder_drive, $workflow_old->nomeFile, $path_pdf . 'Log_' . $workflow_old->commessa . '.pdf', false);
                                    $workflow->id_log_drive = $id_file_c;

                                    WfDocument::addDocument($workflow::$modelName, $workflow->id, $workflow->commessa, 'Log_' . $workflow_old->commessa . '.pdf', 100, $workflow->id_log_drive, $workflow->id);
                                    @unlink($path_pdf . 'Log_' . $workflow_old->commessa . '.pdf');
                                }
                            }
                            $workflow->save();
                        }
                    }

                    $workflow_rev = WfOrder::checkFlow($subs[0], 3, $rev);

                    if (!empty($workflow->id) && empty($workflow_rev->id)) {
                        $workflow_rev = WfOrder::addWorkflow($subs[0], 3, $category, $subs[0], $rev, $folderMonthId, $workflow->id, true);
                        $workflow_rev->folder_drive = $workflow->folder_drive;
                        $id_file = GoogleDrive::add_file($workflow->folder_drive, $file, $fullLocalPath, true);
                        $workflow_rev->id_file_drive = $id_file;

                        WfDocument::addDocument($workflow_rev::$modelName, $workflow_rev->id, $workflow_rev->commessa, $file, 3, $workflow_rev->id_file_drive, $workflow->id);
                        $workflow_rev->save();

                        if ($workflow_rev->id_file_drive) {
                            $disk->delete($this->relativeFilePath);
                        }

                        $workflow->id = $workflow_rev->id;
                    }
                }
            }

            if (!empty($workflow->id)) {
                dispatch(new FirmaCommesse($workflow->id));
            }

        } catch (\Exception $e) {
            Log::error("Commesse Ingest - Errore critico nel Job per il file [{$file}]: " . $e->getMessage());
            $this->cleanupFailedFile($disk);
            throw $e; // Rilancia l'eccezione per marcare il job come fallito sulla coda
        } finally {
            // Pulisci sempre il file temporaneo locale
            if (file_exists($fullLocalPath)) {
                @unlink($fullLocalPath);
            }
        }
    }

    /**
     * Ripristina o sposta il file in caso di fallimento critico.
     */
    protected function cleanupFailedFile($disk)
    {
        if ($disk->exists($this->relativeFilePath)) {
            $failedPath = 'Commesse/failed/' . basename($this->relativeFilePath);
            $disk->move($this->relativeFilePath, $failedPath);
        }
    }

    /**
     * Estrae il numero di commessa dalla prima pagina del PDF usando Gemini AI.
     * Cerca un numero di 10 cifre che inizia con 46.
     * 
     * @param string $percorsoFile Percorso locale del file PDF
     * @return string|null Numero commessa se trovato, null altrimenti
     */
    private function estraiCommessaDaPDF($percorsoFile): ?string
    {
        $prompt = 'Sei un assistente di estrazione dati. Analizza la prima pagina del documento PDF fornito seguendo queste istruzioni tassative:

1. **IMPORTANTE: Ignora il nome del file**:
   * NON considerare il nome del file per l\'estrazione della commessa.
   * Leggi SOLO il contenuto del documento PDF, in particolare la prima pagina.
   * Il numero di commessa deve essere estratto esclusivamente dal contenuto del documento, non dal nome del file.

2. **Cerca il numero di commessa**:
   * Cerca nella prima pagina del documento la dicitura "Ordine di vendita N." (o varianti come "Ordine di vendita", "ORDINE DI VENDITA N.", "ordine di vendita n.").
   * Proprio accanto o vicino a questa dicitura troverai il numero di commessa: è un numero di 10 cifre che inizia sempre con 46.
   * Se trovi più numeri di 10 cifre che iniziano con 46, scegli quello che appare più vicino alla dicitura "Ordine di vendita N.".
   * Questo numero rappresenta la commessa del documento.

3. **Formato della Risposta**:
   * Se trovi il numero di commessa nel contenuto del documento, restituisci esclusivamente il numero (es. "4612345678").
   * Se NON trovi la dicitura "Ordine di vendita N." o il numero di commessa associato, rispondi unicamente con la stringa: NON TROVATO.
   * Non includere markdown (no ```json o ```text), niente introduzioni o testo aggiuntivo. Sii totalmente sintetico.';

        try {
            $geminiService = new \App\Services\GeminiAiService();
            $rispostaRaw = $geminiService->analizzaFile(
                filePath: $percorsoFile,
                prompt: $prompt,
                mimeType: 'application/pdf'
            );

            $rispostaPulita = trim($rispostaRaw);

            if ($rispostaPulita === 'NON TROVATO') {
                return null;
            }

            // Verifica che sia un numero di 10 cifre che inizia con 46
            if (preg_match('/^46\d{8}$/', $rispostaPulita)) {
                return $rispostaPulita;
            }

            return null;

        } catch (\Exception $e) {
            Log::error("[ProcessOrderFile] Errore nell'estrazione commessa da PDF: " . $e->getMessage());
            return null;
        }
    }
}
