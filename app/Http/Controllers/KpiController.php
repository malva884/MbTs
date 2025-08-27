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
            'ofc_wip'=> 0,
            'cc_wip'=> 0,
            'total_wip'=> 0,
            'total_rm'=> 0,
            'ofc_wip_mese'=> 0,
            'cc_wip_mese'=> 0,
            'total_wip_mese'=> 0,
            'total_rm_mese'=> 0,
            'total_warehous_mese' => 0,
        ];


        $rame_ckm = $this->getdata($annoDa,$meseDa,$annoA,$meseA,'cc_ckm_production','desc',true);
        $rame_ckm_mese = $this->getdata($anno,$mese,null,null,'cc_ckm_production','desc',false);
        $energia_tot = $this->getdata($annoDa,$meseDa,$annoA,$meseA,'of_eletric_power','asc',true, 'of_utility_cost');
        $energia_mese = $this->getdata($anno,$mese,null,null,'of_eletric_power','asc',false, 'of_utility_cost' );

        $rame_costo_personale = $this->getdata($annoDa,$meseDa,$annoA,$meseA,'cc_cmk_employee','asc',true);

        //$energia_mese_tot = $this->getdata($anno,$mese,null,null,'of_eletric_power','desc',false);

        if(!empty($energia_tot->year) && !empty($energia_tot->month)){
            $rame_ckm_power = $this->get_terget($energia_tot->year, $energia_tot->month, 'ckm_cc', 1);
            $ottico_ckm_power = $this->get_terget($energia_tot->year, $energia_tot->month, 'fkm_ofc', 1);
            $result['cc_ckm_power'] = @round($rame_ckm_power->valore,0);
            $result['ofc_kfkm_power'] = round(@$ottico_ckm_power->valore / 1000, 0);
            $cc_power_data = date_create(@$energia_tot->year.'-'.@$energia_tot->month);
            $result['cc_power_data'] =date_format($cc_power_data,"M y");
            $result['cc_power_cost'] = round(((@$energia_tot->of_eletric_power * 30) / 100) / 1000);
            $ofc_power_data = date_create(@$energia_tot->year.'-'.@$energia_tot->month);
            $result['ofc_power_data'] = date_format($ofc_power_data,"M y");
            $result['ofc_power_cost'] = round(((@$energia_tot->of_eletric_power * 70) / 100) / 1000);
        }

        // if(!empty($energia_mese_tot->year) && !empty($energia_mese_tot->month)){
        $rame_ckm_power = $this->get_terget($anno,$mese, 'ckm_cc', 1);
        $ottico_ckm_power = $this->get_terget($anno,$mese, 'fkm_ofc', 1);
        $result['cc_ckm_power_mese'] = round( @$rame_ckm_power->valore, 0);
        $result['ofc_kfkm_power_mese'] = round(@$ottico_ckm_power->valore / 1000, 0);
        $result['cc_power_cost_mese'] = round(((@$energia_mese->of_eletric_power * 30) / 100) / 1000);
        $result['ofc_power_cost_mese'] = round(((@$energia_mese->of_eletric_power * 70) / 100) / 1000);
        // }

        $cc_ckm_data=date_create($rame_ckm->year.'-'.$rame_ckm->month);
        $result['cc_ckm_data'] = date_format($cc_ckm_data,"M y");
        $result['cc_ckm_prodotto'] = $rame_ckm->cc_ckm_production;
        $result['cc_ckm_mese_prodotto'] = $rame_ckm_mese->cc_ckm_production;
        $cc_costo_personale_data=date_create($rame_costo_personale->year.'-'.$rame_costo_personale->month);

        $result['cc_costo_personale_data'] = date_format($cc_costo_personale_data,"M y");
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



        $ofc_kfkm_data = date_create($ottico_fkm->year.'-'.$ottico_fkm->month);

        $result['ofc_kfkm_data'] = date_format($ofc_kfkm_data,"M y");
        $result['ofc_kfkm_prodotto'] =  $ottico_fkm->of_kfkm_production;
        $result['ofc_kfkm_mese_prodotto'] =  $ottico_ckm_mese->of_kfkm_production;
        $ofc_costo_personale_data = date_create($ottico_costo_personale->year.'-'.$ottico_costo_personale->month);
        $result['ofc_costo_personale_data'] = date_format($ofc_costo_personale_data,"M y");
        $result['ofc_costo_personale'] = $ottico_costo_personale->of_cmk_employee;


        $fatturato_ottico_kfm = $this->get_terget($ottico_fkm->year, $ottico_fkm->month, 'fkm_ofc',1);
        $fatturato_ottico_kfm_mese = $this->get_terget($anno, $mese, 'fkm_ofc',1);

        $result['ofc_kfkm_fatturato'] = round($fatturato_ottico_kfm->valore / 1000,0);
        $result['ofc_kfkm_mese_fatturato'] = (empty($fatturato_ottico_kfm_mese->valore) ? '-':round($fatturato_ottico_kfm_mese->valore/1000,0));

        $scrapOtticoMese = DB::connection('mysql_old')->table('report_production_scraps')
            ->select('overall')
            ->whereYear('date_a',$anno)
            ->whereMonth('date_a',$mese)
            ->orderBy('date_a','desc')
            ->groupBy()
            ->first();

        $scrapOtticoTop = DB::connection('mysql_old')->table('report_production_scraps')
            ->select('date_a',DB::raw('min(overall) as overall'))
            ->whereIn('id', function($query) use ($annoDa,$meseDa,$annoA,$meseA){
                $query->select( DB::raw('max(id) as id'))
                    ->from('report_production_scraps')
                    ->whereBetween('date_a', [$annoDa.'-'.$meseDa.'-01', $annoA.'-'.$meseA.'-31'])
                    ->groupBy(DB::raw('Month(date_a)'));
            })
            ->first();

        $fatturato_ottico_kfm_scrap = $this->get_terget(explode("-",$scrapOtticoTop->date_a)[0] , explode("-",$scrapOtticoTop->date_a)[1], 'fkm_ofc',1);

        $result['ofc_scarp_month'] = (empty($scrapOtticoMese->overall) ? '-':$scrapOtticoMese->overall);
        $result['ofc_scarp_kfkm_top'] = (empty($fatturato_ottico_kfm_scrap->valore) ? '-':round($fatturato_ottico_kfm_scrap->valore/1000,0));
        $result['ofc_scarp_kfkm_month'] = (empty($fatturato_ottico_kfm_mese->valore) ? '-':round($fatturato_ottico_kfm_mese->valore/1000,0));

        $anno_scrap = '';
        $mese_scrap = '';
        if(!empty($scrapOtticoTop->overall)){
            $result['ofc_scarp_top'] = $scrapOtticoTop->overall;
            $ofc_scarp_top_data = date_create(explode("-",$scrapOtticoTop->date_a)[0].'-'.explode("-",$scrapOtticoTop->date_a)[1]);

            $result['ofc_scarp_top_data'] = date_format($ofc_scarp_top_data,"M y");

        }
        else{
            $result['ofc_scarp_top'] = '-';
            $result['ofc_scarp_top_data'] = '';
        }

        $ftrOtticoMese = DB::connection('mysql_old')->table('report_ftrs')
            ->select(DB::raw('SUM(value) as ftr'))
            ->where('year',$anno)
            ->where('month',$mese)
            ->where('reference','<>','coloring')
            ->orderBy('ftr','desc')
            ->first();

        $ftrOtticoTopUno = DB::connection('mysql_old')->table('report_ftrs')
            ->select('year','month',DB::raw('SUM(value) as ftr'))
            ->where('year',$annoDa)
            ->where('month','>=',$meseDa)
            ->where('reference','<>','coloring')
            ->orderBy('ftr','desc')
            ->groupBy('month')
            ->first();

        $ftrOtticoTopDue = DB::connection('mysql_old')->table('report_ftrs')
            ->select('year','month',DB::raw('SUM(value) as ftr'))
            ->where('year',$annoA)
            ->where('month','<=',$meseA)
            ->where('reference','<>','coloring')
            ->orderBy('ftr','desc')
            ->groupBy('month')
            ->first();

        $result['ofc_ftr_month'] = (empty($ftrOtticoMese->ftr) ? '-':round($ftrOtticoMese->ftr/3,2));
        $anno_ftr = '';
        $mese_ftr = '';
        if(!empty($ftrOtticoTopUno->ftr) && empty($ftrOtticoTopDue->ftr)){
            $result['ofc_ftr_top'] =  round($ftrOtticoTopUno->ftr/3,2);
            $anno_ftr = $ftrOtticoTopUno->year;
            $mese_ftr = $ftrOtticoTopUno->month;
        }
        elseif(!empty($ftrOtticoTopUno->ftr) && !empty($ftrOtticoTopDue->ftr)){
            $result['ofc_ftr_top'] = ($ftrOtticoTopUno->ftr > $ftrOtticoTopDue->ftr ? round($ftrOtticoTopUno->ftr/3,2):round($ftrOtticoTopDue->ftr/3,2));
            $anno_ftr =  ($ftrOtticoTopUno->ftr > $ftrOtticoTopDue->ftr ? $ftrOtticoTopUno->year: $ftrOtticoTopDue->year);
            $mese_ftr = ($ftrOtticoTopUno->ftr > $ftrOtticoTopDue->ftr ? $ftrOtticoTopUno->month: $ftrOtticoTopDue->month);
        }
        else
        {
            $result['ofc_ftr_top'] = '-';
            $result['ofc_ftr_top_year'] = '-';
            $result['ofc_ftr_top_month'] = '-';
        }
        $ofc_ftr_top_data = date_create($anno_ftr.'-'.$mese_ftr);
        $result['ofc_ftr_top_data'] = date_format($ofc_ftr_top_data,"M y");
        $fatturato_ottico_kfm_ftr_top= $this->get_terget($anno_ftr, $mese_ftr, 'fkm_ofc',1);

        $result['ofc_ftr_kfkm_month'] = (empty($fatturato_ottico_kfm_mese->valore) ? '-':round($fatturato_ottico_kfm_mese->valore/1000,0));
        $result['ofc_ftr_kfkm_top'] = (empty($fatturato_ottico_kfm_ftr_top->valore) ? '-':round($fatturato_ottico_kfm_ftr_top->valore/1000,0));

        $ottico_ckm_cost_month = $this->getdata($anno,$mese,null, null,'of_cmk_employee','desc',false);
        $rame_ckm_cost_month = $this->getdata($anno,$mese,null, null,'cc_cmk_employee','desc',false);
        $fatturato_ottico_ckm_employee_cost_top= $this->get_terget($ottico_costo_personale->year, $ottico_costo_personale->month, 'fkm_ofc',1);
        $fatturato_rame_ckm_employee_cost_top= $this->get_terget($rame_costo_personale->year, $rame_costo_personale->month, 'ckm_cc',1);

        $result['ofc_ckm_employee_cost_month'] = (empty($ottico_ckm_cost_month->of_cmk_employee) ? '-':$ottico_ckm_cost_month->of_cmk_employee);
        $result['ofc_fatturato_ckm_employee_cost_top'] = (empty($fatturato_ottico_ckm_employee_cost_top->valore) ? '-':round($fatturato_ottico_ckm_employee_cost_top->valore/1000,0));
        $result['cc_ckm_employee_cost_month'] = (empty($rame_ckm_cost_month->cc_cmk_employee) ? '-':$rame_ckm_cost_month->cc_cmk_employee);
        $result['cc_fatturato_ckm_employee_cost_month'] = (empty($fatturato_rame_ckm_employee_cost_top->valore) ? '-':round($fatturato_rame_ckm_employee_cost_top->valore,0));

        $ottico_run_time_uno = DB::connection('mysql_old')->table('report_run_times')
            ->select('value', 'report_reference','month','year')
            ->where('year','=',$annoDa)
            ->where('month','>=',$meseDa)
            ->whereNull('machinery')
            ->where('report_reference','<>','coloring')
            ->get();

        $ottico_run_time_due = DB::connection('mysql_old')->table('report_run_times')
            ->select('value', 'report_reference','month','year')
            ->where('year','=',$annoA)
            ->where('month','<=',$meseA)
            ->whereNull('machinery')
            ->where('report_reference','<>','coloring')
            ->get();

        foreach ($ottico_run_time_uno as $t)
            $ottico_run_time[] = (array)$t;

        foreach ($ottico_run_time_due as $t)
            $ottico_run_time[] = (array)$t;


        $oee = [];
        foreach ($ottico_run_time as $row){
            $ftr = DB::connection('mysql_old')->table('report_ftrs')
                ->select('value', 'reference','month','year')
                ->where('reference',$row['report_reference'] )
                ->where('year','=',$row['year'])
                ->where('month','=',$row['month'])
                ->first();
            $eff = DB::connection('mysql_old')->table('report_team_av_totals')
                ->select(DB::raw('SUM(value) as value'), 'reference','month','year')
                ->where('reference',$row['report_reference'] )
                ->where('year','=',$row['year'])
                ->where('month','=',$row['month'])
                ->groupBy(['reference','month'])
                ->first();

            $oee[$row['month'].'-'.$row['year']][$row['report_reference']] = round(($row['value'] * $ftr->value * round($eff->value / 3,1)) / 10000,2);
        }

        $result['ofc_oee_top'] = '-';
        $result['ofc_oee_top_periodo'] = '';
        foreach ($oee as $periodo => $values){

            if($periodo == $mese.'-'.$anno || $periodo == str_replace("0","",$mese).'-'.$anno){
                $result['ofc_oee_month'] = round(array_sum($oee[$periodo]) / 3,1);
            }

            $tmp =  round(array_sum($oee[$periodo]) / 3,1);
            if($tmp > $result['ofc_oee_top']){
                $result['ofc_oee_top'] = $tmp;
                $result['ofc_oee_top_periodo'] = $periodo;
            }
        }

        $fatturato_ottico_ckm_employee_cost_top= $this->get_terget(explode("-",$result['ofc_oee_top_periodo'])[1], explode("-",$result['ofc_oee_top_periodo'])[0], 'fkm_ofc',1);
        $result['ofc_oee_kfkm_top'] = (!empty($fatturato_ottico_ckm_employee_cost_top->valore) ? round($fatturato_ottico_ckm_employee_cost_top->valore / 1000,0):'');

        $wipOfc = $this->magazzino([$annoDa.'-'.$meseDa.'-01', $annoA.'-'.$meseA.'-31'],'WIP OFC');
        $wipCc = $this->magazzino([$annoDa.'-'.$meseDa.'-01', $annoA.'-'.$meseA.'-31'],'WIP CC');
        $wipTotal = $this->magazzino([$annoDa.'-'.$meseDa.'-01', $annoA.'-'.$meseA.'-31'],['WIP CC','WIP OFC']);
        $rmTotal = $this->magazzino([$annoDa.'-'.$meseDa.'-01', $annoA.'-'.$meseA.'-31'],['Raw Materials OFC','Raw Materials CC']);

        $result['ofc_wip'] = number_format($wipOfc->totale, 2, ',','.');
        $data = date_create(@$wipOfc->anno.'-'.@$wipOfc->mese);
        $result['ofc_wip_data'] = date_format($data,"M y");
        $result['cc_wip'] = number_format($wipCc->totale, 2, ',','.');
        $data = date_create(@$wipCc->anno.'-'.@$wipCc->mese);
        $result['cc_wip_data'] =date_format($data,"M y");
        $result['total_wip'] = number_format($wipTotal->totale, 2, ',','.');
        $data = date_create(@$wipTotal->anno.'-'.@$wipTotal->mese);
        $result['total_wip_data'] = date_format($data,"M y");
        $result['total_rm'] = number_format($rmTotal->totale, 2, ',','.');
        $data = date_create(@$rmTotal->anno.'-'.@$rmTotal->mese);
        $result['total_rm_data'] = date_format($data,"M y");

        $result_warehouse = DB::table('pr_warehouse_heads')
            ->select(DB::raw('SUM(totale+corso_lavori) as totale_magazzino'),'mese','anno')
            ->whereBetween('data_riferimento',[$annoDa.'-'.$meseDa.'-01', $annoA.'-'.$meseA.'-31'])
            ->groupBy('mese','anno')->orderBy('totale_magazzino')->first();

        $result['total_warehouse'] = number_format($result_warehouse->totale_magazzino, 2, ',','.');
        $data = date_create(@$result_warehouse->anno.'-'.@$result_warehouse->mese);
        $result['total_warehouse_date'] = date_format($data,"M y");


        $wipOfcMese = $this->magazzino($anno.'-'.$mese,'WIP OFC');
        $wipCcMese = $this->magazzino($anno.'-'.$mese,'WIP CC');
        $wipTotalMese = $this->magazzino($anno.'-'.$mese,['WIP CC','WIP OFC']);
        $rmTotalMese = $this->magazzino($anno.'-'.$mese,['Raw Materials OFC','Raw Materials CC']);

        if(!empty($wipOfcMese->totale))
            $result['ofc_wip_mese'] = number_format($wipOfcMese->totale, 2, ',','.');
        if(!empty($wipCcMese->totale))
            $result['cc_wip_mese'] = number_format($wipCcMese->totale, 2, ',','.');
        if(!empty($wipTotalMese->totale))
            $result['total_wip_mese'] = number_format($wipTotalMese->totale, 2, ',','.');
        if(!empty($rmTotalMese->totale))
            $result['total_rm_mese'] = number_format($rmTotalMese->totale, 2, ',','.');

        $result_warehouse_mese = DB::table('pr_warehouse_heads')
            ->select(DB::raw('SUM(totale+corso_lavori) as totale_magazzino'),'mese','anno')
            ->whereYear('data_riferimento',$anno)
            ->whereMonth('data_riferimento',$mese)
            ->groupBy('mese','anno')->orderBy('totale_magazzino')->first();

        if(!empty($result_warehouse_mese->totale_magazzino))
            $result['total_warehous_mese'] = number_format($result_warehouse_mese->totale_magazzino, 2, ',','.');

        return response()->json($result);
    }

    private function magazzino($data,$class)
    {
        $result = DB::table('pr_warehouse_rows')
            ->select('mese','anno',DB::raw('SUM(valore_totale) as totale'))
            ->join('pr_warehouse_heads','pr_warehouse_heads.id','pr_warehouse_rows.warehouse_id')
            ->Where(function ($query) use ($class) {
                if (is_array($class))
                    $query->WhereIn('classe', $class);
                elseif(is_string($class))
                    $query->where('classe', $class);
            })
            ->Where(function ($query) use ($data) {
                if (is_array($data))
                    $query->whereBetween('data_riferimento',$data);
                else{
                    $data = explode("-",$data);
                    $query->whereYear('data_riferimento', $data[0]);
                    $query->whereMonth('data_riferimento', $data[1]);
                }

            })
            ->groupBy('mese','anno')->orderBy('totale')->first();

        return $result;
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

    private function getdata($annoDa,$meseDa,$annoA,$meseA,$column,$order = 'desc',$range=false, $columnDue = '')
    {
        if($range){
            if($columnDue){
                $uno = DB::connection('mysql_old')->table('plant_costs')
                    ->select(DB::raw('SUM('.$column.' + '.$columnDue.') as '.$column), 'month','year','id')
                    ->where($column,'>', 0)
                    ->where('year',$annoDa)
                    ->where('month', '>=',$meseDa)
                    ->orderBy($column,$order)
                    ->first();

                $due = DB::connection('mysql_old')->table('plant_costs')
                    ->select(DB::raw('SUM('.$column.' + '.$columnDue.') as '.$column),'month','year','id')
                    ->where($column,'>', 0)
                    ->where('year',$annoA)
                    ->where('month', '<=',$meseA)
                    ->orderBy($column,$order)
                    ->first();

            }else{
                $uno = DB::connection('mysql_old')->table('plant_costs')
                    ->select($column, 'month','year','id')
                    ->where($column,'>', 0)
                    ->where('year',$annoDa)
                    ->where('month', '>=',$meseDa)
                    ->orderBy($column,$order)
                    ->first();

                $due = DB::connection('mysql_old')->table('plant_costs')
                    ->select($column,'month','year','id')
                    ->where($column,'>', 0)
                    ->where('year',$annoA)
                    ->where('month', '<=',$meseA)
                    ->orderBy($column,$order)
                    ->first();
            }


            if($order == 'desc' && @$uno->$$column >= @$due->$$column)
                return $uno;
            elseif($order == 'desc' && @$uno->$$column <= @$due->$$column)
                return $due;
            elseif($order == 'asc' && @$uno->$$column <= @$due->$$column)
                return $uno;
            elseif($order == 'asc' && @$uno->$$column >= @$due->$$column)
                return $due;

            return $due;
        }else{
            if(!$columnDue){
                $obj = DB::connection('mysql_old')->table('plant_costs')
                    ->select($column,'month','year','id')
                    ->where('year',$annoDa)
                    ->where('month', $meseDa)
                    ->orderBy($column,'desc')
                    ->first();
            }
            else{
                $obj = DB::connection('mysql_old')->table('plant_costs')
                    ->select(DB::raw('SUM('.$column.' + '.$columnDue.') as '.$column),'month','year','id')
                    ->where('year',$annoDa)
                    ->where('month', $meseDa)
                    ->orderBy($column,'desc')
                    ->first();
            }


            return $obj;
        }
    }
}
