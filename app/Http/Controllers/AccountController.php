<?php

namespace App\Http\Controllers;

use App\Models\User; #
use App\Models\Utility;
use Illuminate\Http\Request; #
use Illuminate\Support\Facades\Auth; #
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

#

class AccountController extends Controller
{
    public function account()
    {
        return response()->json(Auth::user());
    }

    public function update(Request $request)
    {

        $input = $request->except(['nome','cognome']);
        $input['userId'] = $request->id;
        $input['nome'] = ucfirst(strtolower($request->nome));
        $input['cognome'] = ucfirst(strtolower($request->cognome));
        $input['full_name'] = $input['nome'].' '.$input['cognome'];
        User::find(Auth::id())->update($input);

        $message = 'Messaggi.Dati-Salvati.';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
            ]
        );
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();

        if (!Hash::check($request->oldPassword,$user->password)){
            $message = 'Messaggi.Password-Corrente-Non-Valida';
            $success = false;
        }
        else
        {
            $user->password = Hash::make($request->newPassword);
            $user->password_changed_at = date('Y-m-d H:i:s');
            $user->save();
            $message = 'Messaggi.Password-Cambiata-Correttamente';
            $success = true;
        }

        return response()->json(
            [
                'success' => $success,
                'message' => $message ,
            ]
        );

    }


    public function test()
    {
        stream_context_set_default(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
        $path_file = '/public/file/';
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

        $file_dir = dirname(__DIR__, 3).$path_file;
        if (!file_exists($file_dir)) {
            if (!mkdir($file_dir, 0777, true) && !is_dir($path_file)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path_file));
            }
        }
        $file = $file_dir.date('Y_m_d_').'.xlsx';
        $writer->save($file);

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
