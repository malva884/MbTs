<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QtFai extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'id', 'ol', 'data_creazione','data_chiusura','user', 'numero_fai', 'descrizione', 'cod_cavo','cod_materiale','esito','path_drive','risultato','anno','num','created_at'
    ];
}
