<?php

namespace App\Console\Commands;

use App\Jobs\ProcessQualityPdf;
use App\Models\JobLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessQualityPdfCommand extends Command
{
    protected $signature = 'app:process-quality-pdf';

    protected $description = 'Scansiona la cartella Drive di transito DDT e dispatcha il job ProcessQualityPdf per ogni PDF trovato';

    public function handle()
    {
        $disk = Storage::disk('quality_pdf_drive');
        $this->info('[ProcessQualityPdfCommand] Inizio comando');
        
        // Verifica accesso al disco
        try {
            $test = $disk->exists('DDT');
            $this->info('[ProcessQualityPdfCommand] Disco quality_pdf_drive accessibile');

        } catch (\Exception $e) {
            $this->error('[ProcessQualityPdfCommand] Errore accesso disco: ' . $e->getMessage());
            Log::error("[ProcessQualityPdfCommand] Errore accesso disco: " . $e->getMessage());
            return 1;
        }

        // --- PASSO 1: PROCESSA FILE GIÀ IN PROCESSING (da esecuzioni precedenti fallite) ---
        try {
            if ($disk->exists('DDT/processing')) {
                $stuckFiles = $disk->files('DDT/processing');
                $stuckPdfs = array_filter($stuckFiles, fn($f) => str_ends_with(strtolower(basename($f)), '.pdf'));
                
                if (!empty($stuckPdfs)) {
                    $this->info('[ProcessQualityPdfCommand] Trovati ' . count($stuckPdfs) . ' PDF in processing da riprocessare.');

                    foreach ($stuckPdfs as $file) {
                        $isAlreadyRunning = JobLog::where('job_name', 'ProcessQualityPdf')
                            ->where('status', 'running')
                            ->where('payload->path', $file)
                            ->where('started_at', '>=', now()->subMinutes(10))
                            ->exists();

                        if ($isAlreadyRunning) {
                            $this->info("[ProcessQualityPdfCommand] File già in elaborazione, salto: {$file}");
                            continue;
                        }

                        $this->info("Rilevato file residuo da precedente riavvio: {$file}");
                        ProcessQualityPdf::dispatch($file);
                        $this->info('[ProcessQualityPdfCommand] Job dispatchato per file residuo: ' . $file);
                    }
                }
                else {
                    $this->info('[ProcessQualityPdfCommand] Nessun file in processing da riprocessare.');
                }
            } else {
                // Crea cartella processing se non esiste
                $disk->makeDirectory('DDT/processing');
                $this->info('[ProcessQualityPdfCommand] Cartella processing creata');
            }
        } catch (\Exception $e) {
            $this->error('[ProcessQualityPdfCommand] Errore verifica/creazione cartella DDT/processing: ' . $e->getMessage());
            Log::error("[ProcessQualityPdfCommand] Errore verifica/creazione cartella DDT/processing: " . $e->getMessage());
        }

        // --- PASSO 2: ELABORAZIONE NUOVI FILE ---
        // Prende SOLO i file della cartella principale (escludendo la sottocartella processing)
        try {
            $newFiles = $disk->files('DDT');
            $this->info('[ProcessQualityPdfCommand] Trovati ' . count($newFiles) . ' file nella cartella principale');
        } catch (\Exception $e) {
            $this->error('[ProcessQualityPdfCommand] Errore lettura cartella DDT: ' . $e->getMessage());
            Log::error("[ProcessQualityPdfCommand] Errore lettura cartella DDT: " . $e->getMessage());
            return 1;
        }

        foreach ($newFiles as $file) {
            try {
                // Salta i file nascosti di sistema e le sottocartelle processing / pending_workflow
                if (str_starts_with(basename($file), '.') || str_contains($file, 'processing') || str_contains($file, 'pending_workflow')) {
                    continue;
                }

                // Salta i file non PDF
                if (!str_ends_with(strtolower(basename($file)), '.pdf')) {
                    continue;
                }

                $fileName = basename($file);
                $temporaryPath = 'DDT/processing/' . $fileName;

                // Se esiste già un duplicato in processing
                if ($disk->exists($temporaryPath)) {
                    $pathInfo = pathinfo($fileName);
                    $newFileName = $pathInfo['filename'] . '_' . time() . '.' . ($pathInfo['extension'] ?? 'pdf');
                    $temporaryPath = 'DDT/processing/' . $newFileName;
                }

                // Sposta il file e lancia il Job
                if ($disk->move($file, $temporaryPath)) {
                    $this->info('[ProcessQualityPdfCommand] File spostato e job dispatchato: ' . $temporaryPath);
                    ProcessQualityPdf::dispatch($temporaryPath);
                } else {
                    $this->error('[ProcessQualityPdfCommand] Impossibile spostare file: ' . $file);
                    Log::error("[ProcessQualityPdfCommand] Impossibile spostare file: {$file}");
                }

            } catch (\Exception $e) {
                $this->error("Errore pre-processing file nuovo [{$file}]: " . $e->getMessage());
                Log::error("[ProcessQualityPdfCommand] Errore pre-processing file [{$file}]: " . $e->getMessage());
                continue;
            }
        }

        $this->info('[ProcessQualityPdfCommand] Fine comando');
        return 0;
    }
}
