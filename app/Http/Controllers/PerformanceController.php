<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceController extends Controller
{
    public function report(Request $request)
    {
        $periodo = $request->periodo;
        $periodo = explode(" to ", $periodo);
        $data[1] = explode("-", $periodo[0]);
        $data[2] = explode("-", $periodo[1]);

        if ($data[1][0] == $data[2][0]) {
            $resultUno = DB::connection('mysql_old')->table('plant_costs')
                ->where('year', $data[1][0])
                ->where('month', '>=', $data[1][1])
                ->where('month', '<=', $data[2][1])
                ->get();
        } else {
            $resultUno = DB::connection('mysql_old')->table('plant_costs')
                ->where('year', $data[1][0])
                ->where('month', '>=', $data[1][1])
                ->get();

            $resultDue = DB::connection('mysql_old')->table('plant_costs')
                ->where('year', $data[2][0])
                ->where('month', '<=', $data[2][1])
                ->get();
        }

        $rows_produzione_ottico_ckm_agv = DB::table('targets')
            ->select(DB::raw('SUM(valore) as ckm_valore'), DB::raw('SUM(target) as ckm_target'))
            ->where('tipo', 3)
            ->where('titolo', 'terget_ofc_ckm')
            ->whereBetween('data_riferimento', $periodo)
            ->first();

        $rows_produzione_ottico_kfkm_agv = DB::table('targets')
            ->select(DB::raw('SUM(valore) as kfkm_valore'), DB::raw('SUM(target) as kfkm_target'))
            ->where('tipo', 3)
            ->where('titolo', 'terget_kfkm')
            ->whereBetween('data_riferimento', $periodo)
            ->first();

        $rows_produzione_rame_ckm_agv = DB::table('targets')
            ->select(DB::raw('SUM(valore) as ckm_valore'), DB::raw('SUM(target) as ckm_target'))
            ->where('tipo', 3)
            ->where('titolo', 'terget_ckm')
            ->whereBetween('data_riferimento', $periodo)
            ->first();

        $rows_fatturato_ottico_ckm_agv = DB::table('targets')
            ->select(DB::raw('SUM(valore) as ckm_valore'), DB::raw('SUM(target) as ckm_target'))
            ->where('tipo', 1)
            ->where('titolo', 'ckm_ofc')
            ->whereBetween('data_riferimento', $periodo)
            ->first();

        $rows_fatturato_ottico_kfkm_agv = DB::table('targets')
            ->select(DB::raw('SUM(valore) as kfkm_valore'), DB::raw('SUM(target) as kfkm_target'))
            ->where('tipo', 1)
            ->where('titolo', 'kfkm_ofc')
            ->whereBetween('data_riferimento', $periodo)
            ->first();

        $rows_fatturato_rame_ckm_agv = DB::table('targets')
            ->select(DB::raw('SUM(valore) as ckm_valore'), DB::raw('SUM(target) as ckm_target'))
            ->where('tipo', 1)
            ->where('titolo', 'ckm_cc')
            ->whereBetween('data_riferimento', $periodo)
            ->first();

        $rows_spedito_ottico_ckm_agv = DB::table('targets')
            ->select(DB::raw('SUM(valore) as ckm_valore'), DB::raw('SUM(target) as ckm_target'))
            ->where('tipo', 2)
            ->where('titolo', 'ckm_ofc')
            ->whereBetween('data_riferimento', $periodo)
            ->first();

        $rows_spedito_ottico_kfkm_agv = DB::table('targets')
            ->select(DB::raw('SUM(valore) as kfkm_valore'), DB::raw('SUM(target) as kfkm_target'))
            ->where('tipo', 2)
            ->where('titolo', 'fkm_ofc')
            ->whereBetween('data_riferimento', $periodo)
            ->first();

        $rows_spedito_rame_ckm_agv = DB::table('targets')
            ->select(DB::raw('SUM(valore) as ckm_valore'), DB::raw('SUM(target) as ckm_target'))
            ->where('tipo', 2)
            ->where('titolo', 'ckm_cc')
            ->whereBetween('data_riferimento', $periodo)
            ->first();


        $rows_fatturato_rame = DB::table('fi_turnover_rows')->select(DB::raw('SUM(ckm) as ckm_tot'), DB::raw('SUM(importo_valuta_locale) as value_tot'))
            ->whereBetween('data_documento', $periodo)
            ->where('tipologia_cavo', '5441')
            ->first();


        $rows_fatturato_ottico = DB::table('fi_turnover_rows')->select(DB::raw('SUM(ckm) as ckm_tot'), DB::raw('SUM(kfkm) as kfkm_tot'), DB::raw('SUM(importo_valuta_locale) as value_tot'))
            ->whereBetween('data_documento', $periodo)
            ->where('tipologia_cavo', '5420')
            ->first();


        $rows_spedito_ottico = DB::table('fi_shipped_rows')->select(DB::raw('SUM(delivered_qty) as ckm_tot'), DB::raw('SUM(qty_fkm) as fkm_tot'))
            ->whereBetween('date_row', $periodo)
            ->where('type', '5420')
            ->first();


        $rows_spedito_rame = DB::table('fi_shipped_rows')->select(DB::raw('SUM(delivered_qty) as ckm_tot'))
            ->whereBetween('date_row', $periodo)
            ->where('type', '5441')
            ->first();
        $report = [
            'production_ckm_ottico_totale' => 0,
            'production_kfkm_ottico_totale' => 0,
            'production_ckm_rame_totale' => 0,
            'production_ckm_ottico_agv' => 0,
            'production_kfkm_ottico_agv' => 0,
            'production_ckm_rame_agv' => 0,
            'production_ckm_ottico_agv_perc' => 0,
            'production_kfkm_ottico_agv_perc' => 0,
            'production_ckm_rame_agv_perc' => 0,
            'sales_ckm_rame_totale' => 0,
            'sales_value_rame_totale' => 0,
            'sales_ckm_ottico_totale' => 0,
            'sales_kfkm_ottico_totale' => 0,
            'sales_value_ottico_totale' => 0,
            'sales_ckm_ottico_agv' => 0,
            'sales_kfkm_ottico_agv' => 0,
            'sales_ckm_rame_agv' => 0,
            'sales_ckm_ottico_agv_perc' => 0,
            'sales_kfkm_ottico_agv_perc' => 0,
            'sales_ckm_rame_agv_perc' => 0,

            'dispatch_ckm_ottico_totale' => 0,
            'dispatch_ckm_ottico_agv_perc' => 0,
            'dispatch_kfkm_ottico_totale' => 0,
            'dispatch_kfkm_ottico_agv_perc' => 0,
            'dispatch_ckm_rame_totale' => 0,
            'dispatch_ckm_rame_agv_perc' => 0,
            'dispatch_ckm_ottico_agv' => 0,
            'dispatch_kfkm_ottico_agv' => 0,
            'dispatch_ckm_rame_agv' => 0,


        ];
        foreach ($resultUno as $uno) {
            $report['production_ckm_ottico_totale'] += $uno->of_ckm_production;
            $report['production_kfkm_ottico_totale'] += $uno->of_kfkm_production;
            $report['production_ckm_rame_totale'] += $uno->cc_ckm_production;
        }

        if ($data[1][0] != $data[2][0])
            foreach ($resultDue as $due) {
                $report['production_ckm_ottico_totale'] += $due->of_ckm_production;
                $report['production_kfkm_ottico_totale'] += $due->of_kfkm_production;
                $report['production_ckm_rame_totale'] += $due->cc_ckm_production;

            }

        $report['sales_ckm_rame_totale'] += (float)str_replace("-", "", $rows_fatturato_rame->ckm_tot);
        $report['sales_value_rame_totale'] += (float)str_replace("-", "", $rows_fatturato_rame->value_tot);
        $report['sales_ckm_ottico_agv'] += $rows_fatturato_ottico_ckm_agv->ckm_target;
        $val = 0;
        if ($rows_fatturato_ottico_ckm_agv->ckm_valore && $rows_fatturato_ottico_ckm_agv->ckm_target) {

            $val = $this->calccalcolo_percentuale($rows_fatturato_ottico_ckm_agv->ckm_valore, $rows_fatturato_ottico_ckm_agv->ckm_target);

        }


        $report['sales_ckm_ottico_agv_perc'] += $val;
        $report['sales_kfkm_ottico_agv'] += $rows_fatturato_ottico_kfkm_agv->kfkm_target;
        $val = 0;
        if ($rows_fatturato_ottico_kfkm_agv->kfkm_valore && $rows_fatturato_ottico_kfkm_agv->kfkm_target)
            $val = $this->calccalcolo_percentuale($rows_fatturato_ottico_kfkm_agv->kfkm_valore, $rows_fatturato_ottico_kfkm_agv->kfkm_target);

        $report['sales_kfkm_ottico_agv_perc'] += $val;
        $report['sales_ckm_rame_agv'] += $rows_fatturato_rame_ckm_agv->ckm_target;
        $val = 0;
        if ($rows_fatturato_rame_ckm_agv->ckm_valore && $rows_fatturato_rame_ckm_agv->ckm_target)
            $val = $this->calccalcolo_percentuale($rows_fatturato_rame_ckm_agv->ckm_valore, $rows_fatturato_rame_ckm_agv->ckm_target);
        $report['sales_ckm_rame_agv_perc'] += $val;

        $report['sales_ckm_ottico_totale'] += (float)str_replace("-", "", $rows_fatturato_ottico->ckm_tot);
        $report['sales_kfkm_ottico_totale'] += (float)str_replace("-", "", $rows_fatturato_ottico->kfkm_tot);
        $report['sales_value_ottico_totale'] += (float)str_replace("-", "", $rows_fatturato_ottico->value_tot);

        $report['dispatch_ckm_ottico_totale'] += (float)str_replace("-", "", $rows_spedito_ottico->ckm_tot);
        $report['dispatch_kfkm_ottico_totale'] += round((float)str_replace("-", "", $rows_spedito_ottico->fkm_tot) / 1000, 3);
        $report['dispatch_ckm_rame_totale'] += (float)str_replace("-", "", $rows_spedito_rame->ckm_tot);
        $report['dispatch_ckm_ottico_agv'] += $rows_spedito_ottico_ckm_agv->ckm_target;
        $val = 0;
        if ($rows_spedito_ottico_ckm_agv->ckm_valore != 0 && $rows_spedito_ottico_ckm_agv->ckm_target)
            $val = $this->calccalcolo_percentuale($rows_spedito_ottico_ckm_agv->ckm_valore, $rows_spedito_ottico_ckm_agv->ckm_target);
        $report['dispatch_ckm_ottico_agv_perc'] += $val;

        $report['dispatch_kfkm_ottico_agv'] += $rows_spedito_ottico_kfkm_agv->kfkm_target;
        $val = 0;
        if ($rows_spedito_ottico_kfkm_agv->kfkm_valore && $rows_spedito_ottico_kfkm_agv->kfkm_target)
            $val = $this->calccalcolo_percentuale($rows_spedito_ottico_kfkm_agv->kfkm_valore, $rows_spedito_ottico_kfkm_agv->kfkm_target);
        $report['dispatch_kfkm_ottico_agv_perc'] += $val;

        $report['dispatch_ckm_rame_agv'] += $rows_spedito_rame_ckm_agv->ckm_target;

        $val = 0;
        if ($rows_spedito_rame_ckm_agv->ckm_valore && $rows_spedito_rame_ckm_agv->ckm_target)
            $val = $this->calccalcolo_percentuale($rows_spedito_rame_ckm_agv->ckm_valore, $rows_spedito_rame_ckm_agv->ckm_target);
        $report['dispatch_ckm_rame_agv_perc'] += $val;

        $report['production_ckm_ottico_agv'] += $rows_produzione_ottico_ckm_agv->ckm_target;
        $report['production_kfkm_ottico_agv'] += $rows_produzione_ottico_kfkm_agv->kfkm_target;
        $report['production_ckm_rame_agv'] += $rows_produzione_rame_ckm_agv->ckm_target;

        $val = 0;
        if ($rows_produzione_ottico_ckm_agv->ckm_valore && $rows_produzione_ottico_ckm_agv->ckm_target)
            $val = $this->calccalcolo_percentuale($rows_produzione_ottico_ckm_agv->ckm_valore, $rows_produzione_ottico_ckm_agv->ckm_target);
        $report['production_ckm_ottico_agv_perc'] += $rows_produzione_ottico_ckm_agv->ckm_target;

        $val = 0;
        if ($rows_produzione_ottico_kfkm_agv->kfkm_valore && $rows_produzione_ottico_kfkm_agv->kfkm_target)
            $val = $this->calccalcolo_percentuale($rows_produzione_ottico_kfkm_agv->kfkm_valore, $rows_produzione_ottico_kfkm_agv->kfkm_target);
        $report['production_kfkm_ottico_agv_perc'] += $val;

        $val = 0;
        if ($rows_produzione_rame_ckm_agv->ckm_valore && $rows_produzione_rame_ckm_agv->ckm_target)
            $val = $this->calccalcolo_percentuale($rows_produzione_rame_ckm_agv->ckm_valore, $rows_produzione_rame_ckm_agv->ckm_target);
        $report['production_ckm_rame_agv_perc'] += $rows_produzione_rame_ckm_agv->ckm_target;

        return response()->json($report);
    }

    private function calccalcolo_percentuale($valore, $target)
    {

        $tmp = round((($valore - $target) / $target) + 1, 4);
        $t = explode(".", $tmp);
        if ($t[0] == 0) {
            $tmp = substr($t[1], 0, 2) . '.' . substr($t[1], 2, 4);
        } else {
            for ($i = strlen($t[0]); $i < 3; $i++)
                $t[0] = $t[0] . '0';
            $tmp = $t[0] . '.' . substr($t[1], 0, 2);
        }


        return $tmp;
    }
}
