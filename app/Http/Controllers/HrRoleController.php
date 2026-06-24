<?php

namespace App\Http\Controllers;

use App\Models\HrRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HrRoleController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $ruoloBy = $request->get('role');
        $disattivoBy = $request->get('disattivo');

        if (empty($sortByName)) {
            $sortByName = 'ruolo';
            $orderBy = 'asc';
        }

        $query = HrRole::query();

        if ($ruoloBy) {
            $query->where('ruolo', 'Like', '%' . $ruoloBy . '%');
        }

        if ($disattivoBy !== null && $disattivoBy !== '') {
            $query->where('disattivo', $disattivoBy == 1);
        }

        $objs = $query->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage ?? 10, ['*'], 'page', $request->page ?? 1);

        return response()->json($objs);
    }

    public function getList(Request $request)
    {
        $objs = HrRole::where('disattivo', false)
            ->orderBy('ruolo', 'asc')
            ->get();

        return response()->json($objs);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ruolo' => 'required|string|unique:hr_roles,ruolo',
        ]);

        $obj = new HrRole();
        $obj->ruolo = ucwords(strtolower($request->ruolo));
        $obj->disattivo = ($request->disattivo ? true : false);
        $obj->save();

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Ruolo-Salvato',
            'color' => 'success',
            'obj' => $obj
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'ruolo' => 'required|string|unique:hr_roles,ruolo,' . $id,
        ]);

        $obj = HrRole::find($id);
        if (!$obj) {
            return response()->json([
                'success' => false,
                'message' => 'Ruolo non trovato',
                'color' => 'error'
            ], 404);
        }

        $obj->ruolo = ucwords(strtolower($request->ruolo));
        $obj->disattivo = ($request->disattivo ? true : false);
        $obj->save();

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Ruolo-Modificato',
            'color' => 'success',
            'obj' => $obj
        ]);
    }

    public function destroy($id)
    {
        $obj = HrRole::find($id);
        if ($obj) {
            $obj->disattivo = true;
            $obj->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Ruolo-Eliminato',
            'color' => 'success',
            'obj' => null
        ]);
    }
}
