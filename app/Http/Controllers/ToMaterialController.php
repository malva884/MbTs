<?php

namespace App\Http\Controllers;

use App\Models\ToMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ToMaterialController extends Controller
{
    public function list(Request $request)
    {


        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $materialeBy = $request->get('materiale');
		$descrizioneBy = $request->get('descrizione');
        $attivoBy = $request->get('attivo');


        if (empty($sortByName)) {
            $sortByName = 'materiale';
            $orderBy = 'asc';
        }

        $objs = ToMaterial::Where(function ($query) use ($materialeBy) {
            if ($materialeBy)
                $query->Where('materiale', 'LIKE', '%'.$materialeBy.'%');
        })
            ->where(function ($query) use ($attivoBy) {
                if ($attivoBy)
                    $query->Where('disabled',$attivoBy);
            })
			->where(function ($query) use ($descrizioneBy) {
                if ($descrizioneBy)
                    $query->Where('descrizione', 'LIKE', '%'.$descrizioneBy.'%');
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function get_list(Request $request)
    {

        $objs = ToMaterial::Where('disabled',false)->get();

        return response()->json($objs);
    }

    public function stored(Request $request)
    {
        $obj = new ToMaterial();
        $obj->materiale = strtoupper($request->materiale);
        $obj->descrizione = $request->descrizione;
        $obj->costo = $request->costo;
        $obj->diametro = $request->diametro;
        $obj->disabled = ($request->disabled ? true:false);
        $obj->save();

        $message = 'Messaggi.Materiale-Aggiunto';

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
        $obj = ToMaterial::find($id);
        $obj->materiale = strtoupper($request->materiale);
        $obj->descrizione = $request->descrizione;
        $obj->costo = $request->costo;
        $obj->diametro = $request->diametro;
		$obj->updated_at = $request->updated_at;
        $obj->disabled = ($request->disabled ? true:false);
        $obj->save();

        $message = 'Messaggi.Materiale-Modificato';

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
