<?php

namespace App\Models;

use App\Jobs\NotificaCreazioneWifi;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegistroAccountWifi extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id','register_id','nome','email','username','password', 'azienda', 'data_inizio', 'data_fine','stato','rete','user'
    ];

    public $status =[
        0 =>'Da Creare',
        1 =>'Crato',
    ];

    public static function create($nome, $email, $username, $password, $azienda, $dataInizio, $dataFine, $user , $register_id = null)
    {

        $obj = new RegistroAccountWifi();
        $obj->nome = $nome;
        $obj->email = $email;
        $obj->user = $user;
        $obj->register_id = $register_id;
        $obj->username = $username;
        $obj->password = $password;
        $obj->azienda = $azienda;
        $obj->data_inizio = $dataInizio;
        $obj->data_fine = $dataFine;
        $obj->stato = false;
        $obj->save();

        $users = Utility::users_notify(['richiesta_wifi']);
        $accounts = [$obj];
        Mail::send('emails/email_richiesta_wifi', compact('accounts'), function ($message) use ($users) {
            $message
                ->to($users)
                ->subject('Richiesta Credenziala Wifi');
        });
        return $obj;
    }

    public static function edit($dataInizio, $dataFine, $register_id = null)
    {

        $obj = RegistroAccountWifi::where('register_id',$register_id)->orderBy('created_at','desc')->first();
        $obj->data_inizio = $dataInizio;
        $obj->data_fine = $dataFine;
        $obj->stato = false;
        $obj->save();

        $users = Utility::users_notify(['richiesta_wifi']);
        $accounts = [$obj];
        Mail::send('emails/email_richiesta_wifi', compact('accounts'), function ($message) use ($users) {
            $message
                ->to($users)
                ->subject('Richiesta Modifica Wifi');
        });
        return $obj;
    }
}
