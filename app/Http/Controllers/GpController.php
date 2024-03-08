<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GpController extends Controller
{
    public function getMateriale($ol){

        $result = DB::connection('sqlsrv_gp')->table('STL_OrdiniMaster_V')->select('STL_OrdiniMaster_V.*','STL_Materiali_V.Descrizione')
            ->join('STL_Materiali_V','STL_Materiali_V.IDProdotto','STL_OrdiniMaster_V.IDProdotto')
            ->where('STL_OrdiniMaster_V.OrdineCliente','=','0000'.$ol)
            ->first();

        return response()->json($result);
    }
}
