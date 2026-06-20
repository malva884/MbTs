<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfDocumentValidation extends Model
{
    use HasFactory, HasUuids;

    // 1. Indica a Laravel che la chiave primaria NON è un intero incrementale
    public $incrementing = false;

    // 2. Definisci il tipo di dati della chiave primaria come stringa
    protected $keyType = 'string';

    protected $fillable = [
        'id', // Se lo usi come WfCategory
        'wf_document_id',
        'user_id',
        'reparto',
        'stato',
        'tipologia_validazione'
    ];

}
