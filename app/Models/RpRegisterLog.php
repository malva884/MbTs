<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RpRegisterLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id','evento_id','cod_riferimento','cod_tessera','user','data_prevista','data_scadenza',
        'attivo','email','nome','wifi','password_wifi','username_wifi','notifica_inviata',
    ];
}
