<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToQuoteCableStructure extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','cavo_id','centro','materiale','descrizione','diametro','peso','ordinata','elementi',
        'posizione','costo','costo_materia_prima','costo_lavorazione','ore_macchina','costo_centro','nota','company_id'];
}
