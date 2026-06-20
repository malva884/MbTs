<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrEmployee extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'id','nome','cognome','nome_completo','email','data_assunzione','data_nascita','data_ultima_visita',
        'data_scadenza_visita','numero_anni_visita_medica','tel','tel_az','avatar','dimesso','path_drive','valutatore',
        'ruolo_id','reparto_id','centro_id','company_id','matricola','sesso'
    ];

    public function centerCost()
    {
        return $this->hasOne(HrCostCenter::class, "id", "centro_id");
    }
}
