<?php

namespace App\Http\Controllers;

use App\Models\QtCheckerReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use phpseclib3\Math\BigInteger\Engines\PHP\Reductions\Barrett;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class QtCheckerReportController extends Controller
{
    public function index(Request $request){

        $objs = QueryBuilder::for(QtCheckerReport::class)
            ->allowedFields(['id', 'date_create', 'ol', 'num_fo', 'fo_try','stage','coil','fo_try'])
            //->allowedFilters(['full_name', AllowedFilter::exact('stato'), AllowedFilter::exact('role')])
            ->defaultSort('-date_create','ol','coil')
            ->allowedSorts('date_create','ol','coil')
            ->paginate($request->get('perPage', 50));

        return response()->json($objs);
    }

    public function store(Request $request){

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
        }

        return response()->json(
            [
                'success' => true,
                'message' => 'Messaggi.Rapportino-Salvato.',
                'color' => 'success'
            ]
        );



    }

    public function update(Request $request){

    }
}
