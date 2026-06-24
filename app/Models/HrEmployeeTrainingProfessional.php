<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrEmployeeTrainingProfessional extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = ['id','employee_id','formazione_id','formazione','data_formazione','path_drive','utente_id','tipologia','created_at'];

    public function training()
    {
        return $this->belongsTo(HrTraining::class, 'formazione_id');
    }
}
