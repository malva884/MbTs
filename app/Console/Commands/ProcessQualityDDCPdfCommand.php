<?php

namespace App\Console\Commands;

use App\Jobs\ProcessQualityDDCPdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessQualityDDCPdfCommand extends Command
{
    protected $signature = 'app:process-quality-ddc-pdf';

    protected $description = 'Scansiona la cartella Drive DDC, estrae numero DDC e commessa con Gemini e carica nella cartella commessa';

    public function handle()
    {
        $disk = Storage::disk('quality_ddc_drive');
        $this->info('[ProcessQualityDDCPdfCommand] Inizio comando');
        
        // Verifica accesso al disco
        try {
            $test = $disk->exists('Qualità DDC');
            $this->info('[ProcessQualityDDCPdfCommand] Disco quality_ddc_drive accessibile');
        } catch (\Exception $e) {
            $this->error('[ProcessQualityDDCPdfCommand] Errore accesso disco: ' . $e->getMessage());
            Log::error("[ProcessQualityDDCPdfCommand] Errore accesso disco: " . $e->getMessage());
            return 1;
        }

        // --- PASSO 1: PROCESSA FILE GIÀ IN PROCESSING (da esecuzioni precedenti fallite) ---
        if ($disk->exists('Qualità DDC/processing')) {
            $stuckFiles = $disk->files('Qualità DDC/processing');
            $stuckPdfs = array_filter($stuckFiles, fn($f) => str_ends_with(strtolower(basename($f)), '.pdf'));
            
            if (!empty($stuckPdfs)) {
                $this->info('[ProcessQualityDDCPdfCommand] Trovati ' . count($stuckPdfs) . ' PDF DDC in processing da riprocessare.');
                foreach ($stuckPdfs as $file) {
                    $this->info("Rilevato file residuo da precedente riavvio: {$file}");
                    ProcessQualityDDCPdf::dispatch($file, basename($file));
                    $this->info('[ProcessQualityDDCPdfCommand] Job dispatchato per file residuo: ' . $file);
                }
            }
            else {
                $this->info('[ProcessQualityDDCPdfCommand] Nessun file in processing da riprocessare.');
            }
        } else {
            // Crea cartella processing se non esiste
            $disk->makeDirectory('Qualità DDC/processing');
            $this->info('[ProcessQualityDDCPdfCommand] Cartella processing creata');
        }

        // --- PASSO 2: ELABORAZIONE NUOVI FILE ---
        // Prende SOLO i file della cartella principale (escludendo la sottocartella processing)
        $newFiles = $disk->files('Qualità DDC');
        $this->info('[ProcessQualityDDCPdfCommand] Trovati ' . count($newFiles) . ' file nella cartella principale');

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
                $temporaryPath = 'Qualità DDC/processing/' . $fileName;

                // Se esiste già un duplicato in processing
                if ($disk->exists($temporaryPath)) {
                    $pathInfo = pathinfo($fileName);
                    $newFileName = $pathInfo['filename'] . '_' . time() . '.' . ($pathInfo['extension'] ?? 'pdf');
                    $temporaryPath = 'Qualità DDC/processing/' . $newFileName;
                }

                // Sposta il file e lancia il Job
                if ($disk->move($file, $temporaryPath)) {
                    $this->info('[ProcessQualityDDCPdfCommand] File spostato e job dispatchato: ' . $temporaryPath);
                    ProcessQualityDDCPdf::dispatch($temporaryPath, basename($temporaryPath));
                } else {
                    $this->error('[ProcessQualityDDCPdfCommand] Impossibile spostare file: ' . $file);
                    Log::error("[ProcessQualityDDCPdfCommand] Impossibile spostare file: {$file}");
                }

            } catch (\Exception $e) {
                $this->error("Errore pre-processing file nuovo [{$file}]: " . $e->getMessage());
                Log::error("[ProcessQualityDDCPdfCommand] Errore pre-processing file [{$file}]: " . $e->getMessage());
                continue;
            }
        }

        $this->info('[ProcessQualityDDCPdfCommand] Fine comando');
        return 0;
    }
}
