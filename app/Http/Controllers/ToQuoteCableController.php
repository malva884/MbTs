<?php

namespace App\Http\Controllers;

use App\Models\ToCable;
use App\Models\ToQuoteCable;
use App\Models\ToQuoteCableStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ToQuoteCableController extends Controller
{
    public function list(Request $request, $id)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');

        if (empty($sortByName)) {
            $sortByName = 'posizione';
            $orderBy = 'asc';
        }

        $objs = ToQuoteCable::where('preventivo_id',$id)
            ->orderBy($sortByName, $orderBy)
            ->paginate(100);



        return response()->json($objs);
    }

    public function view($id)
    {
        $obj = ToQuoteCable::where('id', $id)->first();
        $obj->preventivo_obj;
        $obj->preventivo_obj->cliente_obj;

        return response()->json($obj);
    }

    public function stored(Request $request, $id)
    {
        $cavo = ToCable::find($request->codice);
        $struttura = DB::table('to_cable_structures')
            ->select('to_cable_structures.*','to_center_costs.costo as costoCentro','to_materials.costo as costo_materia','to_center_costs.id as centro_check','to_materials.id as matariale_check')
            ->leftJoin('to_center_costs','to_center_costs.centro','to_cable_structures.centro')
            ->leftJoin('to_materials','to_materials.materiale','to_cable_structures.materiale')
            ->where('cavo_id','=',$request->codice)->get();

        $obj = new ToQuoteCable();
        $obj->preventivo_id = $request->preventivo['id'];
        $obj->cavo_id = $request->codice;
        $obj->codice = $cavo->codice;
        $obj->descrizione = $request->descrizione;
        $obj->scarto = $request->scarto;
        $obj->metri = $request->metri;
        $obj->diametro = $request->diametro;
        $obj->pezzatura = $request->pezzatura;
        $obj->bobina_id = $request->bobina['id'];
        $obj->bobina = $request->bobina['bobina'];
        $obj->bobina_numero = $obj->metri / $obj->pezzatura;
        $obj->peso = $request->bobina['peso'];
        $obj->m3 = $request->bobina['m3'];
        $obj->m3_totale = round($request->m3 * $obj->bobina_numero,2);
        $obj->totale_costo_bobine = round($request->bobina['costo'] * $obj->bobina_numero,4);
        $obj->costo_bobina = $request->bobina['costo'];
        $obj->posizione = $request->posizione;
        $obj->save();

        foreach ($struttura as $row){
            $obj_struct = New ToQuoteCableStructure();
            $obj_struct->cavo_id = $obj->id;
            $obj_struct->posizione = $row->posizione;
            $obj_struct->centro = $row->centro;
            $obj_struct->materiale = $row->materiale;
            $obj_struct->descrizione = $row->descrizione;
            $obj_struct->diametro = $row->diametro;
            $obj_struct->peso = $row->peso;
            $obj_struct->nota = $row->nota;

            $obj_struct->ordinata = $row->ordinata;
            $obj_struct->elementi = $row->elementi;
            $obj_struct->costo_centro = (!empty($row->costoCentro) ? $row->costoCentro : 0.00);
            $obj_struct->costo = (!empty($row->costo_materia) ? $row->costo_materia : 0.00);
            if($obj_struct->peso)
                $obj_struct->costo_materia_prima = round(($obj_struct->peso * $obj_struct->costo) / 1000, 4);
            if(!empty($obj_struct->costo_centro))
                $obj_struct->ore_macchina = round((($obj->metri / $obj_struct->ordinata) * $obj_struct->elementi) / 1000, 2) ;
            if(!empty($obj_struct->costo_centro) && !empty($obj_struct->ordinata))
                $obj_struct->costo_lavorazione = round((($obj_struct->costo_centro / $obj_struct->ordinata) * $obj_struct->elementi) / 1000,4);
            $obj_struct->save();
        }
        $obj->calcola_totali();

        $checkCentro = $struttura->whereNotNull('centro')->whereNull('centro_check')->first();
        $checkMateriale = $struttura->whereNotNull('materiale')->whereNull('matariale_check')->first();

        $objs = ToQuoteCable::where('preventivo_id', $obj->preventivo_id)->orderby('posizione', 'asc')->orderby('updated_at','desc')->get();

        $i = 0;
        foreach ($objs as $obj) {
            $i++;
            $obj->posizione = $i;
            $obj->save();
        }

        $message = 'Messaggi.Cavo-Aggiunto';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => $obj,
                'checkMateriale' => !empty($checkMateriale),
                'checkCentro' => !empty($checkCentro)
            ]
        );
    }

    public function update(Request $request, $id, $cid)
    {
        $obj = ToQuoteCable::find($cid);
        $obj->scarto = $request->scarto;
        $obj->descrizione = $request->descrizione;
        $obj->metri = $request->metri;
        $obj->diametro = $request->diametro;
        $obj->pezzatura = $request->pezzatura;
        $obj->bobina_id = $request->bobina['id'];
        $obj->bobina = $request->bobina['bobina'];
        $obj->bobina_numero = $obj->metri / $obj->pezzatura;
        $obj->peso = $request->bobina['peso'];
        $obj->m3 = $request->bobina['m3'];
        $obj->m3_totale = round($request->m3 * $obj->bobina_numero,2);
        $obj->totale_costo_bobine = round($request->bobina['costo'] * $obj->bobina_numero,4);
        $obj->costo_bobina = $request->bobina['costo'];
        $obj->posizione = $request->posizione;
        $obj->save();

        $obj->calcola_totali();

        $objs = ToQuoteCable::where('preventivo_id', $obj->preventivo_id)->orderby('posizione', 'asc')->orderby('updated_at','desc')->get();

        $i = 0;
        foreach ($objs as $obj) {
            $i++;
            $obj->posizione = $i;
            $obj->save();
        }

        $message = 'Messaggi.Cavo-Modificato';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function deleted(Request $request, $pid, $cid)
    {
        $obj = ToQuoteCable::find($cid);
        $obj->delete();

        $objs = ToQuoteCable::where('preventivo_id', $pid)->orderby('posizione', 'asc')->get();

        $i = 0;
        foreach ($objs as $obj) {
            $i++;
            $obj->posizione = $i;
            $obj->save();
        }

        $message = 'Messaggi.Cavo-Eliminato';
        $color = 'success';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => $color,
            ]);
    }
}
