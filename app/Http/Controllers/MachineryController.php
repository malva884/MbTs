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
        $attivo = $request->get('attivo');

        $objs = DB::table('machineries')->select('id','nome','name_gp','categoria')
            ->Where(function ($query) use ($attivo) {
                if ($attivo !== null && $attivo !== '')
                    $query->Where('attivo', filter_var($attivo, FILTER_VALIDATE_BOOLEAN));
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

        $sortableColumns = ['id', 'nome', 'name_gp', 'lavorazione', 'categoria', 'attivo', 'report_gp', 'velocita_minima', 'check_downtime'];
        if (!in_array($sortByName, $sortableColumns, true)) {
            $sortByName = 'nome';
            $orderBy = 'asc';
        }
        if (!in_array($orderBy, ['asc', 'desc'], true)) {
            $orderBy = 'asc';
        }
        $objs = DB::table('machineries')
            ->Where(function ($query) use ($macchinaBy) {
                if ($macchinaBy)
                    $query->Where('nome', 'LIKE','%'.$macchinaBy.'%');
            })
            ->Where(function ($query) use ($attivoBy) {
                if ($attivoBy !== null && $attivoBy !== '')
                    $query->Where('attivo', (int)$attivoBy);
            })
            ->Where(function ($query) use ($lavorazioneBy) {
                if (in_array($lavorazioneBy, ['1', '2', '3'], true))
                    $query->Where('lavorazione', $lavorazioneBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        $obj = new Machinery();
        $obj->nome = $request->nome;
        $obj->name_gp = $request->name_gp;
        $obj->lavorazione = $request->lavorazione;
        $obj->attivo = ($request->attivo ? true:false);
        $obj->report_gp = ($request->report_gp ? true:false);
        $obj->categoria = $request->categoria;
        $obj->velocita_minima = $request->velocita_minima;
        $obj->id_gp = $request->id_gp;
        $obj->check_downtime = ($request->check_downtime ? true:false);
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
        $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        $obj = Machinery::findOrFail($id);
        $obj->nome = $request->nome;
        $obj->name_gp = $request->name_gp;
        $obj->lavorazione = $request->lavorazione;
        $obj->attivo = ($request->attivo ? true:false);
        $obj->report_gp = ($request->report_gp ? true:false);
        $obj->categoria = $request->categoria;
        $obj->velocita_minima = $request->velocita_minima;
        $obj->id_gp = $request->id_gp;
        $obj->check_downtime = ($request->check_downtime ? true:false);
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
