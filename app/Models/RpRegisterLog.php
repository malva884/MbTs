<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RpRegisterLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id','evento_id','cod_riferimento','cod_tessera','user','data_prevista','data_scadenza',
        'attivo','email','nome','azienda','wifi','password_wifi','username_wifi','notifica_inviata',
    ];

    public static function inviaNotifica($id)
    {
        $users = DB::table('rp_register_notifications')->select('rp_register_notifications.nome','users.email')
            ->join('users','users.id','rp_register_notifications.user')
            ->where('register_id',$id)
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
