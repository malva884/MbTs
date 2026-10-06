<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoCache extends Model
{
    protected $table = 'geo_cache';

    protected $fillable = [
        'chiave',
        'indirizzo_ricerca',
        'lat',
        'lng',
        'paese',
        'country_code',
        'display_name',
        'tipo',
    ];
}
