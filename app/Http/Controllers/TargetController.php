<?php

namespace App\Http\Controllers;

use App\Models\Target;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TargetController extends Controller
{
    public function store($items, $tipo, $data)
    {
        foreach ($items as $item) {
            $obj = Target::where('data_riferimento', $data)->where('tipo', $tipo)->where('titolo', $item['titolo'])->first();
            if (empty($obj->id))
                $obj = new Target();

            $obj->titolo = $item['titolo'];
            $obj->tipo = $tipo;
            $obj->target = $item['target'];
            $obj->data_riferimento = $data;
            $obj->id_riferimento = $item['id'];
            $obj->user = Auth::id();
            $obj->save();
        }
    }

    public function update($items, $tipo, $data)
    {
        $objs = Target::where('data_riferimento', $data)->where('tipo', $tipo)->get();
        foreach ($objs as $obj) {
            if (!empty($items[$obj->titolo])) {

                //$obj->valore = $obj->valore + $items[$obj->titolo];
                $obj->valore = $items[$obj->titolo];
                $obj->save();
            }

        }
    }

    public function list(Request $request, $id)
    {

        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $periodoBy = $request->get('periodoBy');


        if (empty($sortByName)) {
            $sortByName = 'data_riferimento';
            $orderBy = 'desc';
        }
        $objs = DB::table('targets')
            ->where('tipo', $id)
            ->Where(function ($query) use ($periodoBy) {
                if ($periodoBy)
                    $query->Where('data_riferimento', $periodoBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function save(Request $request)
    {
        $obj = new Target();
        $obj->titolo = $request->titolo;
        $obj->tipo = $request->modulo;
        $obj->target = $request->target;
        $obj->data_riferimento = $request->anno . '-' . $request->mese . '-01';
        $obj->user = Auth::id();
        $obj->save();

        $message = 'Messaggi.Target-Aggiunto';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function edit(Request $request, $id)
    {

        $obj = Target::find($id);
        $obj->titolo = $request->titolo;
        $obj->tipo = $request->modulo;
        $obj->target = $request->target;
        $obj->data_riferimento = $request->anno . '-' . $request->mese . '-01';
        $obj->user = Auth::id();
        $obj->save();

        $message = 'Messaggi.Target-Modificato';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function ricalcola($id)
    {
        $colums = [
            1 => [
                'value_cc' => ['colum' => 'importo_valuta_locale', 'tipo' => 5441],
                'value_ofc' => ['colum' => 'importo_valuta_locale', 'tipo' => 5420],
                'ckm_cc' => ['colum' => 'ckm', 'tipo' => 5441],
                'ckm_ofc' => ['colum' => 'ckm', 'tipo' => 5420],
                'fkm_ofc' => ['colum' => 'fkm', 'tipo' => 5420],
            ],
            2 => [
                'value_cc' => ['colum' => 'cost_value', 'tipo' => 5441],
                'value_ofc' => ['colum' => 'cost_value', 'tipo' => 5420],
                'ckm_cc' => ['colum' => 'delivered_qty', 'tipo' => 5441],
                'ckm_ofc' => ['colum' => 'delivered_qty', 'tipo' => 5420],
                'fkm_ofc' => ['colum' => 'qty_fkm', 'tipo' => 5420],
            ],
            3=>[
                'ckm_cc' => ['colum' => 'cc_ckm_production'],
                'ckm_ofc' => ['colum' => 'of_ckm_production' ],
                'kfkm_ofc' => ['colum' => 'of_kfkm_production'],
            ]
        ];
        $obj = Target::find($id);
        $t = explode('-', $obj->data_riferimento);

        switch ($obj->tipo) {
            case 1:
                $result = DB::table('fi_turnover_rows')->select(DB::raw('SUM('.$colums[1][$obj->titolo]['colum'].') as tot'))
                    ->whereYear('data_documento', $t[0])
                    ->whereMonth('data_documento',$t[1])
                    ->where('tipologia_cavo', $colums[1][$obj->titolo]['tipo'])
                    ->where('head', $obj->id_riferimento)
                    ->first();
                break;
            case 2:
                $result = DB::table('fi_shipped_rows')->select(DB::raw('SUM('.$colums[2][$obj->titolo]['colum'].') as tot'))
                    ->whereYear('date_row', $t[0])
                    ->whereMonth('date_row',$t[1])
                    ->where('type', $colums[2][$obj->titolo]['tipo'])
                    ->where('head', $obj->id_riferimento)
                    ->first();
                break;
            case 3:
                $result = DB::connection('mysql_old')->table('plant_costs')
                    ->select($colums[3][$obj->titolo]['colum'].'as tot')
                    ->where('year', $t[0])
                    ->where('month', '>=', $t[1])
                    ->get();


                break;
        }

        if(!empty($result->tot)){
            $obj->valore = str_replace("-","",$result->tot);
            $obj->save();
            $message = 'Messaggi.Valore-Target-Aggiornato';
            $color = 'success';
        }else{

            $message = 'Messaggi.Valore-Target-Non-Aggiornato';
            $color = 'error';
        }

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => $color,
                'obj' => $obj
            ]
        );
    }

    public function list_agp(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $periodoBy = $request->get('periodoBy');
        $modulo = $request->get('modulo');


        if (empty($sortByName)) {
            $sortByName = 'data_riferimento';
            $orderBy = 'desc';
        }
        $objs = DB::table('targets')
            ->where('tipo', $modulo)
            ->Where(function ($query) use ($periodoBy) {
                if ($periodoBy)
                    $query->Where('data_riferimento', $periodoBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->get();

        $result = [];
        foreach ($objs as $obj){
            if(empty($result[strtotime($obj->data_riferimento)]))
                $result[strtotime($obj->data_riferimento)]['periodo'] = date('Y-F',strtotime($obj->data_riferimento));
            $result[strtotime($obj->data_riferimento)][$obj->titolo] = [ 'agp'=>$obj->target, 'value'=>$obj->valore ];
        }

        krsort($result);

        return response()->json($result);
    }

    public function save_agp(Request $request)
    {

        $t = date('Y-m-01', strtotime($request->periodo));

        if(!empty($request->edit)){
            $obj = Target::where('data_riferimento',$t)->where('titolo',$request->titolo)->where('tipo',$request->tipo)->first();
            if(empty($obj->id)){
                $obj = new Target();
                $obj->titolo = $request->titolo;
                $obj->tipo = $request->tipo;
                $obj->data_riferimento = $t;
                $obj->user = Auth::id();
            }
            $obj->valore = (!empty($request->valore) ? $request->valore:0);
            $obj->target =(!empty( $request->target) ?  $request->target: 0);
            $obj->save();
            $message = 'Messaggi.Valore-Inserito';
        }
        else{
            foreach ($request->all() as $key => $camp){

                $obj = Target::where('data_riferimento',$t)->where('titolo',$key)->where('tipo',$request->tipo)->first();
                if($key != 'id' && $key != 'periodo'){
                    if(empty($obj->id)){
                        $obj = new Target();
                        $obj->titolo = $key;
                        $obj->tipo = $request->tipo;  // AGP
                        $obj->data_riferimento = $t;
                        $obj->user = Auth::id();
                    }
                    $obj->target = $camp;
                    $obj->save();

                }
            }
            $message = 'Messaggi.Agp-Inserito';
        }




        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }
}
