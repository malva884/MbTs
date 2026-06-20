<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrEmployeeTrainingMandatory extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = ['id','employee_id','formazione_id','data_scadenza','data_formazione','path_drive','utente_id','created_at'];
}
