<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrHoursRequested extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id', 'bacheca_id', 'bacheca_dipendente_id','dipendente_matricola','stato', 'data_richiesta', 'note','tipologia',
        'centro_di_costo','dipendente_cognome','dipendente_matricola'
    ];
}
