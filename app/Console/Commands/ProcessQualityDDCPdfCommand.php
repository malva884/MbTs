<?php

namespace App\Console\Commands;

use App\Jobs\ProcessQualityDDCPdf;
use App\Services\GoogleDrive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ProcessQualityDDCPdfCommand extends Command
{
    protected $signature = 'app:process-quality-ddc-pdf';

    protected $description = 'Scansiona la cartella Drive DDC, estrae numero DDC e commessa con Gemini e carica nella cartella commessa';

    public function handle()
    {
        $folderId = config('services.google_drive.ddc_folder_id');

        if (empty($folderId)) {
            $this->error('Variabile ID_GOOGLE_DDC non configurata nel .env');
            return 1;
        }

        // --- PASSO 1: PROCESSA FILE GIÀ IN TRANSITO (da esecuzioni precedenti fallite) ---
        $transitoDir = 'quality_pdf/transito_ddc';
        if (Storage::exists($transitoDir)) {
            $localFiles = Storage::files($transitoDir);
            $localPdfs = array_filter($localFiles, fn($f) => str_ends_with(strtolower($f), '.pdf'));

            if (!empty($localPdfs)) {
                $this->info('Trovati ' . count($localPdfs) . ' PDF DDC in transito da riprocessare.');
                foreach ($localPdfs as $localFile) {
                    $nomeFile = basename($localFile);
                    ProcessQualityDDCPdf::dispatch($localFile, $nomeFile);
                    $this->info("Job DDC in coda (da transito): {$nomeFile}");
                }
            }
        }

        // --- PASSO 2: SCARICA NUOVI FILE DA DRIVE ---
        $files = GoogleDrive::search($folderId, 'google', 'files');

        if (!empty($files) && !$files->isEmpty()) {
            $pdfFiles = $files->filter(fn($f) => str_ends_with(strtolower($f->name), '.pdf'));

            if (!$pdfFiles->isEmpty()) {
                $this->info('Trovati ' . $pdfFiles->count() . ' PDF DDC nuovi da Drive.');

                foreach ($pdfFiles as $driveFile) {
                    $nomeFile = $driveFile->name;
                    $fileId   = $driveFile->id;

                    $contenuto = GoogleDrive::download($fileId);

                    if (empty($contenuto)) {
                        $this->error("Impossibile scaricare: {$nomeFile}");
                        continue;
                    }

                    $percorsoTransito = $transitoDir . '/' . $nomeFile;
                    Storage::put($percorsoTransito, $contenuto);

                    GoogleDrive::delated($fileId, 'google');

                    ProcessQualityDDCPdf::dispatch($percorsoTransito, $nomeFile);
                    $this->info("Job DDC in coda (da Drive): {$nomeFile}");
                }
            }
        }

        $this->info("Tutti i PDF DDC sono stati inviati per l'elaborazione.");
        return 0;
    }
}
