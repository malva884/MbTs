<?php

namespace App\Console\Commands;

use App\Http\Controllers\UserController;
use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class MensaWeek extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mensa_week';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'invio prenotazioni mensa settimana.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $g = new UserController();
        $g->mensa();

        stream_context_set_default(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
        $day_of_week = date("N") - 5;
        $primo_giorno =  strtotime("-$day_of_week days");
        $path = 'https://app.metallurgicabresciana.it/turni/mb/menza/api/get.php?';
        //$path.= 'time='.date('Y-m-d',$primo_giorno);
        $path.= 'time='.date('Y-m-d');
        $getMovieList = file_get_contents($path);
        $result = json_decode($getMovieList);

        $spreadsheet  = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();


        $activeWorksheet->setCellValue('A1', 'Pientanza');
        $activeWorksheet->setCellValue('B1', 'Matricola');
        $activeWorksheet->setCellValue('C1', 'Dipendente');
        $activeWorksheet->setCellValue('D1', 'Data');
        $activeWorksheet->setCellValue('E1', 'Costo');
        $i = 2;
        foreach ((array)$result->list as $row){
            $activeWorksheet->setCellValue('A'.$i, $row[0]);
            $activeWorksheet->setCellValue('B'.$i, $row[1]);
            $activeWorksheet->setCellValue('C'.$i, $row[2]);
            $activeWorksheet->setCellValue('D'.$i, $row[3]);
            $activeWorksheet->setCellValue('E'.$i, $row[4]);
            $i++;
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save('file/'.date('Y_m_d').'.xlsx');

        $file = public_path('file/'.date('Y_m_d').'.xlsx');

        $users = Utility::users_notify(['mensa_week']);

        Mail::send('emails/email_mensa', [], function ($message) use($file, $primo_giorno, $users){
            $message
                ->to('gregorio.grande@stl.tech')
                ->subject('Mensa Del '. date('Y-m-d'));

            $message->attach( $file);
        });
        File::delete($file);
    }
}
