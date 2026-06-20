<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HrDepartmentController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $repartoBy = $request->get('reparto');


        if(empty($sortByName)){
            $sortByName = 'formazione';
            $orderBy = 'asc';
        }
        $objs = DB::table('hr_departments')
            ->Where(function ($query) use ($repartoBy) {
                if ($repartoBy)
                    $query->Where('reparto','Like', '%'.$repartoBy.'%');
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);

    }

    public function getList(Request $request)
    {
        $objs = DB::table('hr_departments')
            ->where('disattivo',false)
            ->orderBy('reparto','asc')
            ->get();

        return response()->json($objs);
    }
}
