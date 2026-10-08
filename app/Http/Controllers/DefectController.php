<?php

namespace App\Http\Controllers;

use App\Models\Defect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DefectController extends Controller
{
    public function get_list()
    {
        $objs = DB::table('defects')->select('id','difetto','categoria')
            ->where('attivo',true)
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

        $sortableColumns = ['id', 'difetto', 'categoria', 'lavorazione', 'attivo', 'sl_no'];
        if (!in_array($sortByName, $sortableColumns, true)) {
            $sortByName = 'difetto';
            $orderBy = 'asc';
        }
        if (!in_array($orderBy, ['asc', 'desc'], true)) {
            $orderBy = 'asc';
        }
        $objs = DB::table('defects')
            ->Where(function ($query) use ($difettoBy) {
                if ($difettoBy)
                    $query->Where('difetto', 'LIKE','%'.$difettoBy.'%');
            })
            ->Where(function ($query) use ($attivoBy) {
                if ($attivoBy !== null && $attivoBy !== '')
                    $query->Where('attivo', (int)$attivoBy);
            })
            ->Where(function ($query) use ($lavorazioneBy) {
                if (in_array($lavorazioneBy, ['1', '2'], true))
                    $query->Where('lavorazione', $lavorazioneBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function store(Request $request)
    {
        $request->validate([
            'difetto' => 'required|string|max:255',
        ]);

        $obj = new Defect();
        $obj->difetto = $request->difetto;
        $obj->categoria = $request->categoria;
        $obj->lavorazione = $request->lavorazione;
        $obj->sl_no = $request->sl_no;
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

        $request->validate([
            'difetto' => 'required|string|max:255',
        ]);

        $obj = Defect::findOrFail($id);
        $obj->difetto = $request->difetto;
        $obj->categoria = $request->categoria;
        $obj->lavorazione = $request->lavorazione;
        $obj->sl_no = $request->sl_no;
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
