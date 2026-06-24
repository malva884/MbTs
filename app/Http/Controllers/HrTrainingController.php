<?php

namespace App\Http\Controllers;

use App\Models\HrTraining;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrTrainingController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $formazioneBy = $request->get('formazione');
        $abbligatorioBy = $request->get('obbligatorio');
        $autoBy = $request->get('auto');
        $tipologiaBy = $request->get('tipologia');

        if(empty($sortByName)){
            $sortByName = 'formazione';
            $orderBy = 'asc';
        }
        $objs = DB::table('hr_trainings')
            ->Where(function ($query) use ($formazioneBy) {
                if ($formazioneBy)
                    $query->Where('formazione', 'Like', '%' . $formazioneBy . '%');
            })
            ->Where(function ($query) use ($abbligatorioBy) {
                if ($abbligatorioBy)
                    $query->Where('obbligatorio', $abbligatorioBy);
            })
            ->Where(function ($query) use ($autoBy) {
                if ($autoBy)
                    $query->Where('auto_creazione', $autoBy);
            })
            ->Where(function ($query) use ($tipologiaBy) {
                if ($tipologiaBy)
                    $query->Where('tipologia', $tipologiaBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function get_list(Request $request)
    {
        $abbligatorioBy = $request->get('obbligatorio');
        $autoBy = $request->get('auto');
        $tipologiaBy = $request->get('tipologia');

        $objs = DB::table('hr_trainings')
            ->Where(function ($query) use ($abbligatorioBy) {
                if ($abbligatorioBy)
                    $query->Where('obbligatorio', $abbligatorioBy);
            })
            ->Where(function ($query) use ($autoBy) {
                if ($autoBy)
                    $query->Where('auto_creazione', $autoBy);
            })
            ->Where(function ($query) use ($tipologiaBy) {
                if ($tipologiaBy)
                    $query->Where('tipologia', $tipologiaBy);
            })
            ->get();

        return response()->json($objs);
    }

    public function store(Request $request)
    {
        $obj = new HrTraining();
        $obj->formazione = ucwords(strtolower($request->formazione));
        $obj->tipologia = $request->tipologia ?? 'obbligatoria';
        $obj->obbligatorio = ($request->obbligatorio ? True:False);
        $obj->auto_creazione = ($request->auto_creazione ? True:False);
        $obj->save();

        $message = 'Messaggi.Formazione-Salvata';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => null
            ]
        );
    }

    public function update(Request $request, $id)
    {
        $obj = HrTraining::find($id);
        $obj->formazione = ucwords(strtolower($request->formazione));
        $obj->tipologia = $request->tipologia ?? 'obbligatoria';
        $obj->obbligatorio = ($request->obbligatorio ? True:False);
        $obj->auto_creazione = ($request->auto_creazione ? True:False);
        $obj->save();

        $message = 'Messaggi.Formazione-Modificata';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => null
            ]
        );
    }
}
