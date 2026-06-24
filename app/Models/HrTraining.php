<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HrTraining extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','formazione','tipologia','obbligatorio','auto_creazione'];

    protected static function booted()
    {
        static::saving(function ($training) {
            // Sincronizzazione bidirezionale per retrocompatibilità e integrità
            if ($training->isDirty('tipologia') && !$training->isDirty('obbligatorio')) {
                $training->obbligatorio = ($training->tipologia === 'obbligatoria');
            } elseif ($training->isDirty('obbligatorio') && !$training->isDirty('tipologia')) {
                $training->tipologia = $training->obbligatorio ? 'obbligatoria' : 'professionale';
            }
        });
    }

    static function createTraning($idDipendente)
    {
        $objs =  DB::table('hr_trainings')
            ->select('id','formazione')
            ->where('tipologia', 'obbligatoria')
            ->get();

        foreach ($objs as $obj){
            $traning =  new HrEmployeeTrainingMandatory();
            $traning->employee_id = $idDipendente;
            $traning->formazione_id = $obj->id;
            $traning->data_formazione = date('Y-m-d');
            $traning->data_scadenza = null;
            $traning->utente_id = Auth::id();
            $traning->save();
        }
    }

}
