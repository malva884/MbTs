<?php

namespace App\Console\Commands;

use App\Jobs\ProcessQualityPdf;
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
            $test = $disk->exists('/');
            $this->info('[ProcessQualityPdfCommand] Disco quality_pdf_drive accessibile');
        } catch (\Exception $e) {
            $this->error('[ProcessQualityPdfCommand] Errore accesso disco: ' . $e->getMessage());
            Log::error("[ProcessQualityPdfCommand] Errore accesso disco: " . $e->getMessage());
            return 1;
        }

        // --- PASSO 1: PROCESSA FILE GIÀ IN PROCESSING (da esecuzioni precedenti fallite) ---
        if ($disk->exists('processing')) {
            $stuckFiles = $disk->files('processing');
            $stuckPdfs = array_filter($stuckFiles, fn($f) => str_ends_with(strtolower(basename($f)), '.pdf'));
            
            if (!empty($stuckPdfs)) {
                $this->info('[ProcessQualityPdfCommand] Trovati ' . count($stuckPdfs) . ' PDF in processing da riprocessare.');
                foreach ($stuckPdfs as $file) {
                    $this->info("Rilevato file residuo da precedente riavvio: {$file}");
                    ProcessQualityPdf::dispatch($file);
                    $this->info('[ProcessQualityPdfCommand] Job dispatchato per file residuo: ' . $file);
                    Log::info("[ProcessQualityPdfCommand] Job dispatchato per file residuo: {$file}");
                }
            }
        } else {
            // Crea cartella processing se non esiste
            //$disk->makeDirectory('processing');
            $this->info('[ProcessQualityPdfCommand] Cartella processing creata');
        }

        // --- PASSO 2: ELABORAZIONE NUOVI FILE ---
        // Prende SOLO i file della cartella principale (escludendo la sottocartella processing)
        $newFiles = $disk->files('/');
        $this->info('[ProcessQualityPdfCommand] Trovati ' . count($newFiles) . ' file nella cartella principale');

        foreach ($newFiles as $file) {
            try {
                // Salta i file nascosti di sistema e la cartella processing
                if (str_starts_with(basename($file), '.') || str_contains($file, 'processing')) {
                    continue;
                }

                // Salta i file non PDF
                if (!str_ends_with(strtolower(basename($file)), '.pdf')) {
                    continue;
                }

                $fileName = basename($file);
                $temporaryPath = 'processing/' . $fileName;

                // Se esiste già un duplicato in processing
                if ($disk->exists($temporaryPath)) {
                    $pathInfo = pathinfo($fileName);
                    $newFileName = $pathInfo['filename'] . '_' . time() . '.' . ($pathInfo['extension'] ?? 'pdf');
                    $temporaryPath = 'processing/' . $newFileName;
                }

                // Sposta il file e lancia il Job
                if ($disk->move($file, $temporaryPath)) {
                    $this->info('[ProcessQualityPdfCommand] File spostato e job dispatchato: ' . $temporaryPath);
                    ProcessQualityPdf::dispatch($temporaryPath);
                    Log::info("[ProcessQualityPdfCommand] File spostato e job dispatchato: {$temporaryPath}");
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
