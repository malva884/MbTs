<?php

namespace App\Http\Controllers;

use App\Models\Machinery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MachineryController extends Controller
{
    public function get_list(Request $request)
    {
        $attivo = null;
        if(!empty($request->attivo) && $request->attivo === true)
            $attivo = true;
        elseif(!empty($request->attivo) && $request->attivo === false)
            $attivo = false;

        $objs = DB::table('machineries')->select('id','nome','name_gp','categoria')
            ->Where(function ($query) use ($attivo) {
                if ($attivo)
                    $query->Where('attivo',$attivo);
            })
            ->get();

        return response()->json($objs);
    }

    public function list(Request $request)
    {

        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $macchinaBy = $request->get('macchina');
        $attivoBy = $request->get('attivo');
        $lavorazioneBy = $request->get('lavorazione');

        if(empty($sortByName)){
            $sortByName = 'nome';
            $orderBy = 'asc';
        }
        $objs = DB::table('machineries')
            ->Where(function ($query) use ($macchinaBy) {
                if ($macchinaBy)
                    $query->Where('nome', 'LIKE','%'.$macchinaBy.'%');
            })
            ->Where(function ($query) use ($attivoBy) {
                if ($attivoBy)
                    $query->Where('attivo', $attivoBy);
            })
            ->Where(function ($query) use ($lavorazioneBy) {
                if ($lavorazioneBy)
                    $query->Where('lavorazione', $lavorazioneBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function store(Request $request)
    {
        $obj = New Machinery();
        $obj->nome = $request->nome;
        $obj->name_gp = $request->name_gp;
        $obj->lavorazione = $request->lavorazione;
        $obj->attivo = ($request->attivo ? true:false);
        $obj->report_gp = ($request->report_gp ? true:false);
        $obj->categoria = $request->categoria;
        $obj->save();

        $message = 'Messaggi.Macchina-Aggiunta';

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
        $obj = Machinery::find($id);
        $obj->nome = $request->nome;
        $obj->name_gp = $request->name_gp;
        $obj->lavorazione = $request->lavorazione;
        $obj->attivo = ($request->attivo ? true:false);
        $obj->report_gp = ($request->report_gp ? true:false);
        $obj->categoria = $request->categoria;
        $obj->save();

        $message = 'Messaggi.Macchina-Modificata';

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
