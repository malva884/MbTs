<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RpRegisterLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id','evento_id','cod_riferimento','cod_tessera','user','data_prevista','data_scadenza',
        'attivo','email','nome','azienda','wifi','password_wifi','username_wifi','notifica_inviata',
        'informativa'
    ];

    public static function create($request)
    {
        $obj = new RpRegisterLog();
        $obj->cod_riferimento = Str::uuid();
        $obj->data_prevista = $request->get('data_prevista').' 07:00:00';
        $obj->data_scadenza = $request->get('data_scadenza').' 23:59:59';
        $obj->wifi = ($request['wifi'] == 'true' ? true:false );
        $obj->informativa = ($request['informativa'] == 'true' ? true:false );
        if($obj->wifi){
            $username = explode("@", $obj->email);
            $obj->username_wifi = $username[0];
            $obj->password_wifi = Str::password(8, true, true, false, false);
        }

        $obj->save();

        if($obj->wifi)
            RegistroAccountWifi::create($obj->nome, $obj->email, $obj->username_wifi, $obj->password_wifi, $obj->azienda,  $obj->data_prevista, $obj->data_scadenza, $obj->user, $obj->id);

    }

    public static function inviaNotifica($id)
    {
        $users = DB::table('rp_register_notifications')->select('rp_register_logs.nome','rp_register_logs.azienda','users.email')
            ->join('users','users.id','rp_register_notifications.user')
            ->join('rp_register_logs','rp_register_logs.id','rp_register_notifications.register_id')
            ->where('register_id',$id)
            ->where('rp_register_notifications.attivo',true)
            ->groupBy('rp_register_logs.nome','rp_register_logs.azienda','users.email')
            ->get();
        foreach ($users as $user){
            $info = [
                'nome' => $user->nome,
                'azienda' => $user->azienda,
                'email' => $user->email,
            ];
            Mail::send('emails/email_visitatore_arrivato', compact('info'), function ($message) use($info) {
                $message
                    ->to($info['email'])
                    ->subject('Notifica Visitatore.');
            });
        }

    }
}
