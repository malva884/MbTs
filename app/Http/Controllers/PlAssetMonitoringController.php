<?php

namespace App\Http\Controllers;

use App\Models\PlAssetMonitoring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlAssetMonitoringController extends Controller
{
    public function list(Request $request, $id)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');

        if(empty($sortByName)){
            $sortByName = 'data';
            $orderBy = 'desc';
        }
        $objs = DB::table('pl_asset_monitorings')
            ->where('asset_id', $id)
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function list_categoria(Request $request, $id)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');

        if(empty($sortByName)){
            $sortByName = 'data';
            $orderBy = 'desc';
        }

        $objs = DB::table('pl_asset_monitorings')
            ->join('pl_assets','pl_assets.id','pl_asset_monitorings.asset_id')
            ->select(DB::raw('ROW_NUMBER() OVER(PARTITION BY asset_id ORDER BY data DESC) AS RowNum, pl_asset_monitorings.*'))
            ->where('pl_assets.tag_asset',$id)
            ->orderBy($sortByName, $orderBy)
            ->get();

        $temp = $objs->where('RowNum',1);

        return response()->json($temp);
    }
    public function monitoring(Request $request, $serial)
    {
        $asset = DB::table('pl_assets')->select('pl_assets.id')
            ->where('pl_assets.numero_seriale', $serial)
            ->first();
        $objs = [];
        if(!empty($asset->id)){
            $monitoring_old = DB::table('pl_asset_monitorings')->select('pl_asset_monitorings.id_client')
                ->where('asset_id',$asset->id)
                ->orderBy('data','desc')
                ->first();

            foreach ($request->all() as $rows)
                foreach ($rows as $row) {
                    $columns = explode(";", $row);
                    if (empty($monitoring_old->id_client) || $columns[0] > $monitoring_old->id_client)
                        $objs[] = [
                            'asset_id' => $asset->id,
                            'id_client' => $columns[0],
                            'data' => date("Y-m-d H:i:s", $columns[0]),
                            'tipo_log' => $columns[1],
                            'hostname' => $columns[2],
                            'gp_stato' => $columns[4],
                            'stl_app' => $columns[5],
                            'portale_stato' => $columns[6],
                            'dc_stato' => $columns[7],
                            'ip_uno_stato' => $columns[8],
                            'ip_due_stato' => $columns[9],
                            'ip_tre_stato' => $columns[10],
                            'ip_quatro_stato' => $columns[11],
                            'ip_cinque_stato' => $columns[12],
                        ];
                }
        }

        foreach ($objs as $obj) {
            $monitoring = new PlAssetMonitoring();
            $monitoring->fill($obj);
            $monitoring->save();
        }

    }

}
