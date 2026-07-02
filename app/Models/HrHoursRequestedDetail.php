<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrHoursRequestedDetail extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id', 'richiesta_id', 'bacheca_id','bacheca_dipendente_id','dipendente_matricola', 'data', 'tipologia','confermato',
        'ore_richieste','ora_inizio','ora_fine'
    ];

    public function richiesta(): BelongsTo
    {
        return $this->belongsTo(HrHoursRequested::class, 'richiesta_id');
    }
}
