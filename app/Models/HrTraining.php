<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HrTraining extends Model
{
    use HasFactory;

    protected $fillable = ['id','formazione','obbligatorio','auto_creazione'];

    static function createTraning($idDipendente)
    {
        $objs =  DB::table('hr_trainings')
            ->select('id','formazione')
            ->where('auto_creazione', 1)
            ->get();

        foreach ($objs as $obj){
            $traning =  new HrEmployeeTrainingMandatory();
            $traning->employee_id = $idDipendente;
            $traning->formazione_id = $obj->id;
            $traning->data_formazione = date('Y-m-d');
            $traning->data_scadenza = date('Y-m-d');
            $traning->utente_id = Auth::id();
            $traning->save();
        }
    }

}
