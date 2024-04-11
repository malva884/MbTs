<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QtCategorie extends Model
{
    use HasFactory;

    protected $fillable = [
        'id', 'categoria', 'descrizione','valore','disabled', 'moduli','id_drive'
    ];
}
