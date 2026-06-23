<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToCableStructure extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','cavo_id','centro','materiale','descrizione','diametro','peso','ordinata',
        'elementi','posizione','nota','costo','costo_materia_prima','costo_lavorazione','ore_macchina',
        'costo_centro','company_id'];

    public function center()
    {
        return $this->hasOne(ToCenterCost::class, 'centro', 'centro');
    }

    public function material()
    {
        return $this->hasOne(ToMaterial::class, 'materiale', 'materiale');
    }
}
