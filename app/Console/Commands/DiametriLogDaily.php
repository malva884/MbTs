<?php

namespace App\Console\Commands;

use App\Models\Utility;
use App\Services\GoogleDrive;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DiametriLogDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:log-diametri-daily {--date= : Data nel formato YYYY-MM-DD} {--machinery= : ID, name_gp o nome macchina}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Log Diametri Macchina.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('max_execution_time', -1);

        $targetDate = $this->option('date')
            ? Carbon::parse($this->option('date'))->format('Y-m-d')
            : Carbon::yesterday('Europe/Amsterdam')->format('Y-m-d');

        $year = Carbon::parse($targetDate)->format('Y');
        $startOfDay = "{$targetDate} 00:00:00";
        $endOfDay = "{$targetDate} 23:59:59.997";

        $rootFolderId = config('services.google.diametri_folder_id', env('GOOGLE_DIAMETRI_FOLDER_ID', '0AHg0OVQB2sJiUk9PVA'));
        $pathFolder = GoogleDrive::add_folder([$rootFolderId], $year, 'google', true);
        if (!$pathFolder) {
            $this->error("Impossibile accedere alla cartella anno {$year} su Google Drive.");
            Log::error("DiametriLogDaily: impossibile accedere alla cartella anno {$year} su Google Drive.");
            return Command::FAILURE;
        }

        $query = DB::table('machineries')->where('report_gp', true);
        if ($machineryOption = $this->option('machinery')) {
            $query->where(function ($q) use ($machineryOption) {
                $q->where('id', $machineryOption)
                    ->orWhere('name_gp', $machineryOption)
                    ->orWhere('nome', $machineryOption);
            });
        }
        $macchine = $query->get();

        foreach ($macchine as $macchina) {
            $tempFilePath = null;
            try {
                if (empty($macchina->name_gp)) {
                    $this->warn("Macchina {$macchina->nome} priva di name_gp, saltata.");
                    continue;
                }

                $path = GoogleDrive::add_folder([$pathFolder], $macchina->nome, 'google', true);
                if (!$path) {
                    $this->error("Impossibile creare o recuperare la cartella per {$macchina->nome}");
                    Log::error("DiametriLogDaily: errore cartella Google Drive per {$macchina->nome}");
                    continue;
                }

                $cursor = DB::connection('sqlsrv_gp')->table('STL_DIAMETRI_V')
                    ->select(['Diametro', 'Diam_X', 'Diam_Y', 'DataEvento'])
                    ->where('IDRisorsa', $macchina->name_gp)
                    ->where('DataEvento', '>=', $startOfDay)
                    ->where('DataEvento', '<=', $endOfDay)
                    ->orderBy('DataEvento', 'desc')
                    ->cursor();

                $tempFilePath = storage_path("app/google/temp_diametri_{$macchina->id}_{$targetDate}.csv");
                if (!file_exists(dirname($tempFilePath))) {
                    mkdir(dirname($tempFilePath), 0755, true);
                }

                $handle = fopen($tempFilePath, 'w+');
                fputcsv($handle, ['Diametro', 'Diam_X', 'Diam_Y', 'DataEvento']);

                $rowCount = 0;
                foreach ($cursor as $obj) {
                    fputcsv($handle, [$obj->Diametro, $obj->Diam_X, $obj->Diam_Y, $obj->DataEvento]);
                    $rowCount++;
                }
                fclose($handle);

                if ($rowCount === 0) {
                    $this->warn("Nessun dato per {$macchina->nome} in data {$targetDate}");
                    Log::info("DiametriLogDaily: nessun dato per {$macchina->nome} in data {$targetDate}");
                    @unlink($tempFilePath);
                    continue;
                }

                $fileName = $targetDate . '.csv';
                $existingFileId = GoogleDrive::search($path, 'google', 'file', $fileName);
                if ($existingFileId) {
                    GoogleDrive::delated($existingFileId, 'google');
                }

                GoogleDrive::add_file($path, $fileName, $tempFilePath);
                $this->info("Esportati {$rowCount} record per {$macchina->nome} ({$targetDate})");
                Log::info("DiametriLogDaily: esportati {$rowCount} record per {$macchina->nome} in {$fileName}");
            } catch (\Throwable $e) {
                $this->error("Errore per {$macchina->nome}: " . $e->getMessage());
                Log::error("DiametriLogDaily errore per {$macchina->nome}: " . $e->getMessage(), [
                    'exception' => $e,
                    'machinery_id' => $macchina->id ?? null,
                    'name_gp' => $macchina->name_gp ?? null,
                    'date' => $targetDate,
                ]);
            } finally {
                if ($tempFilePath && file_exists($tempFilePath)) {
                    @unlink($tempFilePath);
                }
            }
        }

        return Command::SUCCESS;
    }
}
