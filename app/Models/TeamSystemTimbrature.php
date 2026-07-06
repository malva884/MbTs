<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamSystemTimbrature extends Model
{
    protected $connection = 'sqlsrv_teamsystem';
    protected $table = 'timbrature';
    protected $guarded = [];

    protected $casts = [
        'data' => 'datetime',
        // ora è un numero intero (es. 60000 = 6:00, 130000 = 13:00)
    ];
    
    /**
     * Converte l'orario numerico in secondi
     * Esempio: 60000 -> 6:00:00 -> 21600 secondi
     *          130000 -> 13:00:00 -> 46800 secondi
     */
    public function getOrarioInSecondsAttribute()
    {
        if (!$this->ora) return 0;
        
        $orarioStr = str_pad($this->ora, 6, '0', STR_PAD_LEFT);
        $ore = (int)substr($orarioStr, 0, 2);
        $minuti = (int)substr($orarioStr, 2, 2);
        $secondi = (int)substr($orarioStr, 4, 2);
        
        return ($ore * 3600) + ($minuti * 60) + $secondi;
    }
}
