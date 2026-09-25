<?php

namespace App\Console\Commands;

use App\Http\Controllers\QtConformitaController;
use App\Models\QtConformita;
use App\Services\GoogleDrive;
use App\Services\SettingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SyncPendingNcDrive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nc:sync-drive {--all : Sincronizza tutte le NC dell\'anno corrente senza cartella valida} {--year= : Anno specifico da elaborare (default: anno corrente)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea le cartelle Google Drive mancanti per le Non Conformità dell\'anno corrente e carica eventuali allegati salvati in locale';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        ini_set('max_execution_time', -1);

        $targetYear = $this->option('year') ? (int) $this->option('year') : (int) date('Y');

        $settingService = new SettingService();
        $ncGiornaliereFolderId = $settingService->get('google_drive_nc_giornaliere_folder_id');

        if (empty($ncGiornaliereFolderId)) {
            $this->error("Setting 'google_drive_nc_giornaliere_folder_id' non configurato!");
            Log::channel('stderr')->error("SyncPendingNcDrive: setting 'google_drive_nc_giornaliere_folder_id' non configurato");
            return 1;
        }

        $query = QtConformita::query()->where('anno', $targetYear);

        // Raccogli gli ID delle NC che hanno allegati locali pendenti
        $pendingStoragePath = storage_path('app/nc_pending');
        $pendingNcIds = [];
        if (File::isDirectory($pendingStoragePath)) {
            foreach (File::directories($pendingStoragePath) as $dir) {
                $pendingNcIds[] = basename($dir);
            }
        }

        if ($this->option('all')) {
            $this->info("Scansione di TUTTE le NC dell'anno {$targetYear} senza cartella Drive valida...");
            $query->where(function ($q) use ($pendingNcIds) {
                $q->whereNull('google_drive_id')
                  ->orWhere('google_drive_id', '0')
                  ->orWhere('google_drive_id', '')
                  ->orWhereRaw('LEN(google_drive_id) < 10');

                if (!empty($pendingNcIds)) {
                    $q->orWhereIn('id', $pendingNcIds);
                }
            });
        } else {
            $this->info("Scansione NC anno {$targetYear} recenti (ultimi 7 giorni) o con allegati pendenti...");
            $query->where(function ($q) use ($pendingNcIds) {
                $q->where(function ($sub) {
                    $sub->where('created_at', '>=', now()->subDays(7))
                        ->where(function ($invalid) {
                            $invalid->whereNull('google_drive_id')
                                    ->orWhere('google_drive_id', '0')
                                    ->orWhere('google_drive_id', '')
                                    ->orWhereRaw('LEN(google_drive_id) < 10');
                        });
                });

                if (!empty($pendingNcIds)) {
                    $q->orWhereIn('id', $pendingNcIds);
                }
            });
        }

        $ncs = $query->orderBy('created_at', 'desc')->get();
        $total = $ncs->count();

        $this->info("Trovate {$total} Non Conformità da verificare/sincronizzare.");
        if ($total === 0) {
            return 0;
        }

        $successCount = 0;
        $failCount = 0;
        $controller = new QtConformitaController();

        foreach ($ncs as $nc) {
            $this->line("Elaborazione NC {$nc->numero} ({$nc->ol}-{$nc->bobina}) [{$nc->id}]...");

            // 1. Assicura che la cartella Drive esista
            $folderId = $nc->google_drive_id;
            if (empty($folderId) || $folderId === '0' || strlen($folderId) < 10) {
                $folderId = GoogleDrive::add_folder(
                    [$ncGiornaliereFolderId],
                    $nc->ol . '-' . $nc->bobina,
                    'google',
                    false
                );

                if (!is_string($folderId) || strlen($folderId) < 10) {
                    $this->error(" -> Fallita creazione cartella Drive per NC {$nc->numero}");
                    $failCount++;
                    continue;
                }

                $nc->google_drive_id = $folderId;
                $nc->save();
                $this->info(" -> Cartella Drive creata/assegnata: {$folderId}");
            }

            // 2. Controlla se esistono file locali pendenti da caricare
            $ncDir = "{$pendingStoragePath}/{$nc->id}";
            if (File::isDirectory($ncDir) && File::exists("{$ncDir}/meta.json") && File::exists("{$ncDir}/file.data")) {
                try {
                    $meta = json_decode(File::get("{$ncDir}/meta.json"), true);
                    $fileData = File::get("{$ncDir}/file.data");
                    $ext = $meta['extension'] ?? 'jpg';
                    $filename = $meta['filename'] ?? 'allegato';

                    $this->line(" -> Caricamento allegato pendente '{$filename}.{$ext}'...");
                    $uploaded = $controller->saveFile($fileData, $folderId, $ext, $filename);

                    if ($uploaded) {
                        File::deleteDirectory($ncDir);
                        $this->info(" -> Allegato caricato e rimosso dalla memoria locale.");
                    } else {
                        $this->warn(" -> Upload allegato fallito, mantenuto in locale per il prossimo tentativo.");
                    }
                } catch (\Exception $e) {
                    $this->error(" -> Errore durante upload allegato: " . $e->getMessage());
                    Log::channel('stderr')->error("SyncPendingNcDrive upload error per NC {$nc->id}: " . $e->getMessage());
                }
            }

            $successCount++;
        }

        $this->info("Completato: {$successCount} riuscite, {$failCount} fallite su {$total} totali.");
        Log::channel('stderr')->info("SyncPendingNcDrive completato: {$successCount} riuscite, {$failCount} fallite su {$total} totali.");

        return $failCount === 0 ? 0 : 1;
    }
}
