<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','tipo','titolo','target','valore','data_riferimento','user','id_riferimento'];

    static $tipi_target = [
        1   =>  'Fatturato',
        2   =>  'Spedito',
        3   =>  'Produzione',
    ];
}
