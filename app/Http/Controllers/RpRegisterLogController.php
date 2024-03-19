<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RpRegisterLogController extends Controller
{
    public function getRegister($id){

        Log::channel('stderr')->info($id);
        $obj = DB::table('rp_register_logs')->select('nome')->where('cod_riferimento',$id)
            ->where('data_scadenza','>', date('Y-m-d H:i:s'))
            ->where('attivo','<>', true)
            ->first();


        return response()->json($obj);
    }
    public function register($id){


    }
}
