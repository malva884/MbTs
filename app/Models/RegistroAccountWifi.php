<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

        return $obj;
    }
}
