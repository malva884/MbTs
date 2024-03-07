<?php

namespace App\Http\Controllers;

use App\Models\QtCheckerReport;
use App\Models\QtFai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\QueryBuilder\QueryBuilder;

class QtFaiController extends Controller
{
    public function index(Request $request){

        $sortByName = $request->get('sort');

        $objs = QueryBuilder::for(QtFai::class)
            //->allowedFilters(['ol', AllowedFilter::exact('stage'), AllowedFilter::exact('num_fo')])
            ->defaultSort('created_at')
            ->allowedSorts($sortByName)
            ->paginate($request->get('itemsPerPage'));

        return response()->json($objs);
    }

    public function store(Request $request){

        $lastRecord = QtFai::where('anno',date('Y'))->orderBy('num', 'desc')->first()->num;


        $obj = new QtFai();
        $obj->data_creazione = date('Y-m-d H:i:s');
        $obj->anno = date('Y');
        $obj->num = $lastRecord + 1 ;
        $obj->numero_fai = $lastRecord + 1 .'-'.Date('Y');
        $obj->user = Auth::id();
        $obj->ol = $request->ol;
        $obj->cod_cavo = $request->cod_cavo;
        $obj->cod_materiale = $request->cod_materiale;
        $obj->descrizione = $request->descrizione;
        $obj->save();

        $message = 'Messaggi.Nuovo-Fai-Salvato.';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }
}
