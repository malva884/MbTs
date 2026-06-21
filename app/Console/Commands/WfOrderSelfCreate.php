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
    protected $description = 'Scansione locale dei file commesse e dispatching del processo di Ingest.';

    /**
     * Execute the console command.
     */
    public function handle()
	{
		$disk = Storage::disk('commesse');
		$this->path = public_path('file/Commesse/');

		// --- STEP 1: RECUPERO FILE BLOCCATI IN PROCESSING ---
		// Prende SOLO i file direttamente dentro la cartella 'processing'
		if ($disk->exists('processing')) {
			$stuckFiles = $disk->files('processing'); // files() non è ricorsivo, allFiles() sì
			
			foreach ($stuckFiles as $file) {
				$this->info("Rilevato file residuo da precedente riavvio: {$file}");
				// Invia direttamente al Job di elaborazione, poiché il file è già nella cartella corretta
				Dispatch(new ProcessOrderFile($file));
			}
		}

		// --- STEP 2: ELABORAZIONE NUOVI FILE ---
		// Prende SOLO i file della cartella principale (escludendo la sottocartella processing)
		$newFiles = $disk->files('/'); 

		foreach ($newFiles as $file) {
			try {
				// Salta i file nascosti di sistema
				if (str_starts_with(basename($file), '.')) {
					continue;
				}

				// Assicurati che la cartella esista
				if (!$disk->exists('processing')) {
					$disk->makeDirectory('processing');
				}

				$fileName = basename($file);
				$temporaryPath = 'processing/' . $fileName;

				// Se esiste già un duplicato in processing (caso raro ma possibile)
				if ($disk->exists($temporaryPath)) {
					$pathInfo = pathinfo($fileName);
					$newFileName = $pathInfo['filename'] . '_' . time() . '.' . ($pathInfo['extension'] ?? 'pdf');
					$temporaryPath = 'processing/' . $newFileName;
				}

				// Sposta il file e lancia il Job
				if ($disk->move($file, $temporaryPath)) {
					Dispatch(new ProcessOrderFile($temporaryPath));
				}

			} catch (\Exception $e) {
				Log::error("Errore pre-processing file nuovo [{$file}]: " . $e->getMessage());
				continue;
			}
		}
	}
}