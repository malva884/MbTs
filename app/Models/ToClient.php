<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToClient extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','ragione_sociale','codice_sap','email','telefono','provincia','citta','cap','indirizzo','disabled','company_id'];
}
