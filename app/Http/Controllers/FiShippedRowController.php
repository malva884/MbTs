<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FiShippedRowController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $macchinaBy = $request->get('macchina');
        $dataBy = $request->get('data');

        if (empty($sortByName)) {
            $sortByName = 'date_row';
            $orderBy = 'desc';
        }
        $objs = DB::table('fi_shipped_rows')
            ->Where(function ($query) use ($macchinaBy) {
                if ($macchinaBy)
                    $query->Where('nome', 'LIKE', '%' . $macchinaBy . '%');
            })
            ->Where(function ($query) use ($dataBy) {
                if ($dataBy){
                    $dataBy = explode(' to ',$dataBy);
                    if(count($dataBy) == 2)
                        $query->whereBetween('date_row', $dataBy);
                    else
                        $query->Where('date_row', $dataBy);

                }
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }
}
