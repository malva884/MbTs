<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipientCoordinate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id','citta','cap','indirizzo','latitudine','longitudine', 'km',
    ];
}
