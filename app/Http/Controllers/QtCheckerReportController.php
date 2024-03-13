<?php

namespace App\Http\Controllers;

use App\Models\QtCheckerReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use phpseclib3\Math\BigInteger\Engines\PHP\Reductions\Barrett;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class QtCheckerReportController extends Controller
{
    public function index(Request $request){

       $request->filter = json_decode($request->filter,true);
        if(empty($request->filter['user']))
            $request->filter['user'] = Auth::id();


        $sortByName = $request->get('sort');
        Log::channel('stderr')->info($request->all());
        $objs = QueryBuilder::for(QtCheckerReport::class)
            ->where('user', $request->filter['user'])

            ->defaultSort('date_create')
            ->allowedSorts($sortByName)
            ->paginate($request->get('itemsPerPage'))
            ->withQueryString();
        Log::channel('stderr')->info($objs);
        return response()->json($objs);
    }

    public function store(Request $request){

        $objs = [];
        if(empty($request->id)){
            foreach ($request->coils as $coil){
                $obj = new QtCheckerReport();
                $obj->date_create = date('Y-m-d H:i:s');
                $obj->user = Auth::id();
                $obj->ol = $request->ol;
                $obj->num_fo = $request->num_fo;
                $obj->fo_try = $request->fo_try;
                $obj->stage = $request->stage;
                $obj->coil = $coil['coil_t'];
                $obj->fo_try = $coil['fo_try'];
                $obj->not_conformity = false;
                $obj->note = $request->note;
                $obj->save();
                $objs[] = $obj;
            }
            $message = 'Messaggi.Rapportino-Salvato.';
        }else{
            $obj = QtCheckerReport::find($request->id);
            $obj->ol = $request->ol;
            $obj->num_fo = $request->num_fo;
            $obj->fo_try = $request->fo_try;
            $obj->stage = $request->stage;
            $obj->coil = $request['coils'][0]['coil_t'];
            $obj->fo_try = $request['coils'][0]['fo_try'];
            $obj->not_conformity = false;
            $obj->note = $request->note;
            $obj->save();
            $objs[] = $obj;
            $message = 'Messaggi.Rapportino-Modificato.';
        }


        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'objs' => $objs
            ]
        );
    }

    public function update(Request $request){

    }
}
