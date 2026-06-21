<?php

namespace App\Console\Commands;

use App\Models\Utility;
use App\Services\GoogleDrive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DiametriLogDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:log-diametri-daily';

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

        $macchine = DB::table('machineries')
            ->where('report_gp', true)
            ->get();

        $pathFolder = GoogleDrive::add_folder(['0AHg0OVQB2sJiUk9PVA'], date('Y'), 'google', true);
        $date = date('Y-m-d', strtotime(date('Y-m-d') . " -1 days"));

        foreach ($macchine as $macchina) {
			$path = GoogleDrive::add_folder([$pathFolder], $macchina->nome, 'google', true);
            $objs = DB::connection('sqlsrv_gp')->table('STL_DIAMETRI_V')
                ->where('IDRisorsa',$macchina->name_gp)
                ->whereDate('DataEvento', $date)
                ->orderBy('DataEvento', 'desc')
                ->get();
            if (!$objs->count()) {
                // notifica dati assenti
            } else {
                $credentialsPath = storage_path('app/google/' . $date . '.csv');
                if (!file_exists(dirname($credentialsPath)))
                    mkdir(dirname($credentialsPath), 0700, true);

                $handle = fopen($credentialsPath, 'w+');
                fputcsv($handle, ['Diametro', 'Diam_X', 'Diam_Y', 'DataEvento']); // Add more headers as needed
                foreach ($objs as $obj)
                    fputcsv($handle, [$obj->Diametro, $obj->Diam_X, $obj->Diam_Y, $obj->DataEvento]); // Add more fields as needed

                fclose($handle);
                GoogleDrive::add_file($path, $date . '.csv', $credentialsPath);
                @unlink($credentialsPath);
            }

        }

    }
}
