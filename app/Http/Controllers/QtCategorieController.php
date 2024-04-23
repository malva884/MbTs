<?php

namespace App\Http\Controllers;

use App\Models\QtCategorie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QtCategorieController extends Controller
{
    public function list(Request $request)
    {

        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $categoriaBy = $request->get('categoria');
        $attivoBy = $request->get('attivo');
        $moduloBy = $request->get('modulo');

        if(empty($sortByName)){
            $sortByName = 'categoria';
            $orderBy = 'asc';
        }
        $objs = DB::table('qt_categories')
            ->Where(function ($query) use ($categoriaBy) {
                if ($categoriaBy)
                    $query->Where('categoria', 'LIKE','%'.$categoriaBy.'%');
            })
            ->Where(function ($query) use ($attivoBy) {
                if ($attivoBy)
                    $query->Where('disabled', $attivoBy);
            })
            ->Where(function ($query) use ($moduloBy) {
                if ($moduloBy)
                    $query->Where('moduli', $moduloBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }
    public function store(Request $request)
    {
        $obj = new QtCategorie();
        $obj->categoria = $request['categoria'];
        $obj->descrizione = $request['descrizione'];
        $obj->valore = $request['valore'];
        $obj->moduli = implode(',',$request['moduli']);
        $obj->id_drive = $request['id_drive'];
        $obj->disabled = ($request->disabled ? true:false);

        $obj->save();

        $message = 'Messaggi.Categoia-Aggiunta';

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
        $obj = QtCategorie::find($request->id);
        $obj->categoria = $request['categoria'];
        $obj->descrizione = $request['descrizione'];
        $obj->valore = $request['valore'];
        $obj->moduli = implode(',',$request['moduli']);
        $obj->id_drive = $request['id_drive'];
        $obj->disabled = ($request->disabled ? true:false);
        $obj->save();

        $message = 'Messaggi.Categoia-Modificata';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function get_categorie(Request $request)
    {
        $objs = DB::table('qt_categories')
            ->where('moduli',$request->modulo)
            ->orderBy('categoria', 'asc') //order in descending order
            ->get();

        return response()->json($objs);
    }
}
