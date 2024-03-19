<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RpRegisterLogController extends Controller
{
    public function getRegister($id){


        $obj = DB::table('rp_register_logs')->select('nome')
            ->where('cod_riferimento','385a1bc1-4f39-4cae-9607-8520edb26bc2')
            ->where('data_scadenza','>', date('Y-m-d H:i:s'))
            //->where('attivo','<>', true)
            ->get();

        //Log::channel('stderr')->info($obj);
        return response()->json($obj);
    }
    public function register($id){


    }
}
