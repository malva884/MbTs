<?php

namespace App\Http\Controllers;

use App\Models\PrMaterial;
use App\Models\PrStockCategorie;
use Illuminate\Http\Request;

class PrStockCategorieController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $legendaBy = $request->get('legenda');
        $notificaBy = $request->get('notifica');


        if (empty($sortByName)) {
            $sortByName = 'legenda';
            $orderBy = 'asc';
        }

        $objs = PrStockCategorie::Where(function ($query) use ($legendaBy) {
            if ($legendaBy)
                $query->Where('legenda', 'LIKE', '%'.$legendaBy.'%');
            })
            ->Where(function ($query) use ($notificaBy) {
                if ($notificaBy)
                    $query->Where('notifica', $notificaBy);
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function materiali(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $categoriaBy = $request->get('categoria');
        $materialeBy = $request->get('materiale');


        if (empty($sortByName)) {
            $sortByName = 'materiale';
            $orderBy = 'asc';
        }

        $objs = PrMaterial::Where(function ($query) use ($categoriaBy) {
                if ($categoriaBy)
                    $query->Where('categorie', 'LIKE', '%'.$categoriaBy.'%');
            })
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('materiale', 'LIKE', '%'.$materialeBy.'%');
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function get_categorie()
    {
        $objs = PrStockCategorie::all();

        return response()->json($objs);
    }

    public function store(Request $request)
    {

        $obj = new PrStockCategorie;
        $obj->condizioni = $request->condizioni;
        $obj->un = $request->un;
        $obj->quantita = $request->quantita;
        $obj->legenda = $request->legenda;
        $obj->notifica = ($request->notifica ? true:false);
        if(is_array($request->utenti_notifica))
            $obj->utenti_notifica = implode(";",$request->utenti_notifica);
        else
            $obj->utenti_notifica = $request->utenti_notifica;

        $obj->tag = strtoupper($request->tag);
        $obj->save();

        $message = 'Messaggi.Salvato';

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
        $obj = PrStockCategorie::find($id);
        $obj->condizioni = $request->condizioni;
        $obj->un = $request->un;
        $obj->quantita = $request->quantita;
        $obj->legenda = $request->legenda;
        $obj->notifica = ($request->notifica ? true:false);
        if(is_array($request->utenti_notifica))
            $obj->utenti_notifica = implode(";",$request->utenti_notifica);
        else
            $obj->utenti_notifica = $request->utenti_notifica;
        $obj->tag = strtoupper($request->tag);
        $obj->save();

        $message = 'Messaggi.Salvato';

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
