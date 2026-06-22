<?php

namespace App\Http\Controllers;

use App\Models\HrCostCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrCostCenterController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $userBy = $request->get('centro');
        $centroBy = $request->get('centro');


        if(empty($sortByName)){
            $sortByName = 'centro_di_costo';
            $orderBy = 'asc';
        }
        $objs = DB::table('hr_cost_centers')
            ->select('hr_cost_centers.*')
            ->Where(function ($query) use ($centroBy) {
                if ($centroBy)
                    $query->Where('centro_di_costo', 'LIKE','%'.$centroBy.'%');
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function store(Request $request)
    {
        $obj = new HrCostCenter();
        $obj->centro_di_costo = $request->centro_di_costo;
        $obj->valore = $request->valore;
        $obj->disattivo = $request->disattivo;
        $obj->save();

        $message = 'Messaggi.Centro-Di-Costo-Salvato';

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
        $obj = HrCostCenter::where('id',$id)->first();
        $obj->valore = $request->valore;
        $obj->disattivo = ($request->disattivo ? true:false);
        $obj->save();

        $message = 'Messaggi.Centro-Di-Costo-Modificato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function get_list(Request $request)
    {
        $objs = DB::table('hr_cost_centers')
            ->select('hr_cost_centers.*')
            ->whereNotNull('valore')
			->orderBy('centro_di_costo','asc')
            ->get();

        return response()->json($objs);
    }
}
