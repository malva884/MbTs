<?php

namespace App\Models;

use App\Print\TemplateZpl;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RpRegisterActivity extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id','rp_register_id','cod_riferimento','azione','data_azione','presente'
    ];

    static function store($request)
    {

        $obj = new RpRegisterActivity();
        $obj->rp_register_id = $request->id;
        $obj->cod_riferimento = $request->cod_riferimento;
        $obj->data_azione = date('Y-m-d H:i:s');
        $obj->azione = ($request->entrata == true ? 'Entrata':'Uscita');
        $obj->presente = ($request->entrata == true ? True:False);
        $obj->save();

        if($obj->azione == 'Uscita')
            DB::table('rp_register_activities')
                ->where('rp_register_id',$obj->rp_register_id)
                ->update(['presente' => False]);

        $registerLog = RpRegisterLog::find($request->id);
        // se sta entrando e il cod_tessera è vuoto creo il cod_tessera.
        if($request->entrata == true && !$registerLog->cod_tessera){
            $registerLog->cod_tessera = Str::uuid();
            $registerLog->save();

        }

        if($request->entrata == true){
            // funziona che invia le notifiche di arrivo del visitatore a gli utenti interni
            RpRegisterLog::inviaNotifica($registerLog->id);
        }

        if($request->entrata == true && $registerLog->cod_riferimento == $request->cod_tessera){
            try {
                $info = [
                    'Id' => $registerLog->cod_tessera,
                    'Visitatore' => $registerLog->nome,
                    'Azienda' => $registerLog->azienda,
                    'Username' => $registerLog->username_wifi,
                    'Password' => $registerLog->password_wifi,
                    'Scadenza' => $registerLog->data_scadenza,
                    'Ip_Printer' => $request->ip_stampante,
                ];
                //TemplateZpl::printReception($info);
            }
            catch (\Exception $e) {
                $obj = DB::table('rp_totems')->select('*')
                    ->where('ip_stampante',$request->ip_stampante)
                    ->first();

                $content = 'Totem: '.$obj->nome.' Errore Stampante.';
                Mail::send('emails/email_white', compact('content'), function ($message) use($obj) {
                    $message
                        ->to('itsupport.metallurgica@stl.tech')
                        ->subject('Errore Totem. '.$obj->nome);
                });

            }
        }

    }
}
