<?php

namespace App\Http\Controllers;

use App\Models\PlAssetTypology;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlAssetTypologyController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $tipologiaBy = $request->get('titolo');
        $attivoBy = $request->get('attivo');

        if(empty($sortByName)){
            $sortByName = 'tipologia';
            $orderBy = 'asc';
        }
        $objs = DB::table('pl_asset_typologies')
            ->Where(function ($query) use ($tipologiaBy) {
                if ($tipologiaBy)
                    $query->Where('tipologia', 'LIKE','%'.$tipologiaBy.'%');
            })
            ->Where(function ($query) use ($attivoBy) {
                if ($attivoBy)
                    $query->Where('attivo', $attivoBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function get_list(Request $request)
    {
        $attivoBy = $request->get('attivo');

        $objs = DB::table('pl_asset_typologies')
            ->Where(function ($query) use ($attivoBy) {
                if ($attivoBy)
                    $query->Where('attivo', $attivoBy);
            })
            ->orderBy('tipologia', 'asc')
            ->get();

        return response()->json($objs);
    }

    public function store(Request $request)
    {
        $obj = new PlAssetTypology();
        $obj->tipologia = $request->tipologia;
        $obj->icona = $request->icona;
        $obj->attivo = ($request->attivo ? true:false);
        $obj->save();

        $message = 'Messaggi.Tipologia-Aggiunta';

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
        $obj = PlAssetTypology::find($id);
        $obj->tipologia = $request->tipologia;
        $obj->icona = $request->icona;
        $obj->attivo = ($request->attivo ? true:false);
        $obj->save();

        $message = 'Messaggi.Tipologia-Modificata';

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
