<?php

namespace App\Http\Controllers;

use App\Models\FiberType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FiberTypeController extends Controller
{
    public function get_list(Request $request)
    {
        $attivo = null;
        if (!empty($request->attivo) && $request->attivo === true)
            $attivo = true;
        elseif (!empty($request->attivo) && $request->attivo === false)
            $attivo = false;

        $objs = DB::table('fiber_types')->select('id', 'nome')
            ->Where(function ($query) use ($attivo) {
                if ($attivo)
                    $query->Where('attivo', $attivo);
            })
            ->get();

        return response()->json($objs);
    }

    public function list(Request $request)
    {

        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $tipologiaBy = $request->get('tipologia');
        $attivoBy = $request->get('attivo');

        if(empty($sortByName)){
            $sortByName = 'nome';
            $orderBy = 'asc';
        }
        $objs = DB::table('fiber_types')
            ->Where(function ($query) use ($tipologiaBy) {
                if ($tipologiaBy)
                    $query->Where('nome', 'LIKE','%'.$tipologiaBy.'%');
            })
            ->Where(function ($query) use ($attivoBy) {
                if ($attivoBy)
                    $query->Where('attivo', $attivoBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function store(Request $request, $id)
    {
        $obj = New FiberType();
        $obj->nome = $request->nome;
        $obj->attivo = ($request->attivo ? true:false);
        $obj->save();

        $message = 'Messaggi.Tipologia-Fibra-Aggiunta';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function update(Request $request, $id)
    {

        $obj = FiberType::find($id);
        $obj->nome = $request->nome;
        $obj->attivo = ($request->attivo ? true:false);
        $obj->save();

        $message = 'Messaggi.Tipologia-Fibra-Modificata';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }
}
