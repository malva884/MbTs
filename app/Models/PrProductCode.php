<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrProductCode extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id', 'codice', 'descrizione_it','descrizione_en','tipologia', 'disattivo','created_at'
    ];
}
