<?php

namespace App\Http\Controllers;

use App\Models\QtConformita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QtConformitaController extends Controller
{
    public function store(Request $request){

        $obj = new QtConformita();
        if(!empty($request->report_id))
            $obj->report_id = $request->report_id;
        $obj->user = Auth::id();
        $obj->data_apertura = data('Y-m-d H:i:s');
        $obj->ol = $request->ol;
        if(!empty($request->num_fo))
            $obj->num_fo = $request->num_fo;
        $obj->stage = $request->stage;
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
        $obj->save();


    }
}
