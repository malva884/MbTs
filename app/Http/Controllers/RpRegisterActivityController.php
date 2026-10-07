<?php

namespace App\Http\Controllers;

use App\Exports\VisitorsPresentExport;
use App\Models\RpRegisterLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

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

    public function visitorsPresent(Request $request)
    {
        $objs = DB::table('rp_register_activities')
            ->select('rp_register_activities.data_azione', 'rp_register_logs.nome', 'rp_register_logs.azienda', 'rp_register_logs.email')
            ->join('rp_register_logs', 'rp_register_logs.id', 'rp_register_activities.rp_register_id')
            ->where('rp_register_activities.presente', true)
            ->where('rp_register_activities.azione', 'Entrata')
            ->whereDate('rp_register_activities.data_azione', today())
            ->orderBy('rp_register_activities.data_azione', 'desc')
            ->get();

        return response()->json([
            'count' => $objs->count(),
            'items' => $objs,
        ]);
    }

    public function recentActivities(Request $request)
    {
        $limit = $request->get('limit', 10);

        $objs = DB::table('rp_register_activities')
            ->select('rp_register_activities.azione', 'rp_register_activities.data_azione', 'rp_register_logs.nome', 'rp_register_logs.azienda', 'users.full_name')
            ->join('rp_register_logs', 'rp_register_logs.id', 'rp_register_activities.rp_register_id')
            ->join('users', 'users.id', 'rp_register_logs.user')
            ->orderBy('rp_register_activities.data_azione', 'desc')
            ->limit($limit)
            ->get();

        return response()->json($objs);
    }

    public function exportVisitorsPresent(Request $request)
    {
        $name_file = 'visitatori_presenti_' . date('dmY') . '.xlsx';
        $export = new VisitorsPresentExport();
        return Excel::download($export, $name_file);
    }


}
