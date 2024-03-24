<?php

namespace App\Http\Controllers;

use App\Models\RpRegisterActivity;
use App\Models\RpRegisterLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RpRegisterLogController extends Controller
{
    public function list(Request $request){
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $visitatore = $request->get('visitatore');
        $azienda = $request->get('azienda');
        $data = $request->get('data');

        if(empty($sortByName)){
            $sortByName = 'data_prevista';
            $orderBy = 'desc';
        }
        $objs = DB::table('rp_register_logs')->select('rp_register_logs.*','users.full_name')
            ->join('users','users.id','rp_register_logs.user')
            ->Where(function ($query) use ($visitatore) {
                $query->where('rp_register_logs.nome','LIKE', '%'.$visitatore.'%');
            })
            ->Where(function ($query) use ($azienda) {
                $query->where('rp_register_logs.azienda','LIKE', '%'.$azienda.'%');
            })

            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }
    public function getRegister($id){
        $success = false;

        $obj = DB::table('rp_register_logs')->select('*')
            ->where('data_scadenza','>', date('Y-m-d H:i:s'))
            ->where('attivo', 1)
            ->Where(function ($query) use ($id) {
                $query->where('cod_riferimento',$id)->orWhere('cod_tessera',$id);
            })
            ->first();


        if(!empty($obj->id))
            $success = true;

        return response()->json(
            [
                'success' => $success,
                'obj' => $obj
            ]
        );

        //Log::channel('stderr')->info(Str::uuid());

    }
    public function storeRegister(Request $request,$id){

        $success = true;
        $obj = new RpRegisterActivity();
        $obj->rp_register_id = $id;
        $obj->cod_riferimento = $request->cod_riferimento;
        $obj->data_azione = date('Y-m-d H:i:s');
        $obj->azione = ($request->entrata == true ? 'Entrata':'Uscita');
        $obj->save();
        $code = '';
        if($request->entrata == true && !$request->cod_tessera){
            $registerLog =  RpRegisterLog::find($request->id);
            $registerLog->cod_tessera = Str::uuid();
            $registerLog->save();
            $code = $registerLog->cod_tessera;
        }elseif($request->entrata == true && $request->cod_tessera){
            $code = $request->cod_tessera;
        }

        return response()->json(
            [
                'success' => $success,
                'code' => $code
            ]
        );

    }
}
