<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KpiController extends Controller
{
    public function report(Request $request)
    {
        $periodo = $request->periodo;
        $anno = explode('-', $request->periodo)[0];
        $mese = explode('-', $request->periodo)[1];
        if($mese <= 3){
            $annoDa = $anno - 1;
            $meseDa = '04';
            $annoA = $anno;
            $meseA = '03';
        }else{
            $annoDa = $anno;
            $meseDa = '04';
            $annoA = $anno + 1;
            $meseA = '03';
        }
        $result = [
            'cc_ckm_data' => 0,
            'cc_ckm_fatturato' => 0,
            'cc_ckm_prodotto' => 0,
            'cc_ckm_mese_fatturato' => 0,
            'cc_ckm_mese_prodotto' => 0,
            'cc_power_cost' => 0,
            'cc_power_data' => 0,
            'cc_ckm_power' => 0,
            'cc_ckm_power_mese'=>0,
            'cc_power_cost_mese'=>0,
            'cc_costo_personale'=>0,
            'cc_costo_personale_data'=> 0,
            'ofc_kfkm_data' => 0,
            'ofc_kfkm_fatturato' => 0,
            'ofc_kfkm_prodotto' => 0,
            'ofc_kfkm_mese_fatturato' => 0,
            'ofc_kfkm_mese_prodotto' => 0,
            'ofc_power_cost' => 0,
            'ofc_power_data' => 0,
            'ofc_kfkm_power' => 0,
            'ofc_kfkm_power_mese'=>0,
            'ofc_power_cost_mese'=>0,
            'ofc_costo_personale'=>0,
            'ofc_costo_personale'=>0,
            'ofc_costo_personale_data'=> 0,
        ];

        $rame_ckm = $this->getdata($annoDa,$meseDa,$annoA,$meseA,'cc_ckm_production','desc',true);
        $rame_ckm_mese = $this->getdata($anno,$mese,null,null,'cc_ckm_production','desc',false);
        $energia_tot = $this->getdata($annoDa,$meseDa,$annoA,$meseA,'of_eletric_power','asc',true);
        $rame_costo_personale = $this->getdata($annoDa,$meseDa,$annoA,$meseA,'cc_cmk_employee','asc',true);

        //$energia_mese_tot = $this->getdata($anno,$mese,null,null,'of_eletric_power','desc',false);

        if(!empty($energia_tot->year) && !empty($energia_tot->month)){
            $rame_ckm_power = $this->get_terget($energia_tot->year, $energia_tot->month, 'ckm_cc', 1);
            $ottico_ckm_power = $this->get_terget($energia_tot->year, $energia_tot->month, 'fkm_ofc', 1);
            $result['cc_ckm_power'] = @round($rame_ckm_power->valore,0);
            $result['ofc_kfkm_power'] = round(@$ottico_ckm_power->valore / 1000, 0);
            $result['cc_power_data'] = @$energia_tot->month.' '.@$energia_tot->year;
            $result['cc_power_cost'] = round(((@$energia_tot->of_eletric_power * 30) / 100) / 1000);
            $result['ofc_power_data'] = @$energia_tot->month.' '.@$energia_tot->year;
            $result['ofc_power_cost'] = round(((@$energia_tot->of_eletric_power * 70) / 100) / 1000);
        }

       // if(!empty($energia_mese_tot->year) && !empty($energia_mese_tot->month)){
            $rame_ckm_power = $this->get_terget($anno,$mese, 'ckm_cc', 1);
            $ottico_ckm_power = $this->get_terget($anno,$mese, 'fkm_ofc', 1);
            $result['cc_ckm_power_mese'] = round( @$rame_ckm_power->valore, 0);
            $result['ofc_kfkm_power_mese'] = round(@$ottico_ckm_power->valore / 1000, 0);
            //$result['cc_power_cost_mese'] = round(((@$energia_mese_tot->of_eletric_power * 30) / 100) / 1000);
            //$result['ofc_power_cost_mese'] = round(((@$energia_mese_tot->of_eletric_power * 70) / 100) / 1000);
       // }


        $result['cc_ckm_data'] = $rame_ckm->month.' '.$rame_ckm->year;
        $result['cc_ckm_prodotto'] = $rame_ckm->cc_ckm_production;
        $result['cc_ckm_mese_prodotto'] = $rame_ckm_mese->cc_ckm_production;
        $result['cc_costo_personale_data'] = $rame_costo_personale->month.' '.$rame_costo_personale->year;
        $result['cc_costo_personale'] = $rame_costo_personale->cc_cmk_employee;



        $fatturato_rame_ckm = DB::table('targets')
            ->select(DB::raw('SUM(valore) as ckm_valore'), DB::raw('SUM(target) as ckm_target'))
            ->where('tipo', 1)
            ->where('titolo', 'ckm_cc')
            ->where('data_riferimento', $rame_ckm->year.'-'.$rame_ckm->month.'-01')
            ->first();
        $fatturato_rame_ckm_mese = DB::table('targets')
            ->select(DB::raw('SUM(valore) as ckm_valore'), DB::raw('SUM(target) as ckm_target'))
            ->where('tipo', 1)
            ->where('titolo', 'ckm_cc')
            ->where('data_riferimento', $anno.'-'.$mese.'-01')
            ->first();
        $result['cc_ckm_fatturato'] =  round($fatturato_rame_ckm->ckm_valore,0);
        $result['cc_ckm_mese_fatturato'] =  round($fatturato_rame_ckm_mese->ckm_valore,0);

        $ottico_fkm = $this->getdata($annoDa,$meseDa,$annoA,$meseA,'of_kfkm_production','desc',true);
        $ottico_ckm_mese = $this->getdata($anno,$mese,null,null,'of_kfkm_production','desc',false);
        $ottico_costo_personale = $this->getdata($annoDa,$meseDa,$annoA,$meseA,'of_cmk_employee','asc',true);




        $result['ofc_kfkm_data'] = $ottico_fkm->month.' '.$ottico_fkm->year;
        $result['ofc_kfkm_prodotto'] =  $ottico_fkm->of_kfkm_production;
        $result['ofc_kfkm_mese_prodotto'] =  $ottico_ckm_mese->of_kfkm_production;
        $result['ofc_costo_personale_data'] = $ottico_costo_personale->month.' '.$ottico_costo_personale->year;
        $result['ofc_costo_personale'] = $ottico_costo_personale->of_cmk_employee;


        $fatturato_ottico_kfm = DB::table('targets')
            ->select(DB::raw('SUM(valore) as fkm_valore'), DB::raw('SUM(target) as fkm_target'))
            ->where('tipo', 1)
            ->where('titolo', 'fkm_ofc')
            ->whereYear('data_riferimento', $ottico_fkm->year)
            ->whereMonth('data_riferimento', $ottico_fkm->month)
            ->first();

        $fatturato_ottico_kfm_mese = DB::table('targets')
            ->select(DB::raw('SUM(valore) as fkm_valore'), DB::raw('SUM(target) as fkm_target'))
            ->where('tipo', 1)
            ->where('titolo', 'fkm_ofc')
            ->whereYear('data_riferimento', $anno)
            ->whereMonth('data_riferimento', $mese)
            ->first();
        $result['ofc_kfkm_fatturato'] = round($fatturato_ottico_kfm->fkm_valore / 1000,0);
        $result['ofc_kfkm_mese_fatturato'] = round($fatturato_ottico_kfm_mese->fkm_valore/ 1000,0);



        //Log::channel('stderr')->info('printoo');
        //Log::channel('stderr')->info($ottico_ckm_mese->of_kfkm_production);
        return response()->json($result);
    }

    private function calccalcolo_percentuale($valore, $target)
    {
        $tmp = number_format((($valore - $target) / $target) + 1,4);
        $t = explode(".",$tmp);
        if($t[0] == 0)
            $tmp = substr($t[1],0,2).'.'.substr($t[1],2,4);
        else{
            for($i = strlen($t[0]); $i < 3; $i++)
                $t[0] = $t[0].'0';
            $tmp =  $t[0].'.'.substr($t[1],0,2);
        }

        return $tmp;
    }

    private function get_terget($anno, $mese, $target, $tipo)
    {
        //Log::channel('stderr')->info($anno);
        $obj = DB::table('targets')
            ->select('valore','target')
            ->where('tipo', $tipo)
            ->where('titolo', $target)
            ->where('data_riferimento', $anno.'-'.$mese.'-01')
            ->first();

        return $obj;
    }

    private function getdata($annoDa,$meseDa,$annoA,$meseA,$column,$order = 'desc',$range=false)
    {
        if($range){
            $uno = DB::connection('mysql_old')->table('plant_costs')
                ->select($column,'month','year','id')
                ->where('year',$annoDa)
                ->where('month', '>=',$meseDa)
                ->orderBy($column,$order)
                ->first();

            $due = DB::connection('mysql_old')->table('plant_costs')
                ->select($column,'month','year','id')
                ->where('year',$annoA)
                ->where('month', '<=',$meseA)
                ->orderBy($column,$order)
                ->first();


            if($order == 'desc' && @$uno->$$column >= @$due->$$column)
                return $uno;
            elseif(@$uno->$$column <= @$due->$$column)
                return $uno;

            return $due;
        }else{
            $obj = DB::connection('mysql_old')->table('plant_costs')
                ->select($column,'month','year','id')
                ->where('year',$annoDa)
                ->where('month', $meseDa)
                ->orderBy($column,'desc')
                ->first();

            return $obj;
        }
    }
}
