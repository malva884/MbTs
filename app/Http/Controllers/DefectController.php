<?php

namespace App\Http\Controllers;

use App\Models\Defect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DefectController extends Controller
{
    public function get_list(Request $request)
    {
        $attivo = null;
        if(!empty($request->attivo) && $request->attivo === true)
            $attivo = true;
        elseif(!empty($request->attivo) && $request->attivo === false)
            $attivo = false;

        $objs = DB::table('defects')->select('id','difetto','categoria')
            ->Where(function ($query) use ($attivo) {
                if ($attivo)
                    $query->Where('attivo',$attivo);
            })
            ->whereIn('lavorazione',[1,2])
            ->orderBy('difetto','asc')
            ->get();

        return response()->json($objs);
    }

    public function list(Request $request)
    {

        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $difettoBy = $request->get('difetto');
        $attivoBy = $request->get('attivo');
        $lavorazioneBy = $request->get('lavorazione');

        if(empty($sortByName)){
            $sortByName = 'difetto';
            $orderBy = 'asc';
        }
        $objs = DB::table('defects')
            ->Where(function ($query) use ($difettoBy) {
                if ($difettoBy)
                    $query->Where('difetto', 'LIKE','%'.$difettoBy.'%');
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

    public function store(Request $request, $id)
    {
        $obj = New Defect();
        $obj->difetto = $request->difetto;
        $obj->categoria = $request->categoria;
        $obj->lavorazione = $request->lavorazione;
        $obj->sl_no = $request->sl_no;
        $obj->descrizione = $request->descrizione;
        $obj->requisiti = $request->requisiti;
        $obj->attivo = ($request->attivo ? true:false);
        $obj->save();

        $message = 'Messaggi.Difetto-Aggiunto';

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

        $obj = Defect::find($id);
        $obj->difetto = $request->difetto;
        $obj->categoria = $request->categoria;
        $obj->lavorazione = $request->lavorazione;
        $obj->sl_no = $request->sl_no;
        $obj->descrizione = $request->descrizione;
        $obj->requisiti = $request->requisiti;
        $obj->attivo = ($request->attivo ? true:false);
        $obj->save();

        $message = 'Messaggi.Difetto-Modificato';

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
