<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiTurnoverRow extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id','head','quantita','unit','materiale','importo_valuta_locale','documento_numero',
        'documento_tipo','cliente','tipologia_cavo','data_documento','data_publicazione','chiave_publicazione',
        'valuta_locale','tax_code','account_tipo','codice_cliente','ckm','fkm','paese','account','check'
    ];

}
