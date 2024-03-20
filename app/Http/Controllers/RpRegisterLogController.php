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
    public function getRegister($id){
        $success = false;

        $obj = DB::table('rp_register_logs')->select('*')
            ->where('cod_riferimento',$id)
            ->orWhere('cod_tessera',$id)
            ->where('data_scadenza','>', date('Y-m-d H:i:s'))
            ->where('attivo', 1)
            ->first();

        //Log::channel('stderr')->info(Str::uuid());
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

        $obj = new RpRegisterActivity();
        $obj->rp_register_id = $id;
        $obj->cod_riferimento = $request->cod_riferimento;
        $obj->data_azione = date('Y-m-d H:i:s');
        $obj->azione = ($request->entrata == true ? 'Entrata':'Uscita');
        $obj->save();



    }
}
