<?php

namespace App\Jobs;

use App\Models\RpRegisterLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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
            $image = $this->generateQrPng($obj->cod_riferimento);
            $output_file = 'qrcode-' . time() . '.png';
            $info = [
                'nome' => $obj->nome,
                'email' => $obj->email,
                'code' => $obj->cod_riferimento,
                'qrcode' => $output_file,
                'data' => Carbon::parse($obj->data_prevista)->locale('it')->isoFormat('dddd D MMMM YYYY'),
            ];
            if (!Storage::disk('ftp')->put("qrcode_portale/" . $output_file, $image)) {
                Log::warning('RegisterNotifiche: upload FTP del QR fallito: ' . $output_file);
            }

            Mail::send('emails/email_visitatore', compact('info','output_file','image'), function ($message) use($info) {
                $message
                    ->to($info['email'])
                    ->subject('Promemoria Appuntamento Metallurgica Bresciana');
            });
            $obj->notifica_inviata = true;
            $obj->save();
        }
    }

    /**
     * Genera il QR code in PNG usando GD, senza richiedere l'estensione imagick.
     */
    private function generateQrPng(string $content, int $size = 300, int $margin = 1): string
    {
        $qrCode = Encoder::encode($content, ErrorCorrectionLevel::H());
        $matrix = $qrCode->getMatrix();

        $modules = $matrix->getWidth() + $margin * 2;
        $scale = (int)max(1, ceil($size / $modules));
        $imageSize = $modules * $scale;

        $image = imagecreatetruecolor($imageSize, $imageSize);
        $background = imagecolorallocate($image, 0, 255, 255);
        $foreground = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $background);

        for ($y = 0; $y < $matrix->getHeight(); $y++) {
            for ($x = 0; $x < $matrix->getWidth(); $x++) {
                if ($matrix->get($x, $y) === 1) {
                    imagefilledrectangle(
                        $image,
                        ($x + $margin) * $scale,
                        ($y + $margin) * $scale,
                        ($x + $margin + 1) * $scale - 1,
                        ($y + $margin + 1) * $scale - 1,
                        $foreground
                    );
                }
            }
        }

        ob_start();
        imagepng($image);
        $png = (string)ob_get_clean();
        imagedestroy($image);

        return $png;
    }
}
