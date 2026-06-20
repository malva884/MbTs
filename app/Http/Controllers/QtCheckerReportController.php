<?php

namespace App\Http\Controllers;

use App\Exports\CheckerReportExport;
use App\Models\QtCheckerReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;


class QtCheckerReportController extends Controller
{
    public function index(Request $request){

        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $checkerBy = $request->get('checker');
        $ordineBy = $request->get('ordine');
        $dataBy = $request->get('data');
        $lavorazinoeBy = $request->get('lavorazione');

        if(!Auth::user()->hasPermissionTo('qt.checker.report.admin') && empty($checkerBy))
            $checkerBy = Auth::id();
        else
            $checkerBy = null;


        if(empty($sortByName)){
            $sortByName = 'date_create';
            $orderBy = 'asc';
        }
            $objs = DB::table('qt_checker_reports')
            ->Where(function ($query) use ($lavorazinoeBy) {
                if ($lavorazinoeBy)
                    $query->Where('lavorazione', $lavorazinoeBy);
            })
            ->Where(function ($query) use ($ordineBy) {
                if ($ordineBy)
                    $query->Where('ol', 'LIKE','%'.$ordineBy.'%');
            })
            ->Where(function ($query) use ($checkerBy) {
                if ($checkerBy )
                    $query->Where('user', $checkerBy);
            })
            ->Where(function ($query) use ($dataBy) {
                if (is_string($dataBy)) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2){
                        $dataBy[0] = $dataBy[0].' 00:00:00.000';
                        $dataBy[1] = $dataBy[1].' 23:59:59.999';
                        $query->whereBetween('date_create', $dataBy);
                    }
                    else{
                        $query->whereDate('date_create', $dataBy[0]);
                    }
                }
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
                $obj->lavorazione = $request->lavorazione;
                $obj->tipo_cavo = $request->tipo_cavo;
                $obj->num_fo = $request->num_fo;
                $obj->fo_try = $request->fo_try;
                $obj->stage = $request->stage;
                $obj->coil = $coil['coil_t'];
                $obj->fo_try = $coil['fo_try'];
                $obj->km = $coil['km'];
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
            $obj->km =  $request['coils'][0]['km'];
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

    public function update(Request $request,$id){
        $obj = QtCheckerReport::find($id);
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

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'objs' => $objs
            ]
        );
    }

    public function deleted($id)
    {

        $message = 'Messaggi.Errore-Eliminazione-Rapportino';
        $color = 'error';
        $obj = QtCheckerReport::find($id);
        $success = false;
        if(!empty($obj->id)){
            if($obj->delete()){
                $message = 'Messaggi.Rapportino-Eliminato';
                $color = 'success';
                $success = true;
            }
        }

        return response()->json(
            [
                'success' => $success,
                'message' => $message ,
                'color' => $color,
            ]
        );

    }

    public function report_stage(Request $request)
    {
        $dataBy = $request->get('dataFilter');
        $userBy = $request->get('userId');
        $lavorazioneBy = $request->get('lavorazione');

        if(!$userBy && !Auth::user()->hasPermissionTo('qt.checker.report.admin'))
            $userBy = Auth::id();
        $objs = DB::table('qt_checker_reports')
            ->select(DB::raw('count(*) as totale'),DB::raw('SUM(km) as km'),'stage')
            ->where('lavorazione',$lavorazioneBy)
            ->Where(function ($query) use ($userBy) {
                if ($userBy) {
                    $query->Where('user', $userBy);
                }
            })
            ->Where(function ($query) use ($dataBy) {
                if (is_string($dataBy)) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2){
                        $dataBy[0] = $dataBy[0].' 00:00:00.000';
                        $dataBy[1] = $dataBy[1].' 23:59:59.999';
                        $query->whereBetween('date_create', $dataBy);
                    }
                    else{
                        $query->whereDate('date_create', $dataBy[0]);
                    }
                }
            })
            ->groupBy('stage')
            ->get();

        return response()->json($objs);

    }

    public function export(Request $request)
    {
        $name_file = date('dmY').'.xlsx';

        $export = new CheckerReportExport($request->checker, $request->ol, $request->periodo, $request->lavorazione);

        return Excel::download($export, $name_file);

    }
}
