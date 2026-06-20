<?php

namespace App\Jobs;

use App\Jobs\FirmaCommesse;
use App\Models\WfDocument;
use App\Models\WfOrder;
use App\Services\GoogleDrive;
use App\Services\GeminiAiService;
use Spatie\PdfToText\Pdf;
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
     *
     * @param string $relativeFilePath Percorso temporaneo nel disco (es. 'processing/file.pdf')
     */
    public function __construct($relativeFilePath)
    {
        $this->relativeFilePath = $relativeFilePath;
    }

    /**
     * Execute the job.
     */
    public function handle(GeminiAiService $geminiAiService)
    {
        $disk = Storage::disk('commesse');

        // Verifica se il file esiste ancora nel disco temporaneo
        if (!$disk->exists($this->relativeFilePath)) {
            Log::warning("Job ProcessOrderFile: File non trovato nel disco: {$this->relativeFilePath}");
            return;
        }

        $file = basename($this->relativeFilePath);
        $fullLocalPath = $disk->path($this->relativeFilePath);

        try {
            $tmp = explode('.', $file);
            $subs = explode(' ', $tmp[0]);

            $commessaNomeFile = null;
            $commessaRealeDocumento = null;
            $isRevisione = false;
            $rev = null;

            // 1. Analisi preliminare del NOME DEL FILE inserito manualmente
            if (!empty($subs[0])) {
                $commesseNomeTmp = explode("-", $subs[0]);
                if (preg_match('/46\d{8}/', $commesseNomeTmp[0], $matches)) {
                    $commessaNomeFile = $matches[0];
                }

                // Controllo se l'operatore ha specificato una revisione nel nome (es. "4600123456 R1")
                if (count($subs) > 1) {
                    $type = strtolower(substr($subs[1], 0, 1));
                    if ($type == 'r') {
                        $isRevisione = true;
                        $rev = !empty($subs[2]) ? $subs[2] : substr($subs[1], 1, 1);
                    }
                }
            }

            // =========================================================================
            // STRATEGIA A: Lettura del testo nativo con Spatie (OCR Locale veloce)
            // =========================================================================
            try {
                $binaryPath = str_replace('/', DIRECTORY_SEPARATOR, base_path('bin/pdftotext.exe'));
                if (file_exists($binaryPath)) {
                    $text = (new Pdf($binaryPath))->setPdf($fullLocalPath)->text();

                    if (!empty(trim($text))) {
                        // Cerchiamo il pattern 46 con 10 cifre all'interno del testo estratto
                        if (preg_match('/46\d{8}/', $text, $matches)) {
                            $commessaRealeDocumento = $matches[0];
                            Log::info("Spatie ha estratto la commessa dal testo nativo: {$commessaRealeDocumento}");
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Spatie fallito per il file {$file}, passo a Gemini. Errore: " . $e->getMessage());
            }

            // =========================================================================
            // STRATEGIA B: Fallback su Gemini AI (Se il PDF è una scansione/immagine della stampante)
            // =========================================================================
            if (!$commessaRealeDocumento) {
                Log::info("Testo nativo assente o non leggibile per [{$file}]. Intervengo con Gemini AI...");

                $promptCommessa = "Analizza la prima pagina di questo documento PDF.\n" .
                    "Concentrati vicino alla voce 'Ordine di Vendita N.' o diciture simili.\n" .
                    "REGOLE RIGIDE SUL NUMERO DA TROVARE:\n" .
                    "1. Deve essere composto ESCLUSIVAMENTE da cifre numeriche.\n" .
                    "2. Deve essere lungo ESATTAMENTE 10 cifre.\n" .
                    "3. Deve INIZIARE SEMPRE con '46' (esempio: 46xxxxxxxx).\n\n" .
                    "Rispondi SOLO con il numero a 10 cifre trovato. Se non esiste alcuna corrispondenza, rispondi: NON TROVATO.";

                $rispostaAi = $geminiAiService->analizzaFile($fullLocalPath, $promptCommessa, 'application/pdf');

                if ($rispostaAi && preg_match('/46\d{8}/', $rispostaAi, $matches)) {
                    $commessaRealeDocumento = $matches[0];
                    Log::info("Gemini AI ha identificato con successo la commessa interna: {$commessaRealeDocumento}");
                }
            }

            // =========================================================================
            // VALIDAZIONE, CONFRONTO E STRATEGIE DI RECUPERO (Tolleranza agli errori)
            // =========================================================================

            // CASO 1: Il documento interno contiene una commessa valida (Fonte della verità)
            if ($commessaRealeDocumento) {
                if ($commessaNomeFile !== $commessaRealeDocumento) {
                    Log::warning("DISCREPANZA RILEVATA su [{$file}]: Il file è stato rinominato '{$commessaNomeFile}' ma all'interno l'ordine riporta '{$commessaRealeDocumento}'. Correggo l'ingest usando il dato interno.");
                    $subs[0] = $commessaRealeDocumento;
                } else {
                    Log::info("CONFRONTO OK: Il nome del file coincide con la commessa interna del documento: {$commessaRealeDocumento}");
                    $subs[0] = $commessaRealeDocumento;
                }
            }
            // CASO 2: La scansione è illeggibile anche per l'IA, ma il nome inserito a mano è formattato bene
            elseif (!$commessaRealeDocumento && $commessaNomeFile) {
                Log::warning("RECOVERY SU [{$file}]: Impossibile leggere il testo interno (scansione corrotta/sbiadita). Mi fido del nome valido inserito manualmente dall'operatore: {$commessaNomeFile}");
                $commessaRealeDocumento = $commessaNomeFile;
                $subs[0] = $commessaNomeFile;
            }
            // CASO 3: Fallimento totale (Nessun dato valido dentro e nome file non conforme)
            else {
                Log::error("INGEST FALLITO: Impossibile determinare una commessa valida a 10 cifre (inizio 46) sia all'interno che nel nome del file {$file}. Sposto in failed.");
                $this->cleanupFailedFile($disk);
                return;
            }

            // Ricostruiamo la categoria basandoci sui primi 3 caratteri della commessa validata
            $cat = substr($commessaRealeDocumento, 0, 3);
            $commesse = explode("-", $subs[0]);

            // Query DB mirata sul singolo record elaborato dal worker
            $workflow = WfOrder::checkFlow($commesse[0], 1);

            // Recupero Categoria dal Database
            $category = DB::table('wf_categories')->where('categoria', '=', $cat)->first();
            if (empty($category->id)) {
                Log::warning("Commesse Ingest - Categoria '{$cat}' non trovata nel DB per il file {$file}. Salto.");
                $this->cleanupFailedFile($disk);
                return;
            }

            // Generazione dinamica e isolata delle cartelle Drive per l'anno/mese corrente
            $folderYearId = GoogleDrive::add_folder([$category->folder_drive], date('Y'), null, true);
            $folderMonthId = GoogleDrive::add_folder([$folderYearId], date('M'), null, true);

            // -------------------------------------------------------------------------
            // CASO 1: Commessa singola o Range Iniziale
            // -------------------------------------------------------------------------
            if (!$isRevisione) {
                if (!empty($workflow->id)) {
                    // Se il flusso esiste già per questa commessa singola, elimina il temporaneo ed esce
                    $disk->delete($this->relativeFilePath);
                    return;
                }

                $workflow = WfOrder::addWorkflow($subs[0], 1, $category, $subs[0], null, $folderMonthId, null, true);
                $workflow->folder_drive = GoogleDrive::add_folder([$folderMonthId], $workflow->commessa, null, true);
                $id_file = GoogleDrive::add_file($workflow->folder_drive, $file, $fullLocalPath, true);
                $workflow->id_file_drive = $id_file['id'];
                $workflow->save();

                WfDocument::addDocument($workflow::$modelName, $workflow->id, $subs[0], $file, 1, $workflow->id_file_drive, $workflow->id);

                // Sotto-Gestione: Range di Commesse (se presente il carattere trattino '-')
                if (count($commesse) == 2) {
                    $commessa_t = $commesse[0];
                    $tmpStr = substr($commesse[0], 0, strlen($commesse[0]) - strlen($commesse[1]));
                    $ultima_commessa = $tmpStr . $commesse[1];

                    while ($commessa_t <= $ultima_commessa) {
                        $workflow_t = WfOrder::addWorkflow($commessa_t, 1, $category, $subs[0], null, $folderMonthId, $workflow->id, false, $workflow->folder_drive);
                        $workflow_t->folder_drive = GoogleDrive::add_folder([$folderMonthId], $workflow_t->commessa, null, true);
                        $id_log = GoogleDrive::add_file($workflow_t->folder_drive, $file, $fullLocalPath, true);
                        $workflow_t->id_file_drive = $id_log['id'];
                        $workflow_t->save();

                        WfDocument::addDocument($workflow::$modelName, $workflow_t->id, $commessa_t, $file, 1, $workflow_t->id_file_drive, $workflow_t->id);
                        $commessa_t++;
                    }
                }

                if ($workflow->id_file_drive) {
                    $disk->delete($this->relativeFilePath);
                }
            }
            // -------------------------------------------------------------------------
            // CASO 2: Gestione Revisioni (Attivato se è presente il flag 'R' nel nome originale)
            // -------------------------------------------------------------------------
            else {
                if (empty($workflow->id)) {
                    $workflow_old = WfOrder::checkFlowOld($subs[0]);
                    if (!empty($workflow_old->id)) {
                        $path_pdf = storage_path('app/pdf/');
                        $workflow = WfOrder::addWorkflow($subs[0], 1, $category, $subs[0], null, $folderMonthId, null, true, null, 'Approved');

                        $fileContents = GoogleDrive::download($workflow_old->id_file_drive);
                        Storage::disk('temp')->put($workflow_old->nomeFile, $fileContents);
                        $workflow->folder_drive = GoogleDrive::add_folder([$folderMonthId], $workflow->commessa, null, true);
                        $id_file = GoogleDrive::add_file($workflow->folder_drive, $workflow_old->nomeFile, $path_pdf . $workflow_old->nomeFile, true);
                        $workflow->id_file_drive = $id_file['id'];

                        WfDocument::addDocument($workflow::$modelName, $workflow->id, $workflow->commessa, $workflow_old->nomeFile, 1, $workflow->id_file_drive, $workflow->id);

                        if ($workflow_old->status == 4) {
                            $logFile = GoogleDrive::search($workflow_old->path_folder_drive, 'google', 'file', $workflow_old->commessa . '_' . $workflow_old->end_date . '.pdf', false);
                            if ($logFile) {
                                $fileContents = GoogleDrive::download($logFile);
                                Storage::disk('temp')->put('Log_' . $workflow_old->commessa . '.pdf', $fileContents);
                                $id_file_c = GoogleDrive::add_file($workflow->folder_drive, $workflow_old->nomeFile, $path_pdf . 'Log_' . $workflow_old->commessa . '.pdf', false);
                                $workflow->id_log_drive = $id_file_c['id'];

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
                    $workflow_rev->id_file_drive = $id_file['id'];

                    WfDocument::addDocument($workflow_rev::$modelName, $workflow_rev->id, $workflow_rev->commessa, $file, 3, $workflow_rev->id_file_drive, $workflow->id);
                    $workflow_rev->save();

                    if ($workflow_rev->id_file_drive) {
                        $disk->delete($this->relativeFilePath);
                    }

                    $workflow->id = $workflow_rev->id;
                }
            }

            // Se tutto è andato a buon fine ed è stato creato/aggiornato un workflow id, lancia il processo firma
            if (!empty($workflow->id)) {
                dispatch(new FirmaCommesse($workflow->id));
            }

        } catch (\Exception $e) {
            Log::error("Commesse Ingest - Errore critico nel Job per il file [{$file}]: " . $e->getMessage());
            $this->cleanupFailedFile($disk);
            throw $e; // Rilanciamo l'eccezione per tracciarla nel pannello delle code (es. Horizon)
        }
    }

    /**
     * Ripristina o sposta il file in caso di fallimento critico.
     */
    protected function cleanupFailedFile($disk)
    {
        if ($disk->exists($this->relativeFilePath)) {
            if (!$disk->exists('failed')) {
                $disk->makeDirectory('failed');
            }
            $failedPath = 'failed/' . basename($this->relativeFilePath);
            $disk->move($this->relativeFilePath, $failedPath);
        }
    }
}
