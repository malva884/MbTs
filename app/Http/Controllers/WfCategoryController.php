<?php

namespace App\Http\Controllers;

use App\Models\WfCategory;
use Illuminate\Http\Request;

class WfCategoryController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $categoriaBy = $request->get('categoria');
        $modelBy = $request->get('model');
        $disabledBy = $request->get('disabled');

        if (empty($sortByName)) {
            $sortByName = 'categoria';
            $orderBy = 'asc';
        }

        $objs = WfCategory::select('*')
            ->Where(function ($query) use ($categoriaBy) {
                if ($categoriaBy)
                    $query->Where('categoria', 'LIKE', '%'.$categoriaBy.'%');
            })
            ->Where(function ($query) use ($modelBy) {
                if ($modelBy)
                    $query->Where('model', $modelBy);
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function store(Request $request)
    {

        $obj = new WfCategory();
        $obj->categoria = $request['categoria'];
        $obj->model = $request->model;
        $obj->descrizione = $request->descrizione;
        $obj->folder_drive = $request->folder_drive;
        $obj->save();

        $message = 'Messaggi.Categoria-Aggiunta';

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

        $obj = WfCategory::find($id);
        $obj->categoria = $request['categoria'];
        $obj->model = $request->model;
        $obj->descrizione = $request->descrizione;
        $obj->folder_drive = $request->folder_drive;
        $obj->save();

        $message = 'Messaggi.Categoria-Modificata';

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
