<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class QtSupplier extends Model
{
    use HasUuids;

    protected $connection = 'sqlsrv_fornitori';
    protected $table = 'suppliers';

    protected $fillable = [
        'id',
        'ragioneSociale',
        'email',
        'nazione',
        'indirizzo',
        'cap',
        'citta',
        'codiceSap',
        'categoria',
        'disattivo',
        'qualificato',
        'prezzo',
        'servizio',
        'critico',
        'folderID',
        'rating'
    ];
}
