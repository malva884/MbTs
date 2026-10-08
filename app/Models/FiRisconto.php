<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiRisconto extends Model
{
    use HasFactory;

    protected $table = 'fi_riscontis';

    protected $fillable = [
        'tipo',
        'descrizione',
        'importo_totale',
        'data_inizio',
        'data_fine',
        'data_chiusura_bilancio',
        'giorni_totali',
        'giorni_competenza',
        'giorni_futuri',
        'quota_giornaliera',
        'quota_mensile',
        'quota_competenza',
        'importo_risconto',
        'percentuale_competenza',
        'percentuale_risconto',
        'conto_dare',
        'conto_avere',
        'note',
        'user_id',
    ];

    protected $casts = [
        'importo_totale' => 'float',
        'quota_giornaliera' => 'float',
        'quota_mensile' => 'float',
        'quota_competenza' => 'float',
        'importo_risconto' => 'float',
        'percentuale_competenza' => 'float',
        'percentuale_risconto' => 'float',
        'data_inizio' => 'date',
        'data_fine' => 'date',
        'data_chiusura_bilancio' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
