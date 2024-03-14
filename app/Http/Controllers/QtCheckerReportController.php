<?php

namespace App\Http\Controllers;

use App\Models\QtCheckerReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class QtCheckerReportController extends Controller
{
    public function index(Request $request){

        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $checkerBy = $request->get('checker');
        $ordineBy = $request->get('ordine');
        if(empty($checkerBy))
            $checkerBy = Auth::id();

        if(empty($sortByName)){
            $sortByName = 'date_create';
            $orderBy = 'asc';
        }
        $objs = DB::table('qt_checker_reports')
            ->Where(function ($query) use ($ordineBy) {
                if ($ordineBy)
                    $query->Where('ol', 'LIKE','%'.$ordineBy.'%');
            })
            ->Where(function ($query) use ($checkerBy) {
                if ($checkerBy)
                    $query->Where('user', $checkerBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

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
