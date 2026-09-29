<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DdtSpedizione extends Model
{
    use HasFactory;

    protected $table = 'ddt_spedizioni';

    protected $fillable = [
        'file_name',
        'drive_path',
        'pdf_path',
        'wf_order_id',
        'numero_ddt',
        'data_ddt',
        'riferimento_interno',
        'ns_ovd',
        'n_colli',
        'peso_lordo_kg',
        'peso_netto_kg',
        'vettore',
        'vettore_palletways',
        'vettore_susa',
        'destinazione_nome',
        'destinazione_indirizzo',
        'destinazione_provincia',
        'destinazione_regione',
        'listino_id',
        'costo_spedizione',
        'costo_tipo_calcolo',
        'costo_note',
        'costo_dettaglio',
        'status',
        'error_message',
        'raw_response',
    ];

    protected $casts = [
        'data_ddt' => 'date',
        'n_colli' => 'integer',
        'peso_lordo_kg' => 'decimal:3',
        'peso_netto_kg' => 'decimal:3',
        'costo_spedizione' => 'decimal:2',
        'costo_dettaglio' => 'array',
        'vettore_palletways' => 'boolean',
        'vettore_susa' => 'boolean',
        'raw_response' => 'array',
    ];

    public function listino()
    {
        return $this->belongsTo(ListinoSpedizione::class, 'listino_id');
    }
}
