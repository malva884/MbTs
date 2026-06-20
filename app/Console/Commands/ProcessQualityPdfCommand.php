<?php

namespace App\Console\Commands;

use App\Jobs\ProcessQualityPdf;
use App\Services\GoogleDrive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ProcessQualityPdfCommand extends Command
{
    protected $signature = 'app:process-quality-pdf';

    protected $description = 'Scansiona la cartella Drive di transito DDT e dispatcha il job ProcessQualityPdf per ogni PDF trovato';

    public function handle()
    {
        $folderId = config('services.google_drive.ddt_folder_id');

        if (empty($folderId)) {
            $this->error('Variabile ID_GOOGLE_DDT non configurata nel .env o config non pubblicata');
            return 1;
        }

        // --- PASSO 1: PROCESSA FILE GIÀ IN TRANSITO (da esecuzioni precedenti fallite) ---
        $transitoDir = 'quality_pdf/transito';
        if (Storage::exists($transitoDir)) {
            $localFiles = Storage::files($transitoDir);
            $localPdfs = array_filter($localFiles, fn($f) => str_ends_with(strtolower($f), '.pdf'));

            if (!empty($localPdfs)) {
                $this->info('Trovati ' . count($localPdfs) . ' PDF in transito da riprocessare.');
                foreach ($localPdfs as $localFile) {
                    ProcessQualityPdf::dispatch($localFile);
                    $this->info("Job in coda (da transito): " . basename($localFile));
                }
            }
        }

        // --- PASSO 2: SCARICA NUOVI FILE DA DRIVE ---
        $files = GoogleDrive::search($folderId, 'google', 'files');

        if (!empty($files) && !$files->isEmpty()) {
            $pdfFiles = $files->filter(fn($f) => str_ends_with(strtolower($f->name), '.pdf'));

            if (!$pdfFiles->isEmpty()) {
                $this->info('Trovati ' . $pdfFiles->count() . ' PDF nuovi da Drive.');

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

                    ProcessQualityPdf::dispatch($percorsoTransito);
                    $this->info("Job in coda (da Drive): {$nomeFile}");
                }
            }
        }

        $this->info("Tutti i PDF sono stati inviati per l'elaborazione.");
        return 0;
    }
}
