<?php

namespace App\Console\Commands;

use App\Http\Controllers\UserController;
use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class DipendentiAssenti extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dipendenti_assenti';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'invio giornalienro assenza dipendenti';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('max_execution_time', -1);
        $result = DB::connection('mysql_old')
            ->table('employees_attendances')
            ->join('employees','employees.id','employees_attendances.employee')
            ->select('nome','cognome','type','start_date')
            ->where('start_date','2025-02-18')
            ->whereIn('centro',['bluecollar_ofc','bluecollar_cc'])
            ->get();

        $result_due = DB::table('hr_hours_requested_details')
            ->join('hr_hours_requesteds','hr_hours_requesteds.id','hr_hours_requested_details.richiesta_id')
            ->select('dipendente_nome','dipendente_cognome','data','ora_inizio','ora_fine')
            ->where('hr_hours_requested_details.tipologia',5)
            ->where('confermato', true)
            ->where('data','2025-02-18')
            ->whereIn('hr_hours_requesteds.centro_di_costo',['bluecollar_ofc','bluecollar_cc'])
            ->get();

        $spreadsheet  = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();

        $activeWorksheet->setCellValue('A1', 'Cognome');
        $activeWorksheet->setCellValue('B1', 'Nome');
        $activeWorksheet->setCellValue('C1', 'Tipologia');
        $activeWorksheet->setCellValue('D1', 'Data');
        $activeWorksheet->setCellValue('E1', 'Dalle');
        $activeWorksheet->setCellValue('F1', 'Alle');
        $i = 2;
        foreach ($result_due as $row){
            $type = 'Permesso';
            $activeWorksheet->setCellValue('A'.$i, $row->dipendente_cognome);
            $activeWorksheet->setCellValue('B'.$i, $row->dipendente_nome);
            $activeWorksheet->setCellValue('C'.$i, $type);
            $activeWorksheet->setCellValue('D'.$i, $row->data);
            $activeWorksheet->setCellValue('E'.$i, $row->ora_inizio);
            $activeWorksheet->setCellValue('F'.$i, $row->ora_fine);
            $i++;
        }

        foreach ($result as $row){
            $type = '';
            switch ($row->type) {
                case 1:
                    $type = 'Ferie';
                    break;
                case 2:
                    $type = 'Malattia';
                    break;
                case 3:
                    $type = 'Assenza';
                    break;
                case 4:
                    $type = '104';
                    break;

            }
            $activeWorksheet->setCellValue('A'.$i, $row->cognome);
            $activeWorksheet->setCellValue('B'.$i, $row->nome);
            $activeWorksheet->setCellValue('C'.$i, $type);
            $activeWorksheet->setCellValue('D'.$i, $row->start_date);
            $i++;
        }

        $writer = new Xlsx($spreadsheet);
        $path_file = '/public/file/';
        $file_dir = dirname(__DIR__, 3).$path_file;
        if (!file_exists($file_dir)) {
            if (!mkdir($file_dir, 0777, true) && !is_dir($path_file)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path_file));
            }
        }
        $file = $file_dir.'dipendenti.xlsx';
        $writer->save($file);

        $users = Utility::users_notify(['pr_assenza_dipendenti']);

        Mail::send('emails/email_empty', [], function ($message) use($file, $users){
            $message
                ->to($users)
                ->subject('Assenze Dipendenti Del '. date('Y-m-d'));

            $message->attach( $file);
        });
        File::delete($file);
    }
}
