<?php

namespace App\Http\Controllers;

use App\Models\ToCenterCost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ToCenterCostController extends Controller
{
    public function list(Request $request)
    {

        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $centroBy = $request->get('centro');
        $attivoBy = $request->get('attivo');


        if (empty($sortByName)) {
            $sortByName = 'centro';
            $orderBy = 'asc';
        }

        $objs = ToCenterCost::Where(function ($query) use ($centroBy) {
            if ($centroBy)
                $query->Where('centro', 'LIKE', '%'.$centroBy.'%');
            })
            ->where(function ($query) use ($attivoBy) {
                if ($attivoBy)
                    $query->Where('disabled',$attivoBy);
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);


        return response()->json($objs);
    }

    public function get_list(Request $request)
    {
        if (empty($sortByName)) {
            $sortByName = 'centro';
            $orderBy = 'asc';
        }

        $objs = ToCenterCost::Where('disabled',false)
            ->orderBy($sortByName, $orderBy)
            ->get();

        return response()->json($objs);
    }

    public function stored(Request $request)
    {
        $obj = new ToCenterCost();
        $obj->centro = strtoupper($request->centro);
        $obj->costo = $request->costo;
        $obj->disabled = ($request->disabled ? true:false);
        $obj->save();

        $message = 'Messaggi.Centro-Di-Costro-Aggiunto';

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
        $obj = ToCenterCost::find($id);
        $obj->centro = strtoupper($request->centro);
        $obj->costo = $request->costo;
        $obj->disabled = ($request->disabled ? true:false);
        $obj->save();

        $message = 'Messaggi.Centro-Di-Costro-Modificato';

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
