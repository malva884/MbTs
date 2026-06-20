<?php

namespace App\Http\Controllers;

use App\Models\ToQuoteCable;
use App\Models\ToQuoteCableStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ToQuoteCableStructureController extends Controller
{
    public function view($id)
    {

        #$objs = ToQuoteCableStructure::where('cavo_id', $id)->orderby('posizione', 'asc')->get();
        $objs = DB::table('to_quote_cable_structures')
            ->leftJoin('to_center_costs','to_quote_cable_structures.centro','to_center_costs.centro')
            ->leftJoin('to_materials','to_quote_cable_structures.materiale','to_materials.materiale')
            ->select('to_quote_cable_structures.*','to_center_costs.id as centro_check','to_materials.id as matariale_check', DB::raw('IIF(peso = 0.00, 0.22, peso) as  peso_mat'))
            ->where('cavo_id', $id)
            ->orderby('posizione', 'asc')
            ->get();

        $checkCentro = $objs->whereNotNull('centro')->whereNull('centro_check')->first();
        $checkMateriale = $objs->whereNotNull('materiale')->whereNull('matariale_check')->first();


        return response()->json(['objs' => $objs, 'checkMateriale' => !empty($checkMateriale), 'checkCentro' => !empty($checkCentro)]);

        #return response()->json($objs);
    }

    public function stored(Request $request, $pid, $cid)
    {
        $quote_cable = DB::table('to_quote_cables')->select('metri')->where('id','=',$cid)->first();
        $rows = ToQuoteCableStructure::where('cavo_id', $cid)->orderby('posizione', 'asc')->get();

        $obj = new ToQuoteCableStructure();
        $obj->cavo_id = $cid;
        $obj->centro = $request->centro;
        $centro = DB::table('to_center_costs')->select('costo')->where('centro','=',$request->centro)->first();
        $obj->costo_centro = $centro->costo;
        $obj->materiale = $request->materiale;
        $obj->descrizione = $request->descrizione;
        $obj->diametro = $request->diametro;
        $obj->peso = $request->peso;
        $obj->ordinata = $request->ordinata;
        $obj->elementi = $request->elementi;
        if(!empty($obj->centro) && !empty($obj->elementi) && !empty($obj->ordinata))
            $obj->costo_lavorazione = round((($obj->costo_centro / $obj->ordinata) * $obj->elementi) / 1000,4);
        else
            $obj->costo_lavorazione = 0;
        $obj->posizione = $request->posizione;

        $obj->ore_macchina = round((($quote_cable->metri / $obj->ordinata) * $obj->elementi) / 1000, 1);
        $obj->nota = $request->nota;
        $obj->save();

        $i = 1;
        foreach ($rows as $row) {
            if ($row->posizione == $request->posizione) {
                $i++;
                $row->posizione = $i;
            } else {
                $row->posizione = $i;
            }
            $row->save();
            $i++;
        }

        $message = 'Messaggi.Elemento-Aggiunto';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function update(Request $request, $pid, $cid, $rid)
    {

        $obj = ToQuoteCableStructure::where('cavo_id',$cid)->where('id',$rid)->first();
        $quote_cable = DB::table('to_quote_cables')->select('metri')->where('id','=',$obj->cavo_id)->first();

        if( $obj->centro != $request->centro){
            $obj->centro = $request->centro;
            $centro = DB::table('to_center_costs')->select('costo')->where('centro','=',$request->centro)->first();
            $obj->costo_centro = $centro->costo;
            $obj->costo_lavorazione = round((($obj->costo_centro / $obj->ordinata) * $obj->elementi) / 1000,4);
        }
        //$obj->centro = $request->centro;
        $obj->descrizione = $request->descrizione;
        if($obj->materiale != $request->materiale){
            $obj->materiale = $request->materiale;
            $mp = DB::table('to_materials')->select('descrizione','costo')->where('materiale','=',$request->materiale)->first();
            //$obj->descrizione = $mp->descrizione;

            $obj->costo = $mp->costo;
            if($obj->peso)
                $obj->costo_materia_prima = round(($obj->peso * $obj->costo) / 1000, 4);
        }

        //$obj->descrizione = $request->descrizione;
        $obj->diametro = $request->diametro;
        if($obj->peso != $request->peso){
            $obj->peso = $request->peso;
            $obj->costo_materia_prima = round(( $request->peso * $obj->costo) / 1000, 4);
        }
        //$obj->peso = $request->peso;
        if( $obj->ordinata != $request->ordinata){
            $obj->ordinata = $request->ordinata;
            $obj->costo_lavorazione = round((($obj->costo_centro / $obj->ordinata) * $obj->elementi) / 1000,4);
            //$obj->ore_macchina = round((($quote_cable->metri / $obj->ordinata) * $obj->elementi) / 1000, 2) ;
        }
        //$obj->ordinata = $request->ordinata;
        $obj->elementi = $request->elementi;
        if(!empty($obj->centro) && !empty($obj->elementi) && !empty($obj->ordinata))
            $obj->costo_lavorazione = round((($obj->costo_centro / $obj->ordinata) * $obj->elementi) / 1000,4);
        else
            $obj->costo_lavorazione = 0;

        $obj->posizione = $request->posizione;
        if(!empty($obj->centro))
            $obj->ore_macchina = round((($quote_cable->metri / $obj->ordinata) * $obj->elementi) / 1000, 2);
        $obj->nota = $request->nota;
        $obj->save();


        if( $obj->posizione != $request->posizione){
            $rows = ToQuoteCableStructure::where('cavo_id', $request->cavo_id)->orderby('posizione', 'asc')->get();

            $i = 1;
            foreach ($rows as $row) {
                if($row->id != $obj->id){
                    $row->posizione = $i;
                    $row->save();
                }
                $i++;



                if ($request->posizione < $obj->posizione) {
                    if ($row->posizione == $request->posizione) {
                        $i = $row->posizione = $i + 1;
                        $i++;
                    } else {
                        $row->posizione = $i;
                        $i++;
                    }
                } elseif ($request->posizione > $obj->posizione) {
                    if($i == $request->posizione)
                        $i++;

                    if ($row->posizione == $request->posizione) {

                        $i = $row->posizione = $i;

                        $i++;
                    } else {
                        $row->posizione = $i;
                        $i++;
                    }
                }
                $row->save();
            }
            $obj->posizione = $request->posizione;
        }
        //$obj->posizione = $request->posizione;
        //$obj->costo_lavorazione = $request->costo_lavorazione;
        if(!empty($obj->centro))
            $obj->ore_macchina = round((($quote_cable->metri / $obj->ordinata) * $obj->elementi) / 1000, 2);
        $obj->nota = $request->nota;
        $obj->save();


        $cavo = ToQuoteCable::find($obj->cavo_id);
        $cavo->calcola_totali();

        $message = 'Messaggi.Elemento-Modificato';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => $obj
            ]
        );

        return response()->json($obj);
    }

    public function deleted(Request $request, $pid, $cid, $rid)
    {
        $obj = ToQuoteCableStructure::find($request->id);
        $obj->delete();

        $rows = ToQuoteCableStructure::where('cavo_id', $request->cavo_id)->orderby('posizione', 'asc')->get();

        $i = 1;
        foreach ($rows as $row) {
            if ($row->posizione == $request->posizione) {
                $i++;
                $row->posizione = $i;
            } else {
                $row->posizione = $i;
            }
            $row->save();
            $i++;
        }

        $message = 'Messaggi.Riga-Eliminata';
        $color = 'success';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => $color,
            ]);
    }
}
