<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MachineryController extends Controller
{
    public function get_list(Request $request)
    {
        $attivo = null;
        if(!empty($request->attivo) && $request->attivo === true)
            $attivo = true;
        elseif(!empty($request->attivo) && $request->attivo === false)
            $attivo = false;

        $objs = DB::table('machineries')->select('id','nome')
            ->Where(function ($query) use ($attivo) {
                if ($attivo)
                    $query->Where('attivo',$attivo);
            })
            ->get();

        return response()->json($objs);
    }
}
