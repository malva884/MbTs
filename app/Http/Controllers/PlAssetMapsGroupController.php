<?php

namespace App\Http\Controllers;

use App\Models\PlAssetMapsGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlAssetMapsGroupController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $titoloBy = $request->get('titolo');
        $attivoBy = $request->get('attivo');

        if(empty($sortByName)){
            $sortByName = 'titolo';
            $orderBy = 'asc';
        }
        $objs = DB::table('pl_asset_maps_groups')
            ->Where(function ($query) use ($titoloBy) {
                if ($titoloBy)
                    $query->Where('titolo', 'LIKE','%'.$titoloBy.'%');
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

        $objs = DB::table('pl_asset_maps_groups')
            ->Where(function ($query) use ($attivoBy) {
                if ($attivoBy)
                    $query->Where('attivo', $attivoBy);
            })
            ->orderBy('titolo', 'asc')
            ->get();

        return response()->json($objs);
    }

    public function store(Request $request)
    {
        $obj = new PlAssetMapsGroup();
        $obj->titolo = $request->titolo;
        $obj->attivo = ($request->attivo ? true:false);
        $obj->save();

        $message = 'Messaggi.Gruppo-Aggiunto';

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
        $obj = PlAssetMapsGroup::find($id);
        $obj->titolo = $request->titolo;
        $obj->attivo = ($request->attivo ? true:false);
        $obj->save();

        $message = 'Messaggi.Gruppo-Modificato';

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
