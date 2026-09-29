<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListinoSpedizione extends Model
{
    protected $table = 'listini_spedizioni';

    protected $fillable = [
        'vettore',
        'descrizione',
        'tipo',
        'anno',
        'valido_da',
        'valido_a',
        'file_name',
        'status',
        'error_message',
        'attivo',
        'raw_response',
    ];

    protected $casts = [
        'attivo' => 'boolean',
        'valido_da' => 'date',
        'valido_a' => 'date',
        'raw_response' => 'array',
    ];

    public const TIPO_PALLET = 'pallet';
    public const TIPO_PESO = 'peso';

    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_ERROR = 'error';

    public function voci(): HasMany
    {
        return $this->hasMany(ListinoSpedizioneVoce::class, 'listino_id');
    }
}
