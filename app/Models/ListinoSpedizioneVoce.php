<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListinoSpedizioneVoce extends Model
{
    protected $table = 'listini_spedizioni_voci';

    protected $fillable = [
        'listino_id',
        'regione',
        'provincia',
        'hub',
        'servizio',
        'fascia',
        'peso_da',
        'peso_a',
        'prezzo',
        'tipo_voce',
    ];

    protected $casts = [
        'peso_da' => 'decimal:2',
        'peso_a' => 'decimal:2',
        'prezzo' => 'decimal:3',
    ];

    public const TIPO_VOCE_TARIFFA = 'tariffa';
    public const TIPO_VOCE_INOLTRO = 'inoltro';

    public function listino(): BelongsTo
    {
        return $this->belongsTo(ListinoSpedizione::class, 'listino_id');
    }
}
