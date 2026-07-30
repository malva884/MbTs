<?php

namespace App\Console\Commands;

use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class DipendentiAssentiNewSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dipendenti_assenti_new_system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invio giornaliero assenza dipendenti dal nuovo sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('max_execution_time', -1);
        
        // Esegui sincronizzazione dal vecchio sistema per la data odierna
        $this->info('Esecuzione sincronizzazione presenze dal vecchio sistema...');
        $this->call('sync:presenze-from-old', ['--date' => date('Y-m-d')]);
        
        // Recupera tutte le assenze dal nuovo sistema per oggi
        // Tipologie: 1=Ferie, 2=104, 3=Malattia, 4=Assenza, 5=Permesso
        // Esclude annullamenti: 101, 102, 105
        $result = DB::table('hr_hours_requested_details')
            ->join('hr_hours_requesteds', 'hr_hours_requesteds.id', 'hr_hours_requested_details.richiesta_id')
            ->select(
                'hr_hours_requesteds.dipendente_nome',
                'hr_hours_requesteds.dipendente_cognome',
                'hr_hours_requested_details.data',
                'hr_hours_requested_details.ora_inizio',
                'hr_hours_requested_details.ora_fine',
                'hr_hours_requested_details.tipologia'
            )
            ->whereIn('hr_hours_requested_details.tipologia', [1, 2, 3, 4, 5])
            ->whereIn('hr_hours_requesteds.centro_di_costo', ['bluecollar_ofc', 'bluecollar_cc'])
            ->whereNotIn('hr_hours_requested_details.bacheca_id', function($query) {
                $query->select('bacheca_id')
                    ->from('hr_hours_requested_details')
                    ->whereIn('hr_hours_requested_details.tipologia', [101, 102, 105])
                    ->where('confermato', true)
                    ->where('data', date('Y-m-d'));
            })
            ->where('confermato', true)
            ->where('data', date('Y-m-d'))
            ->get();

        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();

        $activeWorksheet->setCellValue('A1', 'Cognome');
        $activeWorksheet->setCellValue('B1', 'Nome');
        $activeWorksheet->setCellValue('C1', 'Tipologia');
        $activeWorksheet->setCellValue('D1', 'Data');
        $activeWorksheet->setCellValue('E1', 'Dalle');
        $activeWorksheet->setCellValue('F1', 'Alle');
        
        $i = 2;
        foreach ($result as $row) {
            $type = '';
            switch ($row->tipologia) {
                case 1:
                    $type = 'Ferie';
                    break;
                case 2:
                    $type = '104';
                    break;
                case 3:
                    $type = 'Malattia';
                    break;
                case 4:
                    $type = 'Assenza';
                    break;
                case 5:
                    $type = 'Permesso';
                    break;
            }
            
            $activeWorksheet->setCellValue('A' . $i, $row->dipendente_cognome);
            $activeWorksheet->setCellValue('B' . $i, $row->dipendente_nome);
            $activeWorksheet->setCellValue('C' . $i, $type);
            $activeWorksheet->setCellValue('D' . $i, $row->data);
            $activeWorksheet->setCellValue('E' . $i, $row->ora_inizio);
            $activeWorksheet->setCellValue('F' . $i, $row->ora_fine);
            $i++;
        }

        $writer = new Xlsx($spreadsheet);
        $path_file = '/public/file/';
        $file_dir = dirname(__DIR__, 3) . $path_file;
        
        if (!file_exists($file_dir)) {
            if (!mkdir($file_dir, 0777, true) && !is_dir($path_file)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path_file));
            }
        }
        
        $file = $file_dir . 'dipendenti_assenti.xlsx';
        $writer->save($file);

        $users = Utility::users_notify(['pr_assenza_dipendenti_new']);

        Mail::send('emails/email_assenze_dipendenti', [], function ($message) use ($file, $users) {
            $message
                ->to($users)
                ->subject('Assenze Dipendenti Del ' . date('Y-m-d'));

            $message->attach($file);
        });
        
        File::delete($file);
        
        $this->info('Email inviata con successo.');
    }
}
