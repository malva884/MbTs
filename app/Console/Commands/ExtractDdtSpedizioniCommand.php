<?php

namespace App\Console\Commands;

use App\Jobs\ExtractDdtSpedizione;
use App\Models\DdtSpedizione;
use App\Models\JobLog;
use App\Models\WfDocument;
use App\Models\WfOrder;
use App\Services\CalcoloCostoSpedizioneService;
use App\Services\GoogleDrive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExtractDdtSpedizioniCommand extends Command
{
    protected $signature = 'app:extract-ddt-spedizioni
        {--recalc-costi : Ricalcola il costo di spedizione per tutti i DDT senza costo o con costo nullo}
        {--recalc-vettori : Rielabora con AI i DDT privi di vettore valido estraendolo dal PDF e ricalcolando i costi}';

    protected $description = 'Scansiona la cartella Drive dei DDT di spedizione e dispatcha il job ExtractDdtSpedizione per ogni PDF trovato';

    public function handle()
    {
        $disk = Storage::disk('ddt_spedizioni_drive');
        $this->info('[ExtractDdtSpedizioniCommand] Inizio comando');

        if ($this->option('recalc-vettori')) {
            return $this->rielaboraVettoriMancanti();
        }

        // Verifica accesso al disco
        try {
            $test = $disk->exists('Bolle');
            $this->info('[ExtractDdtSpedizioniCommand] Disco ddt_spedizioni_drive accessibile');
        } catch (\Exception $e) {
            $this->error('[ExtractDdtSpedizioniCommand] Errore accesso disco: ' . $e->getMessage());
            Log::error("[ExtractDdtSpedizioniCommand] Errore accesso disco: " . $e->getMessage());
            return 1;
        }

        // --- PASSO 1: PROCESSA FILE GIÀ IN PROCESSING (da esecuzioni precedenti fallite) ---
        try {
            if ($disk->exists('Bolle/processing')) {
                $stuckFiles = $disk->files('Bolle/processing');
                $stuckPdfs = array_filter($stuckFiles, fn($f) => str_ends_with(strtolower(basename($f)), '.pdf'));

                if (!empty($stuckPdfs)) {
                    $this->info('[ExtractDdtSpedizioniCommand] Trovati ' . count($stuckPdfs) . ' PDF in processing da riprocessare.');

                    foreach ($stuckPdfs as $file) {
                        $isAlreadyRunning = JobLog::where('job_name', 'ExtractDdtSpedizione')
                            ->where('status', 'running')
                            ->where('payload->path', $file)
                            ->where('started_at', '>=', now()->subMinutes(10))
                            ->exists();

                        if ($isAlreadyRunning) {
                            $this->info("[ExtractDdtSpedizioniCommand] File già in elaborazione, salto: {$file}");
                            continue;
                        }

                        $this->info("Rilevato file residuo da precedente riavvio: {$file}");
                        ExtractDdtSpedizione::dispatch($file);
                        $this->info('[ExtractDdtSpedizioniCommand] Job dispatchato per file residuo: ' . $file);
                    }
                } else {
                    $this->info('[ExtractDdtSpedizioniCommand] Nessun file in processing da riprocessare.');
                }
            } else {
                // Crea cartella processing se non esiste
                $disk->makeDirectory('Bolle/processing');
                $this->info('[ExtractDdtSpedizioniCommand] Cartella processing creata');
            }
        } catch (\Exception $e) {
            $this->error('[ExtractDdtSpedizioniCommand] Errore verifica/creazione cartella DDT/processing: ' . $e->getMessage());
            Log::error("[ExtractDdtSpedizioniCommand] Errore verifica/creazione cartella DDT/processing: " . $e->getMessage());
        }

        // --- PASSO 2: ELABORAZIONE NUOVI FILE ---
        // Prende SOLO i file della cartella principale (escludendo le sottocartelle)
        try {
            $newFiles = $disk->files('Bolle');
            $this->info('[ExtractDdtSpedizioniCommand] Trovati ' . count($newFiles) . ' file nella cartella principale');
        } catch (\Exception $e) {
            $this->error('[ExtractDdtSpedizioniCommand] Errore lettura cartella DDT: ' . $e->getMessage());
            Log::error("[ExtractDdtSpedizioniCommand] Errore lettura cartella DDT: " . $e->getMessage());
            return 1;
        }

        foreach ($newFiles as $file) {
            try {
                // Salta i file nascosti di sistema e le sottocartelle processing / processed / pending_workflow / errori
                if (str_starts_with(basename($file), '.')
                    || str_contains($file, 'processing')
                    || str_contains($file, 'processed')
                    || str_contains($file, 'pending_workflow')
                    || str_contains($file, 'errori')) {
                    continue;
                }

                // Salta i file non PDF
                if (!str_ends_with(strtolower(basename($file)), '.pdf')) {
                    continue;
                }

                $fileName = basename($file);
                $temporaryPath = 'Bolle/processing/' . $fileName;

                // Se esiste già un duplicato in processing
                if ($disk->exists($temporaryPath)) {
                    $pathInfo = pathinfo($fileName);
                    $newFileName = $pathInfo['filename'] . '_' . time() . '.' . ($pathInfo['extension'] ?? 'pdf');
                    $temporaryPath = 'Bolle/processing/' . $newFileName;
                }

                // Sposta il file e lancia il Job
                if ($disk->move($file, $temporaryPath)) {
                    $this->info('[ExtractDdtSpedizioniCommand] File spostato e job dispatchato: ' . $temporaryPath);
                    ExtractDdtSpedizione::dispatch($temporaryPath);
                } else {
                    $this->error('[ExtractDdtSpedizioniCommand] Impossibile spostare file: ' . $file);
                    Log::error("[ExtractDdtSpedizioniCommand] Impossibile spostare file: {$file}");
                }

            } catch (\Exception $e) {
                $this->error("Errore pre-processing file nuovo [{$file}]: " . $e->getMessage());
                Log::error("[ExtractDdtSpedizioniCommand] Errore pre-processing file [{$file}]: " . $e->getMessage());
                continue;
            }
        }

        // --- PASSO 3: RETRY DEI DDT IN ATTESA DI WORKFLOW (pending_workflow) ---
        try {
            $pendingDdt = DdtSpedizione::where('status', 'pending_workflow')
                ->whereNotNull('ns_ovd')
                ->whereNotNull('pdf_path')
                ->get();

            if ($pendingDdt->isNotEmpty()) {
                $this->info('[ExtractDdtSpedizioniCommand] Trovati ' . $pendingDdt->count() . ' DDT in pending_workflow da verificare.');

                foreach ($pendingDdt as $ddt) {
                    $workflow = WfOrder::where('commessa', $ddt->ns_ovd)->where('tipologia', 1)->first();

                    if ($workflow && !empty($workflow->folder_drive)) {
                        if ($disk->exists($ddt->pdf_path)) {
                            $contenuto = $disk->get($ddt->pdf_path);
                            $nomeFile = basename($ddt->pdf_path);
                            $tempPath = storage_path('app/temp_retry_' . uniqid() . '_' . $nomeFile);
                            file_put_contents($tempPath, $contenuto);

                            $filePerDrive = new \Illuminate\Http\File($tempPath);
                            $documentId = GoogleDrive::add_file($workflow->folder_drive, $nomeFile, $filePerDrive, true);

                            $typologieDocuments = $workflow::$typologieDocuments;
                            WfDocument::addDocument(
                                $workflow::$modelName,
                                $workflow->id,
                                $ddt->ns_ovd,
                                $nomeFile,
                                $typologieDocuments['DDT'],
                                $documentId,
                                $workflow->id
                            );

                            @unlink($tempPath);
                            $disk->delete($ddt->pdf_path);

                            $ddt->update([
                                'status' => 'processed',
                                'wf_order_id' => $workflow->id,
                                'pdf_path' => $documentId,
                            ]);

                            $this->info("[ExtractDdtSpedizioniCommand] Commessa {$ddt->ns_ovd} ora presente! DDT {$ddt->numero_ddt} collegato con successo.");
                            Log::info("[ExtractDdtSpedizioniCommand] Commessa {$ddt->ns_ovd} trovata per DDT {$ddt->numero_ddt}, file caricato su Drive ({$documentId}).");
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('[ExtractDdtSpedizioniCommand] Errore retry pending_workflow: ' . $e->getMessage());
            Log::error("[ExtractDdtSpedizioniCommand] Errore retry pending_workflow: " . $e->getMessage());
        }

        // --- PASSO 4: CALCOLO / RICALCOLO COSTI SPEDIZIONE ---
        $this->calcolaCostiSpedizione();

        $this->info('[ExtractDdtSpedizioniCommand] Fine comando');
        return 0;
    }

    /**
     * Rielabora i DDT con vettore mancante o generico ('Vettore', 'Mittente', ecc.):
     * riestrae il nome del vettore dal PDF tramite Gemini e ricalcola il costo.
     */
    protected function rielaboraVettoriMancanti(): int
    {
        $this->info('[ExtractDdtSpedizioniCommand] Avvio rielaborazione vettori non riconosciuti...');

        $ddtsSenzaVettore = DdtSpedizione::where(function ($q) {
            $q->whereNull('vettore')
              ->orWhereIn('vettore', ['Vettore', 'vettore', 'VETTORE', 'Mittente', 'mittente', 'MITTENTE', 'Destinatario', 'destinatario', 'DESTINATARIO']);
        })->whereNotNull('numero_ddt')
          ->get();

        $this->info("Trovati {$ddtsSenzaVettore->count()} DDT da verificare.");

        if ($ddtsSenzaVettore->isEmpty()) {
            return 0;
        }

        $aggiornati = 0;

        foreach ($ddtsSenzaVettore as $ddt) {
            $this->info("Rielaborazione DDT {$ddt->numero_ddt}...");

            try {
                if (ExtractDdtSpedizione::riestraiVettoreDdt($ddt)) {
                    $ddt->refresh();
                    $this->info(" -> Vettore: {$ddt->vettore} | Costo: " . ($ddt->costo_spedizione ? "€ {$ddt->costo_spedizione}" : 'non calcolato'));
                    $aggiornati++;
                } else {
                    $this->warn(" -> Vettore non individuato.");
                }
            } catch (\Exception $e) {
                $this->error(" -> Errore: " . $e->getMessage());
                Log::error("[ExtractDdtSpedizioniCommand] Errore rielaborazione DDT {$ddt->id}: " . $e->getMessage());
            }
        }

        $this->info("Rielaborazione completata: {$aggiornati} DDT aggiornati su {$ddtsSenzaVettore->count()}.");
        Log::info("[ExtractDdtSpedizioniCommand] Rielaborazione vettori: {$aggiornati}/{$ddtsSenzaVettore->count()} aggiornati");

        return 0;
    }

    /**
     * Calcola o ricalcola i costi di spedizione per i DDT che non hanno ancora un costo valorizzato,
     * oppure per tutti se è stata passata l'opzione --recalc-costi.
     */
    protected function calcolaCostiSpedizione(): void
    {
        $recalcAll = (bool) $this->option('recalc-costi');

        $query = DdtSpedizione::query();
        if (!$recalcAll) {
            $query->whereNull('costo_spedizione')
                  ->whereNotNull('peso_lordo_kg')
                  ->whereNotNull('vettore')
                  ->whereNotIn('vettore', ['Vettore', 'vettore', 'VETTORE', 'Mittente', 'Destinatario']);
        }

        $ddts = $query->get();

        if ($ddts->isEmpty()) {
            return;
        }

        $this->info("[ExtractDdtSpedizioniCommand] Calcolo costi per {$ddts->count()} DDT...");
        $calcolati = 0;

        foreach ($ddts as $ddt) {
            $vettore = ExtractDdtSpedizione::normalizzaVettore($ddt->vettore);
            if ($vettore !== $ddt->vettore) {
                $ddt->vettore = $vettore;
                $ddt->vettore_susa = ($vettore === 'SUSA' || str_contains(mb_strtoupper((string) $vettore), 'SUSA'));
                $ddt->vettore_palletways = ($vettore === 'PALLETWAYS' || str_contains(mb_strtoupper((string) $vettore), 'PALLETWAYS'));
            }

            $annoDdt = $ddt->data_ddt ? (int) $ddt->data_ddt->format('Y') : null;

            $calcolo = CalcoloCostoSpedizioneService::calcola(
                $ddt->vettore,
                $ddt->destinazione_indirizzo,
                $ddt->peso_lordo_kg ? (float) $ddt->peso_lordo_kg : null,
                $annoDdt,
                $ddt->n_colli
            );

            $ddt->update([
                'vettore' => $ddt->vettore,
                'vettore_susa' => $ddt->vettore_susa,
                'vettore_palletways' => $ddt->vettore_palletways,
                'destinazione_provincia' => $calcolo['provincia'] ?? $ddt->destinazione_provincia,
                'destinazione_regione' => $calcolo['regione'] ?? $ddt->destinazione_regione,
                'listino_id' => $calcolo['listino_id'] ?? $ddt->listino_id,
                'costo_spedizione' => $calcolo['costo'] ?? $ddt->costo_spedizione,
                'costo_tipo_calcolo' => $calcolo['tipo_calcolo'] ?? $ddt->costo_tipo_calcolo,
                'costo_note' => $calcolo['note'] ?? $ddt->costo_note,
                'costo_dettaglio' => $calcolo['dettaglio'] ?? $ddt->costo_dettaglio,
            ]);

            if ($calcolo['costo'] !== null) {
                $calcolati++;
            }
        }

        $this->info("[ExtractDdtSpedizioniCommand] Costi calcolati con successo: {$calcolati} su {$ddts->count()} DDT.");
        Log::info("[ExtractDdtSpedizioniCommand] Costi spedizione calcolati: {$calcolati}/{$ddts->count()}");
    }
}
