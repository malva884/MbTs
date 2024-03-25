<?php

namespace App\Http\Controllers;

use App\Models\RpRegisterLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RpRegisterActivityController extends Controller
{
    public function list(Request $request){
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $visitatore = $request->get('visitatore');
        $azienda = $request->get('azienda');
        $data = $request->get('data');

        if(empty($sortByName)){
            $sortByName = 'data_azione';
            $orderBy = 'desc';
        }
        $objs = DB::table('rp_register_activities')->select('rp_register_activities.azione','rp_register_activities.data_azione','rp_register_logs.*','users.full_name')
            ->Join('rp_register_logs','rp_register_logs.id','rp_register_activities.rp_register_id')
            ->join('users','users.id','rp_register_logs.user')
            ->Where(function ($query) use ($visitatore) {
                $query->where('rp_register_logs.nome','LIKE', '%'.$visitatore.'%');
            })
            ->Where(function ($query) use ($azienda) {
                $query->where('rp_register_logs.azienda','LIKE', '%'.$azienda.'%');
            })
            ->Where(function ($query) use ($data) {
                if($data){
                    $data = explode('-',$data);
                    $query->whereYear('rp_register_activities.data_azione',$data[0])
                        ->whereMonth('rp_register_activities.data_azione',$data[1])
                        ->whereDay('rp_register_activities.data_azione',$data[2]);
                }

            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function store(Request $request){

        Log::channel('stderr')->info($request);
        $obj = new RpRegisterLog();

    }
}
