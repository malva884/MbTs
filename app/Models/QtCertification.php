<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class QtCertification extends Model
{
    use HasUuids;

    protected $connection = 'sqlsrv_fornitori';
    protected $table = 'certifications';

    protected $fillable = [
        'id',
        'titolo',
        'descrizione',
        'disattivo',
        'valore_rating',
        'sigla'
    ];
}
