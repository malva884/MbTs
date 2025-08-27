<?php

namespace App\Jobs;

use App\Models\RpRegisterLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class RegisterNotifiche implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $objs = RpRegisterLog::all()->where('notifica_inviata',false);
        foreach ($objs as $obj){
            $image = QrCode::format('png')
                ->backgroundColor(0,255,255)
                ->color(0, 0, 0)
                ->margin(1)
                ->size(300)->errorCorrection('H')
                ->generate($obj->cod_riferimento);
            $output_file = '/qrcode-' . time() . '.png';
            $info = [
                'nome' => $obj->nome,
                'email' => $obj->email,
                'code' => $obj->cod_riferimento,
                'qrcode' => $output_file,
            ];
            Storage::disk('ftp')->put("qrcode_portale/" . $output_file, $image);

            Mail::send('emails/email_visitatore', compact('info','output_file'), function ($message) use($info) {
                $message
                    ->to($info['email'])
                    ->subject('Promemoria Appuntamento Metallurgica Bresciana');
            });
            $obj->notifica_inviata = true;
            $obj->save();
        }
    }
}
