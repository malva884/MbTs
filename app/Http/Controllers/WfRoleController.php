<?php

namespace App\Http\Controllers;

use App\Models\WfRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WfRoleController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $ruoloBy = $request->get('ruolo');
        $modelBy = $request->get('model');
        $disabledBy = $request->get('disabled');


        if (empty($sortByName)) {
            $sortByName = 'created_at';
            $orderBy = 'desc';
        }

        $objs = WfRole::select('*')
            ->Where(function ($query) use ($ruoloBy) {
                if ($ruoloBy)
                    $query->Where('ruolo', $ruoloBy);
            })
            ->Where(function ($query) use ($modelBy) {
                if ($modelBy)
                    $query->Where('model', $modelBy);
            })
            ->Where(function ($query) use ($disabledBy) {
                if ($disabledBy)
                    $query->Where('disabled', $disabledBy);
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);


        return response()->json($objs);
    }

    public function store(Request $request)
    {

        $obj = new WfRole();
        $obj->role = ucfirst(strtolower($request['role']));
        $obj->model = $request->model;
        $obj->disabled = ($request->disabled ? true:false);
        $obj->save();

        $message = 'Messaggi.Ruolo-Aggiunto';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function getModel()
    {
        $objs = WfRole::$WfModels;

        return response()->json(
            [
                'success' => true,
                'objs' => $objs
            ]
        );
    }

    public function getRole(Request $request)
    {
        $model =  $request->model;
        $objs = WfRole::where('disabled',0)
            ->Where(function ($query) use ($model) {
                if ($model)
                    $query->Where('model', $model);
            })
        ->get();

        return response()->json(
            [
                'success' => true,
                'objs' => $objs
            ]
        );
    }
}
