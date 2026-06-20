<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class QtFai extends Model
{
    use HasFactory,HasUuids;

    // Definisco esplicitamente la tabella associata
    protected $table = 'qt_fais';

    /**
     * I campi che possono essere massivamente assegnati.
     * Allineati alle colonne corte della migrazione.
     */
    protected $fillable = [
        'codice',
        'data_inizio',
        'descrizione',
        'esito_fattibilita',
        'soggetto',
        'articolo',
        'specifica',
        'ol',
        'prove',
        'esito',
        'drive_id',
        'specifica_id',
    ];

    /**
     * Cast automatico dei tipi di dato.
     */
    protected $casts = [
        'data_inizio' => 'date:Y-m-d',
        'prove'       => 'array', // Converte automaticamente il JSON del DB in array in PHP e viceversa
    ];

}
