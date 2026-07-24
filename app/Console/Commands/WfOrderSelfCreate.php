<?php

namespace App\Console\Commands;

use App\Jobs\ProcessOrderFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WfOrderSelfCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:commesse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scansione file commesse da Google Drive e dispatching del processo di Ingest.';

    /**
     * Execute the console command.
     */
    public function handle()
	{
		$disk = Storage::disk('commesse_drive');
        $this->info('[WfOrderSelfCreate] Inizio comando');

        // Verifica accesso al disco
        try {
            $test = $disk->exists('/');
            $this->info('[WfOrderSelfCreate] Disco commesse_drive accessibile');
        } catch (\Exception $e) {
            $this->error('[WfOrderSelfCreate] Errore accesso disco: ' . $e->getMessage());
            return;
        }

        // Verifica esistenza cartella Commesse
        if (!$disk->exists('Commesse')) {
            $this->error('[WfOrderSelfCreate] Cartella Commesse non trovata');
            return;
        }
        $this->info('[WfOrderSelfCreate] Cartella Commesse trovata');

		// --- STEP 1: RECUPERO FILE BLOCCATI IN PROCESSING ---
		// Prende SOLO i file direttamente dentro la cartella 'processing'
		if ($disk->exists('Commesse/processing')) {
			$stuckFiles = $disk->files('Commesse/processing'); // files() non è ricorsivo, allFiles() sì
            $this->info('[WfOrderSelfCreate] Trovati ' . count($stuckFiles) . ' file in processing');

			foreach ($stuckFiles as $file) {
				$this->info("Rilevato file residuo da precedente riavvio: {$file}");
                $this->info('[WfOrderSelfCreate] Dispatch job per file residuo: ' . $file);
                ProcessOrderFile::dispatch($file);
			}
		} else {
            $this->info('[WfOrderSelfCreate] Cartella Commesse/processing non trovata');
        }

		// --- STEP 2: ELABORAZIONE NUOVI FILE ---
		// Prende SOLO i file della cartella principale (escludendo la sottocartella processing)
		$newFiles = $disk->files('Commesse');
        $this->info('[WfOrderSelfCreate] Trovati ' . count($newFiles) . ' file in Commesse');

		foreach ($newFiles as $file) {
			try {
				// Salta i file nascosti di sistema
				if (str_starts_with(basename($file), '.')) {
					continue;
				}

				// Assicurati che le cartelle esistano
				if (!$disk->exists('Commesse/processing')) {
					$disk->makeDirectory('Commesse/processing');
				}
				if (!$disk->exists('Commesse/duplicati')) {
					$disk->makeDirectory('Commesse/duplicati');
				}

				$fileName = basename($file);
				$temporaryPath = 'Commesse/processing/' . $fileName;

				// Se esiste già un duplicato in processing, sposta in duplicati
				if ($disk->exists($temporaryPath)) {
					$duplicatePath = 'Commesse/duplicati/' . $fileName;
					// Se esiste anche in duplicati, aggiungi timestamp
					if ($disk->exists($duplicatePath)) {
						$pathInfo = pathinfo($fileName);
						$newFileName = $pathInfo['filename'] . '_' . time() . '.' . ($pathInfo['extension'] ?? 'pdf');
						$duplicatePath = 'Commesse/duplicati/' . $newFileName;
					}
					$disk->move($file, $duplicatePath);
					$this->info('[WfOrderSelfCreate] File duplicato spostato in duplicati: ' . $duplicatePath);
					continue;
				}

				// Sposta il file e lancia il Job
				if ($disk->move($file, $temporaryPath)) {
                    $this->info('[WfOrderSelfCreate] File spostato e job dispatchato: ' . $temporaryPath);
                    ProcessOrderFile::dispatch($temporaryPath);
				} else {
                    $this->error('[WfOrderSelfCreate] Impossibile spostare file: ' . $file);
                }

			} catch (\Exception $e) {
				$this->error("Errore pre-processing file nuovo [{$file}]: " . $e->getMessage());
				continue;
			}
		}

        $this->info('[WfOrderSelfCreate] Fine comando');
	}
}
