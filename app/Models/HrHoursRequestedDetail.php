<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrHoursRequestedDetail extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id', 'richiesta_id', 'bacheca_id','bacheca_dipendente_id','dipendente_matricola', 'data', 'tipologia','confermato',
        'ore_richieste','ora_inizio','ora_fine'
    ];
}
