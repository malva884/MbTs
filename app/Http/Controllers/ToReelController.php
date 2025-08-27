<?php

namespace App\Http\Controllers;

use App\Models\ToReel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ToReelController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $bobinaBy = $request->get('bobina');
        $codiceBy = $request->get('codice');


        if (empty($sortByName)) {
            $sortByName = 'bobina';
            $orderBy = 'asc';
        }

        $objs = ToReel::Where(function ($query) use ($bobinaBy) {
            if ($bobinaBy)
                $query->Where('bobina', 'LIKE', '%'.$bobinaBy.'%');
            })
            ->Where(function ($query) use ($codiceBy) {
                if ($codiceBy)
                    $query->Where('codice_as', 'LIKE', '%'.$codiceBy.'%');
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function get_list(Request $request)
    {
        $sortByName = 'lettera';
        $orderBy = 'asc';

        $objs = ToReel::orderBy($sortByName, $orderBy)->get();

        return response()->json($objs);
    }

    public function stored(Request $request)
    {
        $obj = new ToReel();
        $obj->bobina = $request->bobina;
        $obj->capacita = $request->capacita;
        $obj->m3 = $request->m3;
        $obj->codice_as = $request->codice_as;
        $obj->costo = $request->costo;
        $obj->costo_medio = $request->costo_medio;
        $obj->peso = $request->peso;
        $obj->dimensioni = $request->dimensioni;
        $obj->lettera = strtoupper($request->lettera);
        $obj->save();

        $message = 'Messaggi.Bobina-Aggiunta';

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
        $obj = ToReel::find($id);
        $obj->bobina = $request->bobina;
        $obj->capacita = $request->capacita;
        $obj->m3 = $request->m3;
        $obj->codice_as = $request->codice_as;
        $obj->costo = $request->costo;
        $obj->costo_medio = $request->costo_medio;
        $obj->peso = $request->peso;
        $obj->dimensioni = $request->dimensioni;
        $obj->lettera = strtoupper($request->lettera);
        $obj->save();

        $message = 'Messaggi.Bobina-Modificata';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );

    }

    public function get_bobina(Request $request){

        $capacita = ($request->diametro * $request->diametro) * $request->pezzatura;

        $result = ToReel::get_bobina($capacita);

        return json_encode($result);
    }
}
