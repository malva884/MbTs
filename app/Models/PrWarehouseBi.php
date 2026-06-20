<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrWarehouseBi extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','id_material','materiale', 'descrizione','um','quantita','valore_uni','totole','categorie',
        'data_ultimo_movimento','days_last_movement','range_last_moviment','anno','mese','settimana','verificato'];
}
