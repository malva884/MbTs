<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RpRegisterActivityController extends Controller
{
    public function list(Request $request){
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');


        if(empty($sortByName)){
            $sortByName = 'data_azione';
            $orderBy = 'asc';
        }
        $objs = DB::table('rp_register_activities')->select('rp_register_activities.azione','rp_register_activities.data_azione','rp_register_logs.*','users.full_name')
            ->Join('rp_register_logs','rp_register_logs.id','rp_register_activities.rp_register_id')
            ->join('users','users.id','rp_register_logs.user')
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }
}
