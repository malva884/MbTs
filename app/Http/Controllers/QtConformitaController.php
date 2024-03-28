<?php

namespace App\Http\Controllers;

use App\Jobs\NonConformita;
use App\Models\QtCheckerReport;
use App\Models\QtConformita;
use App\Services\GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QtConformitaController extends Controller
{
    public function index(Request $request){

        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $ordineBy = $request->get('ordine');
        $materialeBy = $request->get('materiale');


        if(empty($sortByName)){
            $sortByName = 'data_apertura';
            $orderBy = 'asc';
        }
        $objs = DB::table('qt_conformitas')->select('qt_conformitas.*','users.full_name')
            ->join('users','users.id','qt_conformitas.user')
            ->Where(function ($query) use ($ordineBy) {
                if ($ordineBy)
                    $query->Where('ol', 'LIKE','%'.$ordineBy.'%');
            })
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('cod_materiale', 'LIKE','%'.$materialeBy.'%');
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function view($id)
    {
        $obj = DB::table('qt_conformitas')
            ->Where(function ($query) use ($id) {
                $query->where('id',$id)->orWhere('report_id',$id);
            })->first();

        return response()->json($obj);
    }

    public function store(Request $request){


        $lastRecord = QtConformita::where('anno',date('Y'))->orderBy('created_at', 'desc')->first();
        $obj = new QtConformita();
        if(!empty($request->report_id))
            $obj->report_id = $request->report_id;
        $obj->user = Auth::id();
        $obj->data_apertura = date('Y-m-d H:i:s');
        $obj->ol = $request->ol;
        if(!empty($request->num_fo))
            $obj->num_fo = $request->num_fo;
        $obj->stage = $request->stage;
        $obj->materiale = $request->materiale;
        $obj->bobina = $request->bobina;
        $obj->note = $request->note;
        $obj->macchina = $request->macchina;
        $obj->difetto = $request->difetto;
        $obj->fibre = $request->fibre;
        $obj->soluzione = $request->soluzione;
        $obj->diametro = $request->diametro;
        if(!empty($request->tipologia_fibra))
            $obj->tipologia_fibra = $request->tipologia_fibra;
        if(!empty($request->operator))
            $obj->operator = $request->operator;
        $obj->physical_l = $request->physical_l;
        $obj->optical_l = $request->optical_l;
        if(!empty($request->tipologia_difetto))
            $obj->tipologia_difetto = $request->tipologia_difetto;
        $obj->anno = date('Y');
        $obj->numero = (!empty($lastRecord->numero) ? $lastRecord->numero + 1:'00001' );
        $obj->google_drive_id = GoogleDrive::add_folder(env('ID_GOOGLE_NC_GIORNALIENRE'),$obj->ol.'-'.$obj->bobina,'google',false);
        $obj->save();

        if($obj->report_id){
            $reportChecker = QtCheckerReport::find($obj->report_id);
            $reportChecker->not_conformity = 1;
            $reportChecker->save();
        }

        dispatch(new NonConformita($obj->id,'Apertura Non Conformita'));
        $message = 'Messaggi.Non Conformita Aperta.';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'objs' => $obj
            ]
        );

    }

    public function update(Request $request, $id){

        $obj = QtConformita::find($id);
        $obj->user = Auth::id();
        $obj->note = $request->note;
        $obj->macchina = $request->macchina;
        $obj->difetto = $request->difetto;
        $obj->fibre = $request->fibre;
        $obj->soluzione = $request->soluzione;
        $obj->diametro = $request->diametro;
        if(!empty($request->tipologia_fibra))
            $obj->tipologia_fibra = $request->tipologia_fibra;
        if(!empty($request->operator))
            $obj->operator = $request->operator;
        $obj->physical_l = $request->physical_l;
        $obj->optical_l = $request->optical_l;
        if(!empty($request->tipologia_difetto))
            $obj->tipologia_difetto = $request->tipologia_difetto;
        $obj->save();

        $message = 'Messaggi.Non Conformita Modificata.';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'objs' => $obj
            ]
        );

    }

    public function closed($id)
    {
        $obj = QtConformita::find($id);
        $obj->data_chiusura = Date('Y-m-d H:i:s');
        $diff = strtotime( $obj->data_apertura." UTC") - strtotime( $obj->data_chiusura." UTC");
        $obj->time = $diff;
        $obj->chiuso = true;
        $obj->save();
        if($obj->report_id){
            $reportChecker = QtCheckerReport::find($obj->report_id);
            $reportChecker->not_conformity = 2;
            $reportChecker->save();
        }

        $message = 'Messaggi.Non Conformita Chiusa.';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'objs' => $obj
            ]
        );
    }
}
