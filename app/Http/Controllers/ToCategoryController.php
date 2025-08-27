<?php

namespace App\Http\Controllers;

use App\Models\ToCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ToCategoryController extends Controller
{
    public function list(Request $request)
    {

        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $categoriaBy = $request->get('categoria');
        $normaBy = $request->get('norma');


        if (empty($sortByName)) {
            $sortByName = 'categoria';
            $orderBy = 'asc';
        }

        $objs = ToCategory::Where(function ($query) use ($categoriaBy) {
            if ($categoriaBy)
                $query->Where('categoria', 'LIKE', '%'.$categoriaBy.'%');
            })
            ->Where(function ($query) use ($normaBy) {
                if ($normaBy)
                    $query->Where('legistrazione', 'LIKE', '%'.$normaBy.'%');
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function get_list(Request $request)
    {
        if (empty($sortByName)) {
            $sortByName = 'categoria';
            $orderBy = 'asc';
        }
        $moduloBy = $request->get('modulo');

        $objs = ToCategory::Where(function ($query) use ($moduloBy) {
            if ($moduloBy)
                $query->Where('modulo', $moduloBy);
        })
            ->orderBy($sortByName, $orderBy)
            ->get();

        return response()->json($objs);
    }

    public function stored(Request $request)
    {
        $obj = new ToCategory();
        $obj->categoria = strtoupper($request->categoria);
        $obj->modulo = $request->modulo;
        $obj->legistrazione = $request->legistrazione;
        $obj->nota = $request->nota;
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
        $obj = ToCategory::find($id);
        $obj->categoria = strtoupper($request->categoria);
        $obj->modulo = $request->modulo;
        $obj->legistrazione = $request->legistrazione;
        $obj->nota = $request->nota;
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
