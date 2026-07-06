<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamSystemGiustificazioni extends Model
{
    protected $connection = 'sqlsrv_teamsystem';
    protected $table = 'giustificazioni';
    protected $guarded = [];

    protected $casts = [
        'inizio' => 'datetime',
        'fine' => 'datetime',
        'data_richiesta' => 'datetime',
        'ora_richiesta' => 'datetime',
        'data_autorizzata' => 'datetime',
        'ora_autorizzata' => 'datetime',
        'data_modifica' => 'datetime',
        'data_aut_missione' => 'datetime',
        'data_competenza' => 'datetime',
        'data_anticipo_consegnato' => 'datetime',
        'data_biglietto_consegnato' => 'datetime',
        'data_pcarico' => 'datetime',
    ];
}
