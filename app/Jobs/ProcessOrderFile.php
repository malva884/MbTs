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
        $disk = Storage::disk('commesse');
        
        // Verifica se il file esiste ancora nel disco temporaneo
        if (!$disk->exists($this->relativeFilePath)) {
            return;
        }

        $file = basename($this->relativeFilePath);
        $fullLocalPath = $disk->path($this->relativeFilePath);

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
                $workflow->id_file_drive = $id_file['id'];
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
            }

            if (!empty($workflow->id)) {
                dispatch(new FirmaCommesse($workflow->id));
            }

        } catch (\Exception $e) {
            Log::error("Commesse Ingest - Errore critico nel Job per il file [{$file}]: " . $e->getMessage());
            $this->cleanupFailedFile($disk);
            throw $e; // Rilancia l'eccezione per marcare il job come fallito sulla coda
        }
    }

    /**
     * Ripristina o sposta il file in caso di fallimento critico.
     */
    protected function cleanupFailedFile($disk)
    {
        if ($disk->exists($this->relativeFilePath)) {
            $failedPath = 'failed/' . basename($this->relativeFilePath);
            $disk->move($this->relativeFilePath, $failedPath);
        }
    }
}