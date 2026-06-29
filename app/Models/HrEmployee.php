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
        'reparto_id','centro_id','company_id','matricola','sesso'
    ];

    public function roles()
    {
        return $this->belongsToMany(HrRole::class, 'hr_employee_role', 'employee_id', 'role_id');
    }

    public function centerCost()
    {
        return $this->belongsTo(HrCostCenter::class, "centro_id", "id");
    }

    public function department()
    {
        return $this->belongsTo(HrDepartment::class, "reparto_id", "id");
    }

    public function assetAssignments()
    {
        return $this->hasMany(ItAssetAssignment::class, 'employee_id');
    }
}
