<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Revolution\Google\Sheets\Facades\Sheets;

class DashboardProduction extends Model
{
    use HasFactory;

   static public function getProductionData($anno, $mese, $lavorazione = null)
   {
       $result = DB::connection('sqlsrv_root_gp')->table('Produzione as PRD')
           ->select('P.NomeProdotto AS Prodotto', 'DM.NRigaOrd AS Ordine', 'P.Conversione12 As NumeroFibre','UMP.UM AS UM','PRD.#Cicli as Quantita','ProdNuovi.valore as Costo')
           ->join('Dettagli_sugli_ordini as DSO', 'DSO.IDDettagliOrdini', '=', 'PRD.IDDettOrd')
           ->join('Dettagli_Master as DM','DM.idMaster','=','DSO.IDMaster')
           ->join('Prodotti as P','P.IDProdotto','=','PRD.IDArticolo')
           ->join('UM as UMP','UMP.IDUM','=','DSO.IDUM')
           ->join('Risorse as R','R.IDRisorsa','=','PRD.IDRis')
           ->join('GP_NX_AGG.dbo.AGG_PRODOTTI_TMP AS ProdNuovi','ProdNuovi.cdProdotto','P.NomeProdotto')
           ->join("GP_NX_AGG.dbo.AGG_DETTAGLI_TMP as DT1",function($join){
               $join->on("DT1.cdOrdine","=",DB::raw("replace(DM.NRigaOrd, '00009', '9')"))
                   ->on("DSO.CodPrel","=","DT1.numFase");
           })
           ->whereYear('PRD.DataOraInizio',$anno)
           ->whereMonth('PRD.DataOraInizio', $mese)

           ->whereIn('DT1.ControlKey',['PP03','ZP03'])
           ->where('PRD.Confermato',1)
           ->where('PRD.Significativo',1)
           ->where('PRD.IdSchedaPrdOrdineAcc',0)
           ->where('PRD.#Cicli','>',0)
           ->get();

       $report['production_ckm_ottico_totale'] = 0;
       $report['production_kfkm_ottico_totale'] = 0;
       $report['production_ckm_rame_totale'] = 0;
       $report['production_valore_rame_totale'] = 0;
       $report['production_valore_ottico_totale'] = 0;
       $report['production_fkm_ottico_totale'] = 0;

       foreach ($result as $ordine) {
           $tmp[1] = substr($ordine->Prodotto, 0, 2);
           $tmp[2] = substr($ordine->Ordine, 0, 2);
           if($tmp[2] != 94 AND ($tmp[1] == 'SF' || $tmp[1] == 'FC' || $tmp[1] == 'F8')){
               $report['production_ckm_ottico_totale'] =  round($report['production_ckm_ottico_totale'] + $ordine->Quantita, 0);
               $report['production_kfkm_ottico_totale'] =  $report['production_kfkm_ottico_totale'] + round($ordine->Quantita * $ordine->NumeroFibre, 0);
               $report['production_valore_ottico_totale'] =  round($report['production_valore_ottico_totale'] + ($ordine->Quantita * $ordine->Costo),2);
           }elseif($tmp[1] == 'F5' || $tmp[1] == 'F6' || $tmp[1] == 'FJ'){
               $report['production_ckm_rame_totale'] =  round($report['production_ckm_rame_totale'] + $ordine->Quantita, 0);
               $report['production_valore_rame_totale'] = round($report['production_valore_rame_totale'] + ($ordine->Quantita * $ordine->Costo),2);
           }
       }

       $report['production_Fkm_ottico_totale'] = $report['production_kfkm_ottico_totale'];
       $report['production_kfkm_ottico_totale'] = round($report['production_kfkm_ottico_totale'] / 1000,0);
       $report['production_ckm_rame_totale'] = round($report['production_ckm_rame_totale'] / 1000,2);
       $report['production_ckm_ottico_totale'] = round( $report['production_ckm_ottico_totale'], 0);
       $report['production_valore_rame_totale'] = round($report['production_valore_rame_totale']/ 1000000,2);
       $report['production_valore_ottico_totale'] = round($report['production_valore_ottico_totale']/ 1000000,2);

       return $report;
   }

    static public function getDispatchData($anno, $mese){

        $objs = DB::table('targets')
            ->select('titolo','valore','target')
            ->where('tipo', 2)
            ->whereYear('data_riferimento', $anno)
            ->whereMonth('data_riferimento', $mese)
            ->whereIn('titolo', ['ckm_ofc','ckm_cc','fkm_ofc','value_ofc','value_cc'])
            ->get();

        $report = [];
        if(!$objs->count()){
            $report['ckm_ofc'] = $report['ckm_cc'] = $report['fkm_ofc'] = $report['value_ofc'] = $report['value_cc'] = [
                'valore' => 0,
                'target' => 0
            ];
        }
        foreach ($objs as $obj){
            if($obj->titolo == 'fkm_ofc' ){
                $report[$obj->titolo]= [
                    'valore' => round($obj->valore / 1000, 0),
                    'target' => round($obj->target / 1000, 0)
                ];
            }else{
                $report[$obj->titolo]= [
                    'valore' => round($obj->valore, 0),
                    'target' => round($obj->target, 0)
                ];
            }

        }


        return $report;
    }

    static public function getRevenueData($anno, $mese){

        $objs = DB::table('targets')
            ->select('titolo','valore','target')
            ->where('tipo', 1)
            ->whereYear('data_riferimento', $anno)
            ->whereMonth('data_riferimento', $mese)
            ->whereIn('titolo', ['ckm_ofc','ckm_cc','fkm_ofc','value_ofc','value_cc'])
            ->get();

        $report = [];
        if(!$objs->count()){
            $report['ckm_ofc'] = $report['ckm_cc'] = $report['fkm_ofc'] = $report['value_ofc'] = $report['value_cc'] = [
                'valore' => 0,
                'target' => 0
            ];
        }
        foreach ($objs as $obj){
            if($obj->titolo == 'fkm_ofc' ){
                $report[$obj->titolo]= [
                    'valore' => round($obj->valore / 1000, 0),
                    'target' => round($obj->target / 1000, 0)
                ];
            }else{
                $report[$obj->titolo]= [
                    'valore' => round($obj->valore, 0),
                    'target' => round($obj->target, 0)
                ];
            }

        }


        return $report;
    }

    static public function getTarget($anno, $mese, $tipo){

        $objs = DB::table('targets')
            ->select('titolo','valore','target')
            ->where('tipo', $tipo)
            ->whereYear('data_riferimento', $anno)
            ->whereMonth('data_riferimento', $mese)
            ->whereIn('titolo', ['ckm_ofc','ckm_cc','fkm_ofc','value_ofc','value_cc'])
            ->get();

        $report = [];
        if(!$objs->count()){
            $report['ckm_ofc'] = $report['ckm_cc'] = $report['fkm_ofc'] = $report['value_ofc'] = $report['value_cc'] = [
                'valore' => 0,
                'target' => 0
            ];
        }
        foreach ($objs as $obj){
            if($obj->titolo == 'fkm_ofc' ){
                $report[$obj->titolo]= [
                    'valore' => round($obj->valore / 1000, 0),
                    'target' => round($obj->target / 1000, 0)
                ];
            }else{
                $report[$obj->titolo]= [
                    'valore' => round($obj->valore, 0),
                    'target' => round($obj->target, 0)
                ];
            }

        }


        return $report;
    }

    static public function getTargetProduction($anno, $mese, $tipo){

        $objs = DB::table('targets')
            ->select('titolo','valore','target')
            ->where('tipo', $tipo)
            ->whereYear('data_riferimento', $anno)
            ->whereMonth('data_riferimento', $mese)
            //->whereIn('titolo', ['ckm_ofc','ckm_cc','fkm_ofc','value_ofc','value_cc'])
            ->get();

        $report = [];

        foreach ($objs as $obj){
            $report[$obj->titolo]= [
                'valore' => round($obj->valore, 0),
                'target' => round($obj->target, 0)
            ];
        }

        return $report;
    }
}
