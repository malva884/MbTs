<?php

namespace App\Http\Controllers;

use App\Exports\OraMacchinaExport;
use App\Models\DashboardProduction;
use App\Models\Gp;
use App\Models\PrWarehouseRows;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class PerformanceController extends Controller
{
    public function report(Request $request)
    {
        $periodo = $request->periodo;
        $periodo = explode(" to ", $periodo);
        $data[1] = explode("-", $periodo[0]);
        $data[2] = explode("-", $periodo[1]);

        $old = DB::connection('sqlsrv_root_gp')->table('Produzione as PRD')
            ->select('P.NomeProdotto AS Prodotto', 'DM.NRigaOrd AS Ordine', 'P.Conversione12 As NumeroFibre', 'UMP.UM AS UM', 'PRD.#Cicli as Quantita')
            ->join('Dettagli_sugli_ordini as DSO', 'DSO.IDDettagliOrdini', '=', 'PRD.IDDettOrd')
            ->join('Dettagli_Master as DM', 'DM.idMaster', '=', 'DSO.IDMaster')
            ->join('Prodotti as P', 'P.IDProdotto', '=', 'PRD.IDArticolo')
            ->join('UM as UMP', 'UMP.IDUM', '=', 'DSO.IDUM')
            ->join('Risorse as R', 'R.IDRisorsa', '=', 'PRD.IDRis')
            ->join('GP_NX_AGG.dbo.AGG_PRODOTTI_TMP AS ProdNuovi', 'ProdNuovi.cdProdotto', 'P.NomeProdotto')
            ->join("GP_NX_AGG.dbo.AGG_DETTAGLI_TMP as DT1", function ($join) {
                $join->on("DT1.cdOrdine", "=", DB::raw("replace(DM.NRigaOrd, '00009', '9')"))
                    ->on("DSO.CodPrel", "=", "DT1.numFase");
            })
            ->Where(function ($query) use ($periodo) {
                if (is_array($periodo))
                    $query->whereBetween('PRD.DataOraInizio', [$periodo[0] . ' 00:00:00:000', $periodo[1] . ' 23:59:59:990']);
                else
                    $query->whereDate('PRD.DataOraInizio', $periodo);

            })
            ->whereIn('DT1.ControlKey', ['PP03', 'ZP03'])
            ->where('PRD.Confermato', 1)
            ->where('PRD.Significativo', 1)
            ->where('PRD.IdSchedaPrdOrdineAcc', 0)
            ->where('PRD.#Cicli', '>', 0)
            ->get();

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
            ->where('titolo', 'fkm_ofc')
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


        $rows_fatturato_ottico = DB::table('fi_turnover_rows')->select(DB::raw('SUM(ckm) as ckm_tot'), DB::raw('SUM(fkm) as kfkm_tot'), DB::raw('SUM(importo_valuta_locale) as value_tot'))
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

        foreach ($old as $ordine) {
            $tmp[1] = substr($ordine->Prodotto, 0, 2);
            $tmp[2] = substr($ordine->Ordine, 0, 2);
            if ($tmp[2] != 94 and ($tmp[1] == 'SF' || $tmp[1] == 'FC' || $tmp[1] == 'F8')) {
                $tmp = ['material' => $ordine->Prodotto, 'description' => '', 'total_stock' => $ordine->Quantita, 'bun' => '', 'unitary_value' => 0, 'total_value' => 0.00, 'last_gds_mvmt' => '2024-01-01'];
                $report['production_ckm_ottico_totale'] = round($report['production_ckm_ottico_totale'] + $ordine->Quantita, 3);
                $report['production_kfkm_ottico_totale'] = $report['production_kfkm_ottico_totale'] + round($ordine->Quantita * $ordine->NumeroFibre, 3);
            } elseif ($tmp[1] == 'F5' || $tmp[1] == 'F6' || $tmp[1] == 'FJ') {
                $tmp = ['material' => $ordine->Prodotto, 'description' => '', 'total_stock' => $ordine->Quantita, 'bun' => '', 'unitary_value' => 0, 'total_value' => 0.00, 'last_gds_mvmt' => '2024-01-01'];
                $report['production_ckm_rame_totale'] = round($report['production_ckm_rame_totale'] + $ordine->Quantita, 3);
            }
        }

        $report['production_kfkm_ottico_totale'] = round($report['production_kfkm_ottico_totale'] / 1000, 0);
        $report['production_ckm_rame_totale'] = round($report['production_ckm_rame_totale'] / 1000, 0);


        $report['production_ckm_rame_totale'] = round($report['production_ckm_rame_totale'], 0);
        $report['production_kfkm_ottico_totale'] = round($report['production_kfkm_ottico_totale'], 0);
        $report['production_ckm_ottico_totale'] = round($report['production_ckm_ottico_totale'], 0);
        $report['sales_ckm_rame_totale'] += round((float)str_replace("-", "", $rows_fatturato_rame->ckm_tot), 0);
        $report['sales_value_rame_totale'] += (float)str_replace("-", "", $rows_fatturato_rame->value_tot);
        $report['sales_ckm_ottico_agv'] += $rows_fatturato_ottico_ckm_agv->ckm_target;
        $val = 0;
        if ($rows_fatturato_ottico_ckm_agv->ckm_valore > 0 && $rows_fatturato_ottico_ckm_agv->ckm_target > 0) {

            $val = $this->calccalcolo_percentuale($rows_fatturato_ottico_ckm_agv->ckm_valore, $rows_fatturato_ottico_ckm_agv->ckm_target);

        }


        $report['sales_ckm_ottico_agv_perc'] += $val;
        $report['sales_kfkm_ottico_agv'] += $rows_fatturato_ottico_kfkm_agv->kfkm_target / 1000;


        $report['sales_ckm_rame_agv'] += $rows_fatturato_rame_ckm_agv->ckm_target;
        $val = 0;
        if ($rows_fatturato_rame_ckm_agv->ckm_valore > 0 && $rows_fatturato_rame_ckm_agv->ckm_target > 0)
            $val = $this->calccalcolo_percentuale($report['sales_ckm_rame_totale'], $report['sales_ckm_rame_agv']);
        $report['sales_ckm_rame_agv_perc'] += $val;

        $report['sales_ckm_ottico_totale'] += round((float)str_replace("-", "", $rows_fatturato_ottico->ckm_tot), 0);
        $report['sales_kfkm_ottico_totale'] += round((float)str_replace("-", "", $rows_fatturato_ottico->kfkm_tot) / 1000, 0);
        $report['sales_value_ottico_totale'] += (float)str_replace("-", "", $rows_fatturato_ottico->value_tot);

        $report['dispatch_ckm_ottico_totale'] += round((float)str_replace("-", "", $rows_spedito_ottico->ckm_tot), 0);
        $report['dispatch_kfkm_ottico_totale'] += round((float)str_replace("-", "", $rows_spedito_ottico->fkm_tot) / 1000, 0);
        $report['dispatch_ckm_rame_totale'] += round((float)str_replace("-", "", $rows_spedito_rame->ckm_tot), 0);
        $report['dispatch_ckm_ottico_agv'] += $rows_spedito_ottico_ckm_agv->ckm_target;
        $val = 0;
        if ($rows_fatturato_ottico_kfkm_agv->kfkm_valore > 0 && $rows_fatturato_ottico_kfkm_agv->kfkm_target > 0)
            $val = $this->calccalcolo_percentuale($report['sales_kfkm_ottico_totale'], $report['sales_kfkm_ottico_agv']);
        $report['sales_kfkm_ottico_agv_perc'] += $val;

        $val = 0;
        if ($rows_spedito_ottico_ckm_agv->ckm_valore > 0 && $rows_spedito_ottico_ckm_agv->ckm_target > 0)
            $val = $this->calccalcolo_percentuale($rows_spedito_ottico_ckm_agv->ckm_valore, $rows_spedito_ottico_ckm_agv->ckm_target);
        $report['dispatch_ckm_ottico_agv_perc'] += $val;

        $report['dispatch_kfkm_ottico_agv'] += $rows_spedito_ottico_kfkm_agv->kfkm_target / 1000;
        $val = 0;
        if ($rows_spedito_ottico_kfkm_agv->kfkm_valore > 0 && $rows_spedito_ottico_kfkm_agv->kfkm_target > 0)
            $val = $this->calccalcolo_percentuale($report['dispatch_kfkm_ottico_totale'], $rows_spedito_ottico_kfkm_agv->kfkm_target / 1000);
        $report['dispatch_kfkm_ottico_agv_perc'] += $val;

        $report['dispatch_ckm_rame_agv'] += $rows_spedito_rame_ckm_agv->ckm_target;

        $val = 0;
        if ($rows_spedito_rame_ckm_agv->ckm_valore > 0 && $rows_spedito_rame_ckm_agv->ckm_target > 0)
            $val = $this->calccalcolo_percentuale($rows_spedito_rame_ckm_agv->ckm_valore, $rows_spedito_rame_ckm_agv->ckm_target);
        $report['dispatch_ckm_rame_agv_perc'] += $val;

        $report['production_ckm_ottico_agv'] += $rows_produzione_ottico_ckm_agv->ckm_target;
        $report['production_kfkm_ottico_agv'] += $rows_produzione_ottico_kfkm_agv->kfkm_target;
        $report['production_ckm_rame_agv'] += $rows_produzione_rame_ckm_agv->ckm_target;

        $val = 0;
        if ($rows_produzione_ottico_ckm_agv->ckm_valore > 0 && $rows_produzione_ottico_ckm_agv->ckm_target > 0)
            $val = $this->calccalcolo_percentuale($rows_produzione_ottico_ckm_agv->ckm_valore, $rows_produzione_ottico_ckm_agv->ckm_target);
        $report['production_ckm_ottico_agv_perc'] += $rows_produzione_ottico_ckm_agv->ckm_target;

        $val = 0;
        if ($rows_produzione_ottico_kfkm_agv->kfkm_valore > 0 && $rows_produzione_ottico_kfkm_agv->kfkm_target > 0)
            $val = $this->calccalcolo_percentuale($rows_produzione_ottico_kfkm_agv->kfkm_valore, $rows_produzione_ottico_kfkm_agv->kfkm_target);
        $report['production_kfkm_ottico_agv_perc'] += $val;

        $val = 0;
        if ($rows_produzione_rame_ckm_agv->ckm_valore > 0 && $rows_produzione_rame_ckm_agv->ckm_target > 0)
            $val = $this->calccalcolo_percentuale($rows_produzione_rame_ckm_agv->ckm_valore, $rows_produzione_rame_ckm_agv->ckm_target);
        $report['production_ckm_rame_agv_perc'] += $rows_produzione_rame_ckm_agv->ckm_target;


        return response()->json($report);
    }

    public function performance(Request $request)
    {

        $definizione = [
            'Production' => [
                ['title' => 'Plan Ofc Ckm', 'camp' => 'Ofc_Ckm_Plan', 'color' => 'primary', 'col' => 4],
                ['title' => 'Actual Ofc Ckm', 'camp' => 'Ofc_Ckm', 'color' => 'success', 'col' => 4],
                ['title' => 'Agp Ofc Ckm', 'camp' => 'Ofc_Ckm_Agp', 'color' => 'info', 'col' => 4],
                ['title' => 'Variance', 'camp_1' => 'Ofc_Ckm', 'camp_2' => 'Ofc_Ckm_Plan', 'camp_3' => 'Ofc_Ckm_Agp', 'color' => 'primary', 'col' => 12],
                ['title' => 'Plan Ofc Kfkm', 'camp' => 'Ofc_Fkm_Plan', 'color' => 'primary', 'col' => 4],
                ['title' => 'Actual Ofc Kfkm', 'camp' => 'Ofc_Fkm', 'color' => 'success', 'col' => 4],
                ['title' => 'Agp Ofc Kfkm', 'camp' => 'Ofc_Fkm_Agp', 'color' => 'info', 'col' => 4],
                ['title' => 'Variance', 'camp_1' => 'Ofc_Fkm', 'camp_2' => 'Ofc_Fkm_Plan', 'camp_3' => 'Ofc_Fkm_Agp', 'color' => 'Primary', 'col' => 12],
                ['title' => 'Plan Cc Ckm', 'camp' => 'Cc_Ckm_Plan', 'color' => 'primary', 'col' => 4],
                ['title' => 'Actual Cc Ckm', 'camp' => 'Cc_Ckm', 'color' => 'success', 'col' => 4],
                ['title' => 'Agp cc Ckm', 'camp' => 'Cc_Ckm_Agp', 'color' => 'info', 'col' => 4],
                ['title' => 'Variance', 'camp_1' => 'Cc_Ckm', 'camp_2' => 'Cc_Ckm_Plan', 'camp_3' => 'Cc_Ckm_Agp', 'color' => 'Primary', 'col' => 12],
            ],
            'Dispatch' => [
                ['title' => 'Plan Ofc Ckm', 'camp' => 'D_Ofc_Ckm_Plan', 'color' => 'primary', 'col' => 4],
                ['title' => 'Actual Ofc Ckm', 'camp' => 'D_Ofc_Ckm', 'color' => 'success', 'col' => 4],
                ['title' => 'Agp Ofc Ckm', 'camp' => 'D_Ofc_Ckm_Agp', 'color' => 'info', 'col' => 4],
                ['title' => 'Variance', 'camp_1' => 'D_Ofc_Ckm', 'camp_2' => 'D_Ofc_Ckm_Plan', 'camp_3' => 'D_Ofc_Ckm_Agp', 'color' => 'primary', 'col' => 12],
                ['title' => 'Plan Ofc Kfkm', 'camp' => 'D_Ofc_Fkm_Plan', 'color' => 'primary', 'col' => 4],
                ['title' => 'Actual Ofc Kfkm', 'camp' => 'D_Ofc_Fkm', 'color' => 'success', 'col' => 4],
                ['title' => 'Agp Ofc Kfkm', 'camp' => 'D_Ofc_Fkm_Agp', 'color' => 'info', 'col' => 4],
                ['title' => 'Variance', 'camp_1' => 'D_Ofc_Fkm', 'camp_2' => 'D_Ofc_Fkm_Plan', 'camp_3' => 'D_Ofc_Fkm_Agp', 'color' => 'Primary', 'col' => 12],
                ['title' => 'Plan Ofc Mln', 'camp' => 'D_Ofc_Mln_Plan', 'color' => 'primary', 'col' => 4],
                ['title' => 'Actual Ofc Mln', 'camp' => 'D_Ofc_Mln', 'color' => 'success', 'col' => 4],
                ['title' => 'Agp Ofc Mln', 'camp' => 'D_Ofc_Mln_Agp', 'color' => 'info', 'col' => 4],
                ['title' => 'Variance', 'camp_1' => 'D_Ofc_Mln', 'camp_2' => 'D_Ofc_Mln_Plan', 'camp_3' => 'D_Ofc_Mln_Agp', 'color' => 'Primary', 'col' => 12],
                ['title' => 'Plan Cc Ckm', 'camp' => 'D_Cc_Ckm_Plan', 'color' => 'primary', 'col' => 4],
                ['title' => 'Actual Cc Ckm', 'camp' => 'D_Cc_Ckm', 'color' => 'success', 'col' => 4],
                ['title' => 'Agp cc Ckm', 'camp' => 'D_Cc_Ckm_Agp', 'color' => 'info', 'col' => 4],
                ['title' => 'Variance', 'camp_1' => 'D_Cc_Ckm', 'camp_2' => 'D_Cc_Ckm_Plan', 'camp_3' => 'D_Cc_Ckm_Agp', 'color' => 'Primary', 'col' => 12],
                ['title' => 'Plan Cc Mln', 'camp' => 'D_Cc_Mln_Plan', 'color' => 'primary', 'col' => 4],
                ['title' => 'Actual Cc Mln', 'camp' => 'D_Cc_Mln', 'color' => 'success', 'col' => 4],
                ['title' => 'Agp cc Mln', 'camp' => 'D_Cc_Mln_Agp', 'color' => 'info', 'col' => 4],
                ['title' => 'Variance', 'camp_1' => 'D_Cc_Mln', 'camp_2' => 'D_Cc_Mln_Plan', 'camp_3' => 'D_Cc_Ckm_Agp', 'color' => 'Primary', 'col' => 12],

            ],
            'Revenue' => [
                ['title' => 'Plan Ofc Ckm', 'camp' => 'R_Ofc_Ckm_Plan', 'color' => 'primary', 'col' => 4],
                ['title' => 'Actual Ofc Ckm', 'camp' => 'R_Ofc_Ckm', 'color' => 'success', 'col' => 4],
                ['title' => 'Agp Ofc Ckm', 'camp' => 'R_Ofc_Ckm_Agp', 'color' => 'info', 'col' => 4],
                ['title' => 'Variance', 'camp_1' => 'R_Ofc_Ckm', 'camp_2' => 'R_Ofc_Ckm_Plan', 'camp_3' => 'R_Ofc_Ckm_Agp', 'color' => 'primary', 'col' => 12],
                ['title' => 'Plan Ofc Kfkm', 'camp' => 'R_Ofc_Fkm_Plan', 'color' => 'primary', 'col' => 4],
                ['title' => 'Actual Ofc Kfkm', 'camp' => 'R_Ofc_Fkm', 'color' => 'success', 'col' => 4],
                ['title' => 'Agp Ofc Kfkm', 'camp' => 'R_Ofc_Fkm_Agp', 'color' => 'info', 'col' => 4],
                ['title' => 'Variance', 'camp_1' => 'R_Ofc_Fkm', 'camp_2' => 'R_Ofc_Fkm_Plan', 'camp_3' => 'R_Ofc_Fkm_Agp', 'color' => 'Primary', 'col' => 12],
                ['title' => 'Plan Ofc Mln', 'camp' => 'R_Ofc_Mln_Plan', 'color' => 'primary', 'col' => 4],
                ['title' => 'Actual Ofc Mln', 'camp' => 'R_Ofc_Mln', 'color' => 'success', 'col' => 4],
                ['title' => 'Agp Ofc Mln', 'camp' => 'R_Ofc_Mln_Agp', 'color' => 'info', 'col' => 4],
                ['title' => 'Variance', 'camp_1' => 'R_Ofc_Mln', 'camp_2' => 'R_Ofc_Mln_Plan', 'camp_3' => 'R_Ofc_Mln_Agp', 'color' => 'Primary', 'col' => 12],
                ['title' => 'Plan Cc Ckm', 'camp' => 'R_Cc_Ckm_Plan', 'color' => 'primary', 'col' => 4],
                ['title' => 'Actual Cc Ckm', 'camp' => 'R_Cc_Ckm', 'color' => 'success', 'col' => 4],
                ['title' => 'Agp cc Ckm', 'camp' => 'R_Cc_Ckm_Agp', 'color' => 'info', 'col' => 4],
                ['title' => 'Variance', 'camp_1' => 'R_Cc_Ckm', 'camp_2' => 'R_Cc_Ckm_Plan', 'camp_3' => 'R_Cc_Ckm_Agp', 'color' => 'Primary', 'col' => 12],
                ['title' => 'Plan Cc Mln', 'camp' => 'R_Cc_Mln_Plan', 'color' => 'primary', 'col' => 4],
                ['title' => 'Actual Cc Mln', 'camp' => 'R_Cc_Mln', 'color' => 'success', 'col' => 4],
                ['title' => 'Agp cc Mln', 'camp' => 'R_Cc_Mln_Agp', 'color' => 'info', 'col' => 4],
                ['title' => 'Variance', 'camp_1' => 'R_Cc_Mln', 'camp_2' => 'R_Cc_Mln_Plan', 'camp_3' => 'R_Cc_Mln_Agp', 'color' => 'Primary', 'col' => 12],

            ],
        ];


        $anno = date('Y', strtotime($request->periodo));
        $mese = date('m', strtotime($request->periodo));
        $tipologia = $request->tipologia;

        $agpProduction = DashboardProduction::getTargetProduction($anno, $mese, 101);
        $agpRevenue = DashboardProduction::getTargetProduction($anno, $mese, 100);
        $productionOfc = DashboardProduction::getProductionData($anno, $mese, 1);
        $dispatch = DashboardProduction::getDispatchData($anno, $mese);
        $revenue = DashboardProduction::getRevenueData($anno, $mese);

        Log::channel('stderr')->info($agpRevenue);

        $tmp = ['Ofc_Ckm' => @$productionOfc['production_ckm_ottico_totale'], 'Ofc_Fkm' => @$productionOfc['production_kfkm_ottico_totale'], 'Cc_Ckm' => @$productionOfc['production_ckm_rame_totale'],
            'Ofc_Fkm_Plan' => @$agpProduction['kfkm_ofc']['valore'], 'Ofc_Ckm_Plan' => @$agpProduction['ckm_ofc']['valore'], 'Cc_Ckm_Plan' => @$agpProduction['ckm_cc']['valore'],
            'Ofc_Ckm_Agp' => @$agpProduction['ckm_ofc']['target'], 'Ofc_Fkm_Agp' => @$agpProduction['kfkm_ofc']['target'], 'Cc_Ckm_Agp' => @$agpProduction['ckm_cc']['target'],

            'D_Ofc_Ckm' => $dispatch['ckm_ofc']['valore'], 'D_Ofc_Fkm' => $dispatch['fkm_ofc']['valore'], 'D_Cc_Ckm' => $dispatch['ckm_cc']['valore'],
            'D_Ofc_Fkm_Plan' => @$agpProduction['kfkm_ofc']['valore'], 'D_Ofc_Ckm_Plan' => @$agpProduction['ckm_ofc']['valore'], 'D_Cc_Ckm_Plan' => @$agpProduction['ckm_cc']['valore'],
            'D_Cc_Mln' => round($dispatch['value_cc']['valore'] / 1000000, 2), 'D_Ofc_Mln' => round($dispatch['value_ofc']['valore'] / 1000000, 2),
            'D_Cc_Mln_Plan' => @$agpProduction['value_cc']['valore'], 'D_Ofc_Mln_Plan' => @$agpProduction['value_ofc']['valore'],
            'D_Ofc_Mln_Agp' => @$agpProduction['value_ofc']['target'], 'D_Cc_Mln_Agp' => @$agpProduction['value_cc']['target'], 'D_Ofc_Ckm_Agp' => @$agpProduction['ckm_ofc']['target'], 'D_Ofc_Fkm_Agp' => @$agpProduction['kfkm_ofc']['target'], 'D_Cc_Ckm_Agp' => @$agpProduction['ckm_cc']['target'],

            'R_Ofc_Ckm' => $revenue['ckm_ofc']['valore'], 'R_Ofc_Fkm' => $revenue['fkm_ofc']['valore'], 'R_Cc_Ckm' => round($revenue['ckm_cc']['valore'],0),
            'R_Ofc_Fkm_Plan' => @$agpRevenue['kfkm_ofc']['value'], 'R_Ofc_Ckm_Plan' => @$agpRevenue['ckm_ofc']['value'], 'R_Cc_Ckm_Plan' => @$agpRevenue['ckm_cc']['value'],
            'R_Cc_Mln' => round($revenue['value_cc']['valore'] / 1000000, 2), 'R_Ofc_Mln' => round($revenue['value_ofc']['valore'] / 1000000, 2),
            'R_Cc_Mln_Plan' => $agpRevenue['value_cc']['valore'], 'R_Ofc_Mln_Plan' => $agpRevenue['value_ofc']['valore'],
            'R_Ofc_Mln_Agp' => @$agpRevenue['value_ofc']['target'], 'R_Cc_Mln_Agp' => @$agpRevenue['value_cc']['target'], 'R_Ofc_Ckm_Agp' => @$agpRevenue['ckm_ofc']['target'], 'R_Ofc_Fkm_Agp' => @$agpRevenue['kfkm_ofc']['target'], 'R_Cc_Ckm_Agp' => @$agpRevenue['ckm_cc']['target'],

        ];

        $array = [];
        foreach ($definizione['Production'] as $key => $def) {
            if ($def['title'] != 'Variance')
                $array['produzione'][] = [
                    'title' => $def['title'],
                    'value' => $tmp[$def['camp']],
                    'color' => $def['color'],
                    'col' => $def['col'],
                ];
            else {
                $camp = $tmp[$def['camp_2']];
                if (empty($tmp[$def['camp_2']]))
                    $camp = $tmp[$def['camp_3']];
                $array['produzione'][] = [
                    'title' => $def['title'],
                    'value' => '',
                    'color' => $def['color'],
                    'col' => $def['col'],
                    'series' => [['data' => [$tmp[$def['camp_1']] - $camp], 'name' => 'Variance']]
                ];
            }

        }

        foreach ($definizione['Dispatch'] as $key => $def) {
            if ($def['title'] != 'Variance')
                $array['spedito'][] = [
                    'title' => $def['title'],
                    'value' => $tmp[$def['camp']],
                    'color' => $def['color'],
                    'col' => $def['col'],
                ];
            else {

                $camp = $tmp[$def['camp_2']];
                if (empty($tmp[$def['camp_2']]))
                    $camp = $tmp[$def['camp_3']];
                $array['spedito'][] = [
                    'title' => $def['title'],
                    'value' => '',
                    'color' => $def['color'],
                    'col' => $def['col'],
                    'series' => [['data' => [round($tmp[$def['camp_1']] - $camp, 2)], 'name' => 'Variance']]
                ];
            }

        }

        foreach ($definizione['Revenue'] as $key => $def) {

            if ($def['title'] != 'Variance')
                $array['fatturato'][] = [
                    'title' => $def['title'],
                    'value' => $tmp[$def['camp']],
                    'color' => $def['color'],
                    'col' => $def['col'],
                ];
            else {
                $camp = $tmp[$def['camp_2']];
                if (empty($tmp[$def['camp_2']]))
                    $camp = $tmp[$def['camp_3']];
                $array['fatturato'][] = [
                    'title' => $def['title'],
                    'value' => '',
                    'color' => $def['color'],
                    'col' => $def['col'],
                    'series' => [['data' => [round($tmp[$def['camp_1']] - $camp, 2)], 'name' => 'Variance']]
                ];
            }

        }

        return response()->json($array);
    }

    public function revenue(Request $request)
    {
        $anno = date('Y', strtotime($request->periodo));
        $mese = date('m', strtotime($request->periodo));

        $yearEnd = $anno;
        if ($mese <= 3)
            $anno = date('Y', strtotime($request->periodo . ' -1 Years'));
        else
            $yearEnd = $yearEnd + 1;


        $fatturato = DB::table('targets')
            ->select('titolo', 'valore', 'target', 'data_riferimento')
            ->where('tipo', 1)
            ->whereBetween('data_riferimento', [$anno . '-04-01', $yearEnd . '-03-31'])
            ->whereIn('titolo', ['ckm_ofc', 'ckm_cc', 'fkm_ofc', 'value_ofc', 'value_cc'])
            ->orderBy('data_riferimento', 'asc')
            ->orderBy('titolo', 'asc')
            ->get();


        $objs = DB::table('targets')
            ->select('titolo', 'valore', 'target', 'data_riferimento')
            ->where('tipo', 100)
            ->whereBetween('data_riferimento', [$anno . '-04-01', $yearEnd . '-03-31'])
            ->whereIn('titolo', ['ckm_cc', 'ckm_ofc', 'inr_cc', 'inr_ofc', 'kfkm_ofc', 'value_cc', 'value_ofc'])
            ->orderBy('data_riferimento', 'asc')
            ->orderBy('titolo', 'asc')
            ->get();


        $return = [];
        $posizioni = ['Apr' => 1, 'May' => 2, 'Jun' => 3, 'Jul' => 5, 'Aug' => 6, 'Sep' => 7, 'Oct' => 9, 'Nov' => 10, 'Dec' => 11, 'Jan' => 13, 'Feb' => 14, 'Mar' => 15,];
        foreach ($objs as $obj) {
            if ($obj->titolo == 'kfkm_ofc')
                $titolo = 'fkm_ofc';
            elseif ($obj->titolo == 'inr_ofc')
                $titolo = 'value_ofc';
            elseif ($obj->titolo == 'inr_cc')
                $titolo = 'value_cc';
            else
                $titolo = $obj->titolo;
            $tmp = $fatturato->where('data_riferimento', $obj->data_riferimento)->where('titolo', $titolo)->first();
            $rif = date('M', strtotime($obj->data_riferimento));

            $return[$posizioni[$rif]]['mese'] = $rif;
            $return[$posizioni[$rif]]['posizione'] = $posizioni[$rif];
            $return[$posizioni[$rif]]['agp'] = true;
            if (!empty($tmp->valore))
                $return[$posizioni[$rif]]['agp'] = null;
            if ($titolo == 'ckm_ofc' || $titolo == 'ckm_cc') {
                $return[$posizioni[$rif]][$obj->titolo] = [
                    'valore' => round((!empty($tmp->valore) ? $tmp->valore : $obj->valore), 0),
                    'target' => round($obj->target, 0),

                ];
            } elseif ($titolo == 'value_ofc' || $titolo == 'value_cc') {
                $return[$posizioni[$rif]][$obj->titolo] = [
                    'valore' => round((!empty($tmp->valore) ? $tmp->valore / 1000000 : $obj->valore), 2),
                    'target' => round($obj->target, 2),
                ];

            } elseif ($titolo == 'fkm_ofc') {
                $return[$posizioni[$rif]][$titolo] = [
                    'valore' => round((!empty($tmp->valore) ? $tmp->valore / 1000 : $obj->valore), 0),
                    'target' => round($obj->target, 0),

                ];
            } elseif ($titolo == 'inr_cc' || $titolo == 'inr_ofc') {

                $return[$posizioni[$rif]][$obj->titolo] = [
                    'valore' => round((!empty($tmp->valore) ? ($tmp->valore * 89.5) / 10 : $obj->valore), 0),
                    'target' => round($obj->target, 0),
                ];
            }

        }

        ksort($return);


        $i = 1;
        $q_n = 1;
        $totali = ['cc_ckm_v' => 0, 'cc_ckm_t' => 0, 'ofc_ckm_v' => 0, 'ofc_ckm_t' => 0, 'ofc_fkm_v' => 0, 'ofc_fkm_t' => 0, 'cc_value_v' => 0, 'cc_value_t' => 0, 'ofc_value_v' => 0.00, 'ofc_value_t' => 0, 'ofc_inr_v' => 0.00, 'ofc_inr_t' => 0, 'cc_inr_v' => 0.00, 'cc_inr_t' => 0];
        foreach ($return as $q) {
            if ($i == 4 || $i == 8 || $i == 12 || $i == 16) {
                $return[$i] = [
                    'mese' => 'Total Q' . $q_n++,
                    'posizione' => $i,
                    'ckm_cc' => ['valore' => round($totali['cc_ckm_v'], 0), 'target' => round($totali['cc_ckm_t'], 0)],
                    'ckm_ofc' => ['valore' => round($totali['ofc_ckm_v'], 0), 'target' => round($totali['ofc_ckm_t'], 0)],
                    'fkm_ofc' => ['valore' => round($totali['ofc_fkm_v'], 0), 'target' => round($totali['ofc_fkm_t'], 0)],
                    'value_cc' => ['valore' => round($totali['cc_value_v'], 2), 'target' => round($totali['cc_value_t'], 2)],
                    'value_ofc' => ['valore' => round($totali['ofc_value_v'], 2), 'target' => round($totali['ofc_value_t'], 2)],
                    'inr_cc' => ['valore' => round($totali['cc_inr_v'], 2), 'target' => round($totali['cc_inr_t'], 2)],
                    'inr_ofc' => ['valore' => round($totali['ofc_inr_v'], 2), 'target' => round($totali['ofc_inr_t'], 2)],
                ];
                $totali = ['cc_ckm_v' => 0, 'cc_ckm_t' => 0, 'ofc_ckm_v' => 0, 'ofc_ckm_t' => 0, 'ofc_fkm_v' => 0, 'ofc_fkm_t' => 0, 'cc_value_v' => 0, 'cc_value_t' => 0, 'ofc_value_v' => 0.00, 'ofc_value_t' => 0, 'ofc_inr_v' => 0.00, 'ofc_inr_t' => 0, 'cc_inr_v' => 0.00, 'cc_inr_t' => 0];
                $i++;
            }

            $totali['cc_ckm_v'] += $q['ckm_cc']['valore'];
            $totali['cc_ckm_t'] += $q['ckm_cc']['target'];
            $totali['ofc_ckm_v'] += $q['ckm_ofc']['valore'];
            $totali['ofc_ckm_t'] += $q['ckm_ofc']['target'];
            $totali['ofc_fkm_v'] += $q['fkm_ofc']['valore'];
            $totali['ofc_fkm_t'] += $q['fkm_ofc']['target'];
            $totali['cc_value_v'] += $q['value_cc']['valore'];
            $totali['cc_value_t'] += $q['value_cc']['target'];
            $totali['ofc_value_v'] += $q['value_ofc']['valore'];
            $totali['ofc_value_t'] += $q['value_ofc']['target'];
            $totali['ofc_inr_v'] += $q['inr_ofc']['valore'];
            $totali['ofc_inr_t'] += $q['inr_ofc']['target'];
            $totali['cc_inr_v'] += $q['inr_cc']['valore'];
            $totali['cc_inr_t'] += $q['inr_cc']['target'];


            $i++;
        }

        $return[$i] = [
            'mese' => 'Total Q' . $q_n++,
            'posizione' => $i,
            'agp' => (!empty($return[4]['ckm_cc']['agp']) ? true : ''),
            'ckm_cc' => ['valore' => round($totali['cc_ckm_v'], 0), 'target' => round($totali['cc_ckm_t'], 0)],
            'ckm_ofc' => ['valore' => round($totali['ofc_ckm_v'], 0), 'target' => round($totali['ofc_ckm_t'], 0)],
            'fkm_ofc' => ['valore' => round($totali['ofc_fkm_v'], 0), 'target' => round($totali['ofc_fkm_t'], 0)],
            'value_cc' => ['valore' => round($totali['cc_value_v'], 2), 'target' => round($totali['cc_value_t'], 2)],
            'value_ofc' => ['valore' => round($totali['ofc_value_v'], 2), 'target' => round($totali['ofc_value_t'], 2)],
            'inr_cc' => ['valore' => round($totali['cc_inr_v'], 2), 'target' => round($totali['cc_inr_t'], 2)],
            'inr_ofc' => ['valore' => round($totali['ofc_inr_v'], 2), 'target' => round($totali['ofc_inr_t'], 2)],
        ];
        $i++;
        $return[$i] = [
            'mese' => 'Total',
            'posizione' => $i,
            'ckm_cc' => ['valore' => round(@$return[4]['ckm_cc']['valore'] + @$return[8]['ckm_cc']['valore'] + @$return[12]['ckm_cc']['valore'] + @$return[16]['ckm_cc']['valore'], 0),
                'target' => round(@$return[4]['ckm_cc']['target'] + @$return[8]['ckm_cc']['target'] + @$return[12]['ckm_cc']['target'] + @$return[16]['ckm_cc']['target'], 0)],
            'ckm_ofc' => ['valore' => round(@$return[4]['ckm_ofc']['valore'] + @$return[8]['ckm_ofc']['valore'] + @$return[12]['ckm_ofc']['valore'] + @$return[16]['ckm_ofc']['valore'], 0),
                'target' => round(@$return[4]['ckm_ofc']['target'] + @$return[8]['ckm_ofc']['target'] + @$return[12]['ckm_ofc']['target'] + @$return[16]['ckm_ofc']['target'], 0)],
            'fkm_ofc' => ['valore' => round(@$return[4]['fkm_ofc']['valore'] + @$return[8]['fkm_ofc']['valore'] + @$return[12]['fkm_ofc']['valore'] + @$return[16]['fkm_ofc']['valore'], 0),
                'target' => round(@$return[4]['fkm_ofc']['target'] + @$return[8]['fkm_ofc']['target'] + @$return[12]['fkm_ofc']['target'] + @$return[16]['fkm_ofc']['target'], 0)],
            'value_cc' => ['valore' => round(@$return[4]['value_cc']['valore'] + @$return[8]['value_cc']['valore'] + @$return[12]['value_cc']['valore'] + @$return[16]['value_cc']['valore'], 2),
                'target' => round(@$return[4]['value_cc']['target'] + @$return[8]['value_cc']['target'] + @$return[12]['value_cc']['target'] + @$return[16]['value_cc']['target'], 2)],
            'value_ofc' => ['valore' => round(@$return[4]['value_ofc']['valore'] + @$return[8]['value_ofc']['valore'] + @ $return[12]['value_ofc']['valore'] + @$return[16]['value_ofc']['valore'], 2),
                'target' => round(@$return[4]['value_ofc']['target'] + @$return[8]['value_ofc']['target'] + @$return[12]['value_ofc']['target'] + @ $return[16]['value_ofc']['target'], 2)],
            'inr_ofc' => ['valore' => round(@$return[4]['inr_ofc']['valore'] + @$return[8]['inr_ofc']['valore'] + @ $return[12]['inr_ofc']['valore'] + @$return[16]['inr_ofc']['valore'], 2),
                'target' => round(@$return[4]['inr_ofc']['target'] + @$return[8]['inr_ofc']['target'] + @$return[12]['inr_ofc']['target'] + @ $return[16]['inr_ofc']['target'], 2)],
            'inr_cc' => ['valore' => round(@$return[4]['inr_cc']['valore'] + @$return[8]['inr_cc']['valore'] + @ $return[12]['inr_cc']['valore'] + @$return[16]['inr_cc']['valore'], 2),
                'target' => round(@$return[4]['inr_cc']['target'] + @$return[8]['inr_cc']['target'] + @$return[12]['inr_cc']['target'] + @ $return[16]['inr_cc']['target'], 2)],

        ];
        ksort($return);
        return response()->json($return);

    }

    public function production(Request $request)
    {
        $anno = date('Y', strtotime($request->periodo));
        $mese = date('m', strtotime($request->periodo));

        $def = [
            1 => ['April', 'May', 'June'],
            2 => ['July', 'August', 'September'],
            3 => ['October', 'November', 'December'],
            4 => ['January', 'February', 'March'],
        ];
        $month = ceil(date('n', strtotime($request->periodo)) / 3);
        $q = 0;
        if ($month == 1) $q = 4;
        if ($month == 2) $q = 1;
        if ($month == 3) $q = 2;
        if ($month == 4) $q = 3;
        $result = [];
        foreach ($def[$q] as $t) {
            $m = date('m', strtotime($anno . ' ' . $t));
            $rif = date('M', strtotime($anno . ' ' . $t));

            $ultimoGiorno = date("d", strtotime(date("Y-m-t", strtotime($anno . ' ' . $t))));
            $productionOfc = Gp::totaleDatiProduzione('sf', [$anno . '.' . $m . '-01', $anno . '.' . $m . '-' . $ultimoGiorno]);
            $productionCc = Gp::totaleDatiProduzione('f', [$anno . '.' . $m . '-01', $anno . '.' . $m . '-' . $ultimoGiorno]);
            $kg = DB::connection('mysql_old')->table('plant_costs')
                ->select('*')
                ->where('year', $anno)
                ->where('month', $m)
                ->where('cc_kg','<>',0)
                ->first();

            $result[$rif] = [
                'mese' => $rif,
                'posizione' => (int)$m,
                'Cc_ckm' => round(!empty($productionCc->quantita) ? $productionCc->quantita : 0, 0),
                'Cc_kg' => round(!empty($kg->cc_kg) ? $kg->cc_kg : 0, 0),
                'Ckm_ofc' => round(!empty($productionOfc->quantita) ? $productionOfc->quantita : 0, 0),
                'Fkm_ofc' => round(!empty($productionOfc->fkm) ? $productionOfc->fkm / 1000 : 0, 0),
                'Ofc_afc' => round(!empty($productionOfc->fkm) ? $productionOfc->fkm / $productionOfc->quantita : 0, 0),
            ];

        }


        $result[100] = [
            'mese' => 'Total',
            'posizione' => 100,
            'Cc_ckm' => array_sum(array_column($result, 'Cc_ckm')),
            'Cc_kg' => array_sum(array_column($result, 'Cc_kg')),
            'Ckm_ofc' => array_sum(array_column($result, 'Ckm_ofc')),
            'Fkm_ofc' => array_sum(array_column($result, 'Fkm_ofc')),
            //'Ofc_afc' => round((array_sum(array_column($result, 'Fkm_ofc')) * 1000) / array_sum(array_column($result, 'Ckm_ofc')), 0),
        ];

        if (array_sum(array_column($result, 'Ckm_ofc')))
            $result[100]['Ofc_afc'] = round((array_sum(array_column($result, 'Fkm_ofc')) * 1000) / array_sum(array_column($result, 'Ckm_ofc')), 0);
        else
            $result[100]['Ofc_afc'] = 0;

        return response()->json($result);
    }

    public function dispatch(Request $request)
    {
        $anno = date('Y', strtotime($request->periodo));
        $mese = date('m', strtotime($request->periodo));

        $def = [
            1 => ['April', 'May', 'June'],
            2 => ['July', 'August', 'September'],
            3 => ['October', 'November', 'December'],
            4 => ['January', 'February', 'March'],
        ];
        $month = ceil(date('n', strtotime($request->periodo)) / 3);
        $q = 0;
        if ($month == 1) $q = 4;
        if ($month == 2) $q = 1;
        if ($month == 3) $q = 2;
        if ($month == 4) $q = 3;
        $result = [];
        foreach ($def[$q] as $t) {
            $m = date('m', strtotime($anno . ' ' . $t));
            $rif = date('M', strtotime($anno . ' ' . $t));
            $dispatch = DashboardProduction::getDispatchData($anno, $m);

            $result[$rif] = [
                'mese' => $rif,
                'posizione' => (int)$m,
                'Cc_ckm' => round($dispatch['ckm_cc']['valore'], 0),
                'Cc_valore' => round($dispatch['value_cc']['valore'] / 1000000, 2),
                'Ckm_ofc' => $dispatch['ckm_ofc']['valore'],
                'Fkm_ofc' => $dispatch['fkm_ofc']['valore'],
                'Ofc_valore' => round($dispatch['value_ofc']['valore'] / 1000000, 2),
            ];

        }
        $result[100] = [
            'mese' => 'Total',
            'posizione' => 100,
            'Cc_ckm' => array_sum(array_column($result, 'Cc_ckm')),
            'Cc_valore' => array_sum(array_column($result, 'Cc_valore')),
            'Ckm_ofc' => array_sum(array_column($result, 'Ckm_ofc')),
            'Fkm_ofc' => array_sum(array_column($result, 'Fkm_ofc')),
            'Ofc_valore' => round(array_sum(array_column($result, 'Ofc_valore')), 2),
        ];

        return response()->json($result);

    }

    public function inventory(Request $request)
    {
        $t = date('Y-m-01 10:00:00', strtotime($request->periodo));
        $dateStart = (new Carbon($request->periodo))->subMonths(6)->format('Y-m-d');
        $annoAnd = date('Y', strtotime($request->periodo));
        $meseAnd = date('m', strtotime($request->periodo));
        $objs = DB::table('pr_warehouse_heads')
            ->select('id', 'data_riferimento')
            ->whereBetween('data_riferimento', [$dateStart, $annoAnd . '-' . $meseAnd . '-20'])
            ->get();

        $return = [];
        $s = [];
        $cat = [];
        $total = [];
        $i = 1;
        foreach ($objs as $obj) {
            $materials = DB::table('pr_warehouse_rows')->select('materiale as material', 'descrizione as description', 'valore_unitario as unitary_value', 'quantita as total_stock', 'ultimo_movimento as last_gds_mvmt', 'valore_totale as total_value', 'crcy as bun')
                ->where('warehouse_id', $obj->id)
                ->get();

            $values = ['valore_ofc' => 0, 'valore_cc' => 0];
            $material_class = null;
            $total[$i] = 0.00;
            foreach ($materials as $key => $material) {
                $result = PrWarehouseRows::processing((array)$material);
                //Log::channel('stderr')->info($result['material_class']);
                $values['valore_ofc'] += $result['values']['valore_ofc'];
                $values['valore_cc'] += $result['values']['valore_cc'];


                if (!empty($material_class[$result['class']])) {
                    $material_class[$result['class']]['valore'] += $result['material_class']['valore'];
                } else {
                    $material_class[$result['class']]['valore'] = $result['material_class']['valore'];
                }
            }
            $rif = date('M-y', strtotime($obj->data_riferimento));
            $cat[$rif] = $rif;
            $s['Finished Products CC'][] = round($material_class['Finished Products CC']['valore'] / 1000000, 2);
            $s['Finished Products OFC'][] = round($material_class['Finished Products OFC']['valore'] / 1000000, 2);
            $s['WIP CC'][] = round($material_class['WIP CC']['valore'] / 1000000, 2);
            $s['WIP OFC'][] = round($material_class['WIP OFC']['valore'] / 1000000, 2);

            $total[$i] += $return['dati']['Fiber Optics OFC'][] = round($material_class['Fiber Optics OFC']['valore'] / 1000000, 2);
            $total[$i] += $return['dati']['Finished Products CC'][] = round($material_class['Finished Products CC']['valore'] / 1000000, 2);
            $total[$i] += $return['dati']['Finished Products OFC'][] = round($material_class['Finished Products OFC']['valore'] / 1000000, 2);
            $total[$i] += $return['dati']['Packaging'][] = round($material_class['Packaging']['valore'] / 1000000, 2);
            $total[$i] += $return['dati']['Raw Materials CC'][] = round($material_class['Raw Materials CC']['valore'] / 1000000, 2);
            $total[$i] += $return['dati']['Raw Materials OFC'][] = round($material_class['Raw Materials OFC']['valore'] / 1000000, 2);
            $total[$i] += $return['dati']['WIP CC'][] = round($material_class['WIP CC']['valore'] / 1000000, 2);
            $total[$i] += $return['dati']['WIP OFC'][] = round($material_class['WIP OFC']['valore'] / 1000000, 2);

            $i++;
        }
        for ($a = 1; $a < $i; $a++)
            $return['dati']['Total'][] = round($total[$a], 2);

        $return['series'] = [
            ['name' => 'Finished Products CC', 'data' => $s['Finished Products CC']],
            ['name' => 'Finished Products OFC', 'data' => $s['Finished Products OFC']],
            ['name' => 'WIP CC', 'data' => $s['WIP CC']],
            ['name' => 'WIP OFC', 'data' => $s['WIP OFC']],
        ];
        $return['categories'] = array_values($cat);

        return response()->json($return);
    }

    public function datiProduttivi(Request $request)
    {

        $dateStart = (new Carbon($request->periodo))->subMonths(6)->format('Y-m-d');
        $annoAnd = date('Y', strtotime($request->periodo));
        $meseAnd = date('m', strtotime($request->periodo));
        $ultimoGiorno = date("d", strtotime(date("Y-m-t", strtotime($annoAnd . '-' . $meseAnd))));

        $categoryes = [];

        $objs = DB::connection('mysql_old')->table('plant_costs')
            ->select('cc_kg', 'cc_ckm_production', 'year', 'month')
            ->whereBetween('data_creazione', [$dateStart, $annoAnd . '-' . $meseAnd . '-' . $ultimoGiorno])
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $tmpA = [];
        foreach ($objs as $obj) {
            $rif = date('M-y', strtotime($obj->year . '-' . $obj->month . '-01'));

            $tmpA['kg'][strtotime($obj->year . '-' . $obj->month . '-01')] = round($obj->cc_kg, 0);
            // $tmpA['ckm'][strtotime($obj->year.'-'.$obj->month.'-01')] = round($obj->cc_ckm_production, 1);
            $categoryes[strtotime($obj->year . '-' . $obj->month . '-01')] = $rif;
            //$series['cc']['kg']['data'][] = round($obj->cc_kg,0);
            //$series['cc']['ckm']['data'][] = round($obj->cc_ckm_production, 1);
        }


        $dataBy = [$dateStart, $annoAnd . '-' . $meseAnd . '-' . $ultimoGiorno];

        $finishedProductOfcTotal = Gp::totaleDatiProduzione('sf', $dataBy);
        $finishedProductCcTotal = Gp::totaleDatiProduzione('f', $dataBy);

        $tmpB = [];
        foreach ($finishedProductOfcTotal as $month) {
            $tmpB['kfkm'][strtotime($month->Periodo . '-01')] = round($month->fkm / 1000, 0);
            $tmpB['ckm'][strtotime($month->Periodo . '-01')] = round($month->quantita, 0);
        }
        foreach ($finishedProductCcTotal as $month)
            $tmpA['ckm'][strtotime($month->Periodo . '-01')] = round($month->quantita, 0);


        ksort($tmpB['kfkm']);
        ksort($tmpB['ckm']);
        ksort($categoryes);
        ksort($tmpA['kg']);
        ksort($tmpA['ckm']);
        $series['cc'] = [
            'kg' => ['name' => 'Kg', 'type' => 'column', 'data' => array_values($tmpA['kg'])],
            'ckm' => ['name' => 'Ckm', 'type' => 'line', 'data' => array_values($tmpA['ckm'])],
        ];
        //Log::channel('stderr')->info([$dateStart . ' 00:00:00:000', $annoAnd.'-'.$meseAnd.'-'.$ultimoGiorno . ' 23:59:59:990']);
        //Log::channel('stderr')->info($dataBy);
        $series['ofc'] = [
            'kfkm' => ['name' => 'KfKm', 'type' => 'column', 'data' => array_values($tmpB['kfkm'])],
            'ckm' => ['name' => 'Ckm', 'type' => 'line', 'data' => array_values($tmpB['ckm'])],
        ];
        return response()->json(['series' =>
            ['cc' => [$series['cc']['kg'], $series['cc']['ckm']],
                'ofc' => [$series['ofc']['kfkm'], $series['ofc']['ckm']],
                'categoryes' => array_values($categoryes)
            ]
        ]);
    }

    public function datiSceep(Request $request)
    {
        $result = [];
        $categorie = [];
        for ($i = 0; $i <= 11; $i++) {
            $mese = (new Carbon($request->periodo))->subMonths($i)->format('m');
            $anno = (new Carbon($request->periodo))->subMonths($i)->format('Y');

            $obj = DB::connection('mysql_old')->table('report_production_scraps')
                ->select(DB::raw("CONCAT( Year(date_a),'-',MONTH(date_a)) AS Periodo"), 'overall')
                ->whereYear('date_a', $anno)
                ->whereMonth('date_a', $mese)
                ->orderBy('date_a', 'desc')
                ->first();

            if (!empty($obj->Periodo)) {
                $result[strtotime($obj->Periodo . '-01')] = $obj->overall;
                $categorie[strtotime($obj->Periodo . '-01')] = date('M-y', strtotime($obj->Periodo . '-01'));
            } else {
                $result[strtotime($anno . '-' . $mese . '-01')] = 0;
                $categorie[strtotime($anno . '-' . $mese . '-01')] = date('M-y', strtotime($anno . '-' . $mese . '-01'));
            }

        }
        ksort($result);
        ksort($categorie);
        $return['series'] = [
            ['name' => 'Scrap', 'data' => array_values($result)],
        ];

        $return['categories'] = array_values($categorie);

        return response()->json($return);
    }

    public function datiSceepStage(Request $request)
    {
        $result = [];
        $categorie = [];

        $dataStart = date('Y-m', strtotime($request->periodo));
        $data[0] = $dataStart.'-'.date('t', strtotime($request->periodo));
        $data[1] = date('Y-m', strtotime($request->periodo . ' -2 Months')).'-01';
        $objs = DB::connection('mysql_old')->table('report_production_scraps')
            ->select(DB::raw("CONCAT( Year(date_a),'-',MONTH(date_a)) AS Periodo"), 'report_production_scraps.*')
            ->where('date_da','>=',$data[1])
            ->where('date_a','<=',$data[0])
            ->orderBy('date_a', 'asc')
            ->get();

        $week = 0;
        $m = '';
        foreach ($objs as $obj){
            $mese = date('m',strtotime($obj->Periodo . '-01'));
            $anno = date('Y',strtotime($obj->Periodo . '-01'));
            if($mese != $m){
                $m = $mese;
                $week = 1;

                $categorie[strtotime($obj->date_a.' -1 day')] = '';
                $result['BUFFERING'][strtotime($obj->date_a.' -1 day' )] = null;
                $result['SHEATHING'][strtotime($obj->date_a.' -1 day' )] = null;
                $result['STRANDING'][strtotime($obj->date_a.' -1 day' )] = null;
                $result['MATERIAL'][strtotime($obj->date_a.' -1 day' )] = null;
                $result['COPPER'][strtotime($obj->date_a.' -1 day' )] = null;
            }

            $categorie[strtotime($obj->date_a)] = $anno.'-'.$mese.'  Week '.$week;
            $result['BUFFERING'][strtotime($obj->date_a )] = round($obj->buffering,0);
            $result['SHEATHING'][strtotime($obj->date_a )] = round($obj->sheathing,0);
            $result['STRANDING'][strtotime($obj->date_a )] = round($obj->stranding,0);
            $result['MATERIAL'][strtotime($obj->date_a )] = round($obj->raw + $obj->other + $obj->coloring,0);
            $result['COPPER'][strtotime($obj->date_a )] = round($obj->others - $obj->total,0);

            $week++;
        }
        ksort($categorie);
        ksort($result['BUFFERING']);
        ksort($result['SHEATHING']);
        ksort($result['STRANDING']);
        ksort( $result['MATERIAL']);
        ksort( $result['COPPER']);

        $return['series'] = [
            ['name' => 'BUFFERING', 'data' => array_values($result['BUFFERING'])],
            ['name' => 'SHEATHING', 'data' =>  array_values($result['SHEATHING'])],
            ['name' => 'STRANDING', 'data' => array_values($result['STRANDING'])],
            ['name' => 'ROW MATERIAL', 'data' => array_values($result['MATERIAL'])],
            ['name' => 'COPPER', 'data' =>  array_values($result['COPPER'])],

        ];

        $return['categories'] = array_values($categorie);

        return response()->json($return);
    }

    public function datiOoe(Request $request)
    {
        $series = [];
        $t_cat = [];
        for ($mes = 0; $mes <= 11; $mes++) {
            $mese = (new Carbon($request->periodo))->subMonths($mes)->format('m');
            $anno = (new Carbon($request->periodo))->subMonths($mes)->format('Y');


            $total_oee = ['jacketing' => 0.0,
                'buffering' => 0.0,
                'stranding' => 0.0];

            $ftrObj = DB::connection('mysql_old')->table('report_ftrs')
                ->where('month', '=', $mese)
                ->where('year', '=', $anno)
                ->where('reference', '<>', 'coloring')
                ->orderBy('reference')
                ->get();


            foreach ($ftrObj as $ftr)
                $total_oee[$ftr->reference] = $ftr->value;


            $runTime = DB::connection('mysql_old')->table('report_run_times')
                ->select('*')
                ->where('month', '=', $mese)
                ->where('year', '=', $anno)
                ->where('report_reference', '<>', 'coloring')
                ->whereNull('machinery')
                ->orderBy('report_reference')
                ->get();

            $teams = DB::connection('mysql_old')->table('report_teams')->get();
            $calAv = [];
            $ref = [];
            foreach ($teams as $team) {
                $totalAvTream = DB::connection('mysql_old')->table('report_team_av_totals')
                    ->select('*')
                    ->where('month', '=', $mese)
                    ->where('year', '=', $anno)
                    ->where('team', '=', $team->id)
                    ->where('reference', '<>', 'coloring')
                    ->orderBy('reference')->get();

                foreach ($totalAvTream as $avTeam) {
                    $ref[$avTeam->reference] = $avTeam->reference;
                    if (empty($calAv[$avTeam->reference])) {
                        $calAv[$avTeam->reference]['val'] = 0.0;
                        $calAv[$avTeam->reference]['n'] = 0;
                    }

                    $calAv[$avTeam->reference]['n'] = $calAv[$avTeam->reference]['n'] + 1;
                    $calAv[$avTeam->reference]['val'] += $avTeam->value;
                }
            }

            $i = 0;
            ksort($ref);
            foreach ($ref as $t) {
                $tot_temp = round($calAv[$t]['val'] / $calAv[$t]['n'], 1);
                $total_oee[$t] = round($total_oee[$t] * $tot_temp, 1);
            }

            foreach ($runTime as $row)
                $total_oee[$row->report_reference] = round($total_oee[$row->report_reference] * $row->value, 1);


            $n = 0;
            $t_final = 0.0;

            foreach ($total_oee as $key => $total) {
                //$t[] = round($total / 10000, 1);
                $t_final += round($total / 10000, 1);
                // $t_cat[] = $key;
                if ($total)
                    $n++;
            }
            if ($n) {
                $series[strtotime($anno . '-' . $mese . '-01')] = round($t_final / $n, 1);

            }

            $t_cat[strtotime($anno . '-' . $mese . '-01')] = date('M-y', strtotime($anno . '-' . $mese . '-01'));


        }

        ksort($t_cat);
        ksort($series);
        $return['categories'] = array_values($t_cat);
        $return['series'] = [
            ['name' => 'total', 'data' => array_values($series)],
        ];
        // Log::channel('stderr')->info($return);
        return response()->json($return);
    }

    public function datiFtr(Request $request)
    {
        $series = [];
        $t_cat = [];
        for ($mes = 0; $mes <= 11; $mes++) {
            $mese = (new Carbon($request->periodo))->subMonths($mes)->format('m');
            $anno = (new Carbon($request->periodo))->subMonths($mes)->format('Y');

            $buf_count = DB::table('qt_checker_reports')
                ->select('ol', 'coil', DB::raw('COUNT(DISTINCT coil) as bob'))
                ->where('stage', 'BUF')
                ->whereYear('date_create', $anno)
                ->whereMonth('date_create', $mese)
                ->groupBy(['ol', 'coil'])
                ->get();

            $str_count = DB::table('qt_checker_reports')
                ->select('ol', 'coil', DB::raw('COUNT(DISTINCT coil) as bob'))
                ->where('stage', 'SZ')
                ->whereYear('date_create', $anno)
                ->whereMonth('date_create', $mese)
                ->groupBy(['ol', 'coil'])
                ->get();

            $jac_count = DB::table('qt_checker_reports')
                ->select('ol', 'coil', DB::raw('COUNT(DISTINCT coil) as bob'))
                ->whereIn('stage', ['FC', 'SF', 'PE'])
                ->whereYear('date_create', $anno)
                ->whereMonth('date_create', $mese)
                ->groupBy(['ol', 'coil'])
                ->get();

            $bobineTestate = $buf_count->count() + $str_count->count() + $jac_count->count();

            $nc_buf = DB::table('qt_conformitas')
                ->select('ol', 'bobina', DB::raw('COUNT(DISTINCT bobina) as bob'))
                ->where('stage', 'BUF')
                ->where('difetto', '<>', 20)
                ->where('motivazione_chiusura', '<>', 1)
                ->where('stato', 3)
                ->whereYear('data_apertura', $anno)
                ->whereMonth('data_apertura', $mese)
                ->groupBy(['ol', 'bobina'])
                ->get();

            $nc_sz = DB::table('qt_conformitas')
                ->select('ol', 'bobina', DB::raw('COUNT(DISTINCT bobina) as bob'))
                ->where('stage', 'SZ')
                ->where('difetto', '<>', 20)
                ->where('motivazione_chiusura', '<>', 1)
                ->where('stato', 3)
                ->whereYear('data_apertura', $anno)
                ->whereMonth('data_apertura', $mese)
                ->groupBy(['ol', 'bobina'])
                ->get();

            $nc_fc = DB::table('qt_conformitas')
                ->select('ol', 'bobina', DB::raw('COUNT(DISTINCT bobina) as bob'))
                ->where('stage', 'FC')
                ->where('difetto', '<>', 20)
                ->where('motivazione_chiusura', '<>', 1)
                ->where('stato', 3)
                ->whereYear('data_apertura', $anno)
                ->whereMonth('data_apertura', $mese)
                ->groupBy(['ol', 'bobina'])
                ->get();

            $nc_sf = DB::table('qt_conformitas')
                ->select('ol', 'bobina', DB::raw('COUNT(DISTINCT bobina) as bob'))
                ->where('stage', 'SF')
                ->where('difetto', '<>', 20)
                ->where('motivazione_chiusura', '<>', 1)
                ->where('stato', 3)
                ->whereYear('data_apertura', $anno)
                ->whereMonth('data_apertura', $mese)
                ->groupBy(['ol', 'bobina'])
                ->get();

            $nc_pe = DB::table('qt_conformitas')
                ->select('ol', 'bobina', DB::raw('COUNT(DISTINCT bobina) as bob'))
                ->where('stage', 'PE')
                ->where('difetto', '<>', 20)
                ->where('motivazione_chiusura', '<>', 1)
                ->where('stato', 3)
                ->whereYear('data_apertura', $anno)
                ->whereMonth('data_apertura', $mese)
                ->groupBy(['ol', 'bobina'])
                ->get();

            $no_good = $nc_fc->count() + $nc_sf->count() + $nc_pe->count() + $nc_sz->count() + $nc_buf->count();

            $series[strtotime($anno . '-' . $mese . '-01')] = (!empty($bobineTestate) ? round((($bobineTestate - $no_good) / $bobineTestate) * 100, 2) : 100);
            $t_cat[strtotime($anno . '-' . $mese . '-01')] = date('M-y', strtotime($anno . '-' . $mese . '-01'));

        }

        ksort($t_cat);
        ksort($series);
        $return['categories'] = array_values($t_cat);
        $return['series'] = [
            ['name' => 'FTR', 'data' => array_values($series)],
        ];
        return response()->json($return);
    }

    public function datiCapacity(Request $request)
    {
        $series = [];
        $t_cat = [];
        for ($mes = 0; $mes <= 11; $mes++) {
            $mese = (new Carbon($request->periodo))->subMonths($mes)->format('m');
            $anno = (new Carbon($request->periodo))->subMonths($mes)->format('Y');
            $giorno = '01';

            $ultimoGiorno = date("d", strtotime(date("Y-m-t", strtotime($anno . '-' . $mese))));

            $dataBy = [$anno . '-' . $mese . '-' . $giorno, $anno . '-' . $mese . '-' . $ultimoGiorno];
            $finishedProductOfcTotal = Gp::totaleDatiProduzione('sf', $dataBy);
            $kfkm = round($finishedProductOfcTotal->fkm / 1000, 1);

            $series[strtotime($anno . '-' . $mese . '-01')] = round(($kfkm * 12) / 1000, 1);
            $t_cat[strtotime($anno . '-' . $mese . '-01')] = date('M-y', strtotime($anno . '-' . $mese . '-01'));

        }
        ksort($t_cat);
        ksort($series);

        $return['categories'] = array_values($t_cat);
        $return['series'] = [
            ['name' => 'Capacity', 'data' => array_values($series)],
        ];
        return response()->json($return);
    }

    public function datiOvertime(Request $request)
    {
        $series = ['uno' => [], 'due' => []];
        $t_cat = [];

        $anno = (new Carbon($request->periodo))->format('Y');
        $mese = (new Carbon($request->periodo))->format('m');
        $annoDa = $anno - 1;
        $objsOne = DB::connection('mysql_old')->table('extraordinaries')
            ->select('reference', 'total_hours', 'total_hours_worked')
            ->whereYear('reference', $anno)
            ->whereMonth('reference', '<=', $mese)
            ->orderBy('month')
            ->get();

        $objsTwo = DB::connection('mysql_old')->table('extraordinaries')
            ->select('reference', 'total_hours', 'total_hours_worked')
            ->whereYear('reference', $anno - 1)
            ->whereMonth('reference', '<=', $mese)
            ->orderBy('month')
            ->get();

        foreach ($objsOne as $row) {
            $series['uno'][strtotime($row->reference)] = ($row->total_hours_worked > 0.00 ? round(($row->total_hours / $row->total_hours_worked) * 100, 2) : 0);
        }

        foreach ($objsTwo as $row) {
            $series['due'][strtotime($row->reference)] = ($row->total_hours_worked > 0.00 ? round(($row->total_hours / $row->total_hours_worked) * 100, 2) : 0);
            $t_cat[strtotime($row->reference)] = date('M', strtotime($row->reference));
        }

        ksort($series['uno']);
        ksort($series['due']);
        ksort($t_cat);
        $annoA = $anno - 1;
        $return['categories'] = array_values($t_cat);
        $return['series'] = [
            ['name' => $anno, 'data' => array_values($series['uno'])],
            ['name' => (string)$annoA, 'data' => array_values($series['due'])],
        ];

        return response()->json($return);
    }

    public function datiCost(Request $request)
    {

        $t_cat = [];
        $placeholder = [];
        $periodoDa = (new Carbon($request->periodo))->subMonths(11)->format('Y-m');
        $periodoA = (new Carbon($request->periodo))->subMonths(0)->format('Y-m');

        $ultimoGiorno = date("d", strtotime(date("Y-m-t", strtotime($request->periodo))));
        $productionOfc = Gp::totaleDatiProduzione('sf', [$periodoDa . '-01', $periodoA . '-' . $ultimoGiorno]);
        $productionCc = Gp::totaleDatiProduzione('f', [$periodoDa . '-01', $periodoA . '-' . $ultimoGiorno]);

        $powers = DB::connection('mysql_old')->table('plant_costs')
            ->select(
                DB::raw("CONCAT( Year(data_creazione),'-',MONTH(data_creazione)) AS Periodo"),
                DB::raw("SUM(of_eletric_power + of_utility_cost) AS of_eletric_power"),
                'manodopera', 'packaging', 'shipping', 'of_manpower_cost_op', 'cc_manpower_cost_op', 'of_manpower_cost_sup',
                'of_manpower_cost_temp', 'cc_manpower_cost_temp', 'manpower_cost_sup_temp','kwh','smc','of_eletric_power','of_utility_cost')
            ->whereBetween('data_creazione', [$periodoDa . '-01', $periodoA . '-' . $ultimoGiorno])
            ->groupBy(DB::raw('Year(data_creazione)'), DB::raw('Month(data_creazione)'))
            ->get();


        $ckm = [];
        $ore = [];
        foreach ($powers as $power) {
            $tempOfc = $productionOfc->where('Periodo', $power->Periodo)->first();
            $tempCc = $productionCc->where('Periodo', $power->Periodo)->first();
            $ckmT = round($tempOfc->quantita + $tempCc->quantita, 0);
            $costSup = round(($power->of_manpower_cost_sup * 50) / 100, 2);

            //$placeholder['Hours'][strtotime($power->Periodo.'-01')]  = round(($power->cc_manpower_cost_op + $power->cc_manpower_cost_temp + $power->of_manpower_cost_op + $power->of_manpower_cost_temp) /  ($tempCc->quantita + $tempOfc->quantita),0);
            $placeholder['HoursCc'][strtotime($power->Periodo . '-01')] = round(($power->cc_manpower_cost_op + $power->cc_manpower_cost_temp) / ($tempCc->quantita + $tempOfc->quantita), 0);
            $placeholder['HoursOfc'][strtotime($power->Periodo . '-01')] = round(($power->of_manpower_cost_op + $power->of_manpower_cost_temp) / ($tempCc->quantita + $tempOfc->quantita), 0);
            $placeholder['OfcHours'][strtotime($power->Periodo . '-01')] = round(($power->of_manpower_cost_op + $power->of_manpower_cost_temp) / (int)$tempOfc->fkm, 2);
            $placeholder['CccHours'][strtotime($power->Periodo . '-01')] = round(($power->cc_manpower_cost_op + $power->cc_manpower_cost_temp) / (int)$tempCc->quantita, 0);

            $t_cat[strtotime($power->Periodo)] = date('y-M', strtotime($power->Periodo));


            $placeholder['Pawers'][strtotime($power->Periodo . '-01')] = round($power->of_eletric_power / $ckmT, 0);
            $placeholder['OfcPawers'][strtotime($power->Periodo . '-01')] = round((($power->of_eletric_power * 70) / 100) / (int)$tempOfc->fkm, 2);
            $placeholder['CcPawers'][strtotime($power->Periodo . '-01')] = round((($power->of_eletric_power * 30) / 100) / $tempCc->quantita, 0);
            $placeholder['Packaging'][strtotime($power->Periodo . '-01')] = ($power->packaging > 0 ? round($power->packaging / $ckmT) : 0);
            $placeholder['OfcPackaging'][strtotime($power->Periodo . '-01')] = ($power->packaging > 0 ? round((($power->packaging * 70) / 100) / (int)$tempOfc->fkm, 2) : 0);
            $placeholder['CcPackaging'][strtotime($power->Periodo . '-01')] = ($power->packaging > 0 ? round((($power->packaging * 30) / 100) / $tempCc->quantita) : 0);
            $placeholder['Shipping'][strtotime($power->Periodo . '-01')] = ($power->shipping > 0 ? round($power->shipping / $ckmT) : 0);
            $placeholder['OfcShipping'][strtotime($power->Periodo . '-01')] = ($power->shipping > 0 ? round((($power->shipping * 70) / 100) / (int)$tempOfc->fkm, 2) : 0);
            $placeholder['CcShipping'][strtotime($power->Periodo . '-01')] = ($power->shipping > 0 ? round((($power->shipping * 30) / 100) / $tempCc->quantita) : 0);

            $placeholder['Kwh'][strtotime($power->Periodo . '-01')] = $power->kwh;
            $placeholder['Smc'][strtotime($power->Periodo . '-01')] = $power->smc;
            $placeholder['KwhPrice'][strtotime($power->Periodo . '-01')] =$power->of_eletric_power;
            $placeholder['SmcPrice'][strtotime($power->Periodo . '-01')] = $power->of_utility_cost;

        }


        ksort($placeholder['HoursCc']);
        ksort($placeholder['HoursOfc']);
        ksort($placeholder['OfcHours']);
        ksort($placeholder['OfcPawers']);
        ksort($placeholder['CcPawers']);
        ksort($placeholder['OfcPackaging']);
        ksort($placeholder['CcPackaging']);
        ksort($placeholder['OfcShipping']);
        ksort($placeholder['CcShipping']);
        ksort($placeholder['Pawers']);
        ksort($placeholder['Packaging']);
        ksort($placeholder['Shipping']);
        //ksort( $placeholder['Hours']);
        ksort($placeholder['CccHours']);
        ksort($placeholder['Kwh']);
        ksort($placeholder['Smc']);
        ksort($placeholder['KwhPrice']);
        ksort($placeholder['SmcPrice']);
        ksort($t_cat);



         $return['seriesCost'] = [
             'power' => [['name' => 'Kwh', 'type' => 'column', 'data' => array_values($placeholder['Kwh'])], ['name' => 'Cost', 'type' => 'line', 'data' => array_values($placeholder['KwhPrice'])]],
             'methane' => [['name' => 'Smc', 'type' => 'column', 'data' => array_values($placeholder['Smc'])], ['name' => 'Cost', 'type' => 'line', 'data' => array_values($placeholder['SmcPrice'])]],
             'categoryes' => array_values($t_cat),
         ];

        $return['series'] = [
            ['name' => 'Manpower Cc', 'data' => array_values($placeholder['HoursCc'])],
            ['name' => 'Manpower Ofc', 'data' => array_values($placeholder['HoursOfc'])],
            ['name' => 'Power', 'data' => array_values($placeholder['Pawers'])],
            ['name' => 'Packing', 'data' => array_values($placeholder['Packaging'])],
            ['name' => 'Freght', 'data' => array_values($placeholder['Shipping'])],
            //['name' => 'MP', 'data' => array_values($placeholder['Hours'])],
            // ['name' => 'Ckm', 'data' => array_values($ckm)],
        ];
        $return['seriesOttico'] = [
            ['name' => 'Manpower', 'data' => array_values($placeholder['OfcHours'])],
            ['name' => 'Power', 'data' => array_values($placeholder['OfcPawers'])],
            ['name' => 'Packing', 'data' => array_values($placeholder['OfcPackaging'])],
            ['name' => 'Freght', 'data' => array_values($placeholder['OfcShipping'])],
        ];
        $return['seriesRame'] = [
            ['name' => 'Manpower', 'data' => array_values($placeholder['CccHours'])],
            ['name' => 'Power', 'data' => array_values($placeholder['CcPawers'])],
            ['name' => 'Packing', 'data' => array_values($placeholder['CcPackaging'])],
            ['name' => 'Freght', 'data' => array_values($placeholder['CcShipping'])],
        ];
        $return['categories'] = array_values($t_cat);

        return response()->json($return);
    }

    public function labourCost(Request $request)
    {

        $anno = (new Carbon($request->periodo))->subMonths(0)->format('Y');
        $mese = (new Carbon($request->periodo))->subMonths(0)->format('m');
        if ($mese <= 3) {
            $annoDA = $anno - 2;
            $dataBetween = [$annoDA . '-04-01', $anno . '-03-01'];
        } else {
            $annoA = $anno + 1;
            $dataBetween = [$anno . '-04-01', $annoA . '-03-01'];
        }


        $annoCorrente = DB::connection('mysql_old')->table('plant_costs')
            ->select(
                DB::raw("CONCAT( Year(data_creazione),'-',MONTH(data_creazione)) AS Periodo"),
                'of_manpower_cost_op', 'cc_manpower_cost_op', 'of_manpower_cost_sup',
                'of_manpower_cost_temp', 'cc_manpower_cost_temp', 'manpower_cost_sup_temp')
            ->whereBetween('data_creazione', $dataBetween)
            ->groupBy(DB::raw('Year(data_creazione)'), DB::raw('Month(data_creazione)'))
            ->get();

        $fatturatoAnno = DB::table('targets')
            ->select('titolo', 'valore', 'target', 'data_riferimento')
            ->where('tipo', 1)
            ->whereBetween('data_riferimento', $dataBetween)
            ->whereIn('titolo', ['value_ofc', 'value_cc'])
            ->orderBy('data_riferimento', 'asc')
            ->get();



        $result = [];

        foreach ($annoCorrente as $datiMese) {
            Log::channel('stderr')->info($datiMese->Periodo);
            $mese = (new Carbon($datiMese->Periodo))->subMonths(0)->format('m');
            $anno = (new Carbon($datiMese->Periodo))->subMonths(0)->format('Y');
            $d = (new Carbon($datiMese->Periodo))->subMonths(0)->format('Y-m');

            if ($mese >= 4 && $mese <= 6) {
                $quadrimestre = 1;
            } elseif ($mese >= 7 && $mese <= 9) {
                $quadrimestre = 2;
            } elseif ($mese >= 10 && $mese <= 12) {
                $quadrimestre = 3;
            } elseif ($mese >= 1 && $mese <= 3) {
                $quadrimestre = 4;
            } else {
                $quadrimestre = "Errore";
            }
            $fatturatoMeseCc = $fatturatoAnno->where('data_riferimento', $d . '-01')->where('titolo', 'value_cc')->first();
            $fatturatoMeseOfc = $fatturatoAnno->where('data_riferimento', $d . '-01')->where('titolo', 'value_ofc')->first();

            $anno = $anno - 1;
            $fatturatoLastMeseCc = $fatturatoAnno->where('data_riferimento', $anno . '-' . $mese . '-01')->where('titolo', 'value_cc')->first();
            $fatturatoLastMeseOfc = $fatturatoAnno->where('data_riferimento', $anno . '-' . $mese . '-01')->where('titolo', 'value_ofc')->first();
            //Log::channel('stderr')->info('Data: '.$anno.'-'.$mese.'-01 Dato: '.(!empty($fatturatoLastMeseCc->valore) ? $fatturatoLastMeseCc->valore:0));

            if (empty($result[$quadrimestre])) {
                $result[$quadrimestre]['reavenuesYearCc'] = 0.00;
                $result[$quadrimestre]['reavenuesYearOfc'] = 0.00;
                $result[$quadrimestre]['reavenuesYear'] = 0.00;
                $result[$quadrimestre]['labourYear'] = 0.00;

                $result[$quadrimestre]['reavenuesLastYearCc'] = 0.00;
                $result[$quadrimestre]['reavenuesLastYearOfc'] = 0.00;
                $result[$quadrimestre]['reavenuesLastYear'] = 0.00;
                $result[$quadrimestre]['labourLastYear'] = 0.00;
            }
            if (!empty($fatturatoMeseCc->valore))
                $result[$quadrimestre]['reavenuesYearCc'] += $fatturatoMeseCc->valore;
            if (!empty($fatturatoMeseOfc->valore))
                $result[$quadrimestre]['reavenuesYearOfc'] += $fatturatoMeseOfc->valore;
            if (!empty($fatturatoMeseOfc->valore))
                $result[$quadrimestre]['reavenuesYear'] += $fatturatoMeseOfc->valore + $fatturatoMeseCc->valore;
            if (!empty($datiMese->of_manpower_cost_op))
                $result[$quadrimestre]['labourYear'] += $datiMese->of_manpower_cost_op + $datiMese->cc_manpower_cost_op + $datiMese->of_manpower_cost_sup + $datiMese->cc_manpower_cost_temp;

            if (!empty($fatturatoLastMeseCc->valore))
                $result[$quadrimestre]['reavenuesLastYearCc'] += $fatturatoLastMeseCc->valore + $fatturatoLastMeseCc->valore;
            if (!empty($fatturatoLastMeseOfc->valore))
                $result[$quadrimestre]['reavenuesLastYearOfc'] += $fatturatoLastMeseOfc->valore + $fatturatoLastMeseOfc->valore;
            if (!empty($fatturatoLastMeseOfc->valore))
                $result[$quadrimestre]['reavenuesLastYear'] += $fatturatoLastMeseOfc->valore + $fatturatoLastMeseCc->valore;
            $result[$quadrimestre]['labourLastYear'] += 0;

        }


        $totalUno = [
            'YTF_AGP' => [
                'bCC' => $result[1]['reavenuesLastYearCc'] + $result[2]['reavenuesLastYearCc'] + $result[3]['reavenuesLastYearCc'] + $result[4]['reavenuesLastYearCc'],
                'bOFC' => $result[1]['reavenuesLastYearOfc'] + $result[2]['reavenuesLastYearOfc'] + $result[3]['reavenuesLastYearOfc'] + $result[4]['reavenuesLastYearOfc'],
                'bR' => $result[1]['reavenuesLastYear'] + $result[2]['reavenuesLastYear'] + $result[3]['reavenuesLastYear'] + $result[4]['reavenuesLastYear'],
            ],
            'YTF_ACTUAL' => [
                'bCC' => $result[1]['reavenuesYearCc'] + $result[2]['reavenuesYearCc'] + $result[3]['reavenuesYearCc'] + $result[4]['reavenuesYearCc'],
                'bOFC' => $result[1]['reavenuesYearOfc'] + $result[2]['reavenuesYearOfc'] + $result[3]['reavenuesYearOfc'] + $result[4]['reavenuesYearOfc'],
                'bR' => $result[1]['reavenuesYear'] + $result[2]['reavenuesYear'] + $result[3]['reavenuesYear'] + $result[4]['reavenuesYear'],
                'bL' => $result[1]['labourYear'] + $result[2]['labourYear'] + $result[3]['labourYear'] + $result[4]['labourYear'],
            ],
        ];

        $result[] = $totalUno;
        $series = [

        ];

        return response()->json($result);
    }

    public function machines(Request $request)
    {
        $dataBy = $request->get('periodo');
        $orderBy = $request->get('orderBy');
        $sortByName = $request->get('sortBy');

        if (empty($dataBy))
            $dataBy = date('Y-m-d');

        if (empty($sortByName)) {
            $sortByName = 'Macchina';
            $orderBy = 'asc';
        }
        $result = DB::connection('sqlsrv_root_gp')->table('Produzione as PRD')
            ->join('Risorse as R', 'R.IDRisorsa', '=', 'PRD.IDRis')
            ->leftJoin(DB::raw('(SELECT idScheda, [Dichiarazione 1] as Dichiarazione1, [Dichiarazione 2] as Dichiarazione2, [Dichiarazione 1] as Dichiarazione3, [Dichiarazione 4] as Dichiarazione4 
                               FROM (SELECT idScheda, Operatore AS A, IDDipendente FROM ElencoOperatoriSchede) src PIVOT (MAX(IDDipendente) FOR A IN ([Dichiarazione 1], [Dichiarazione 2], [Dichiarazione 3], [Dichiarazione 4])) pvt) AS PVT'),
                function ($join) {
                    $join->on('PRD.IDPRODUZIONE', '=', 'PVT.idScheda');
                })
            ->leftJoin('Dipendenti As Dip1', 'PVT.Dichiarazione1', 'Dip1.IDImpiegato')
            ->leftJoin('Dipendenti As Dip2', 'PVT.Dichiarazione2', 'Dip2.IDImpiegato')
            ->leftJoin('Dipendenti As Dip3', 'PVT.Dichiarazione3', 'Dip3.IDImpiegato')
            ->leftJoin('Dipendenti As Dip4', 'PVT.Dichiarazione4', 'Dip4.IDImpiegato')
            ->leftJoin('AGGDB_Produzione_Schede_Ricalcolate As SR', 'PRD.IDProduzione', 'SR.idscheda')
            ->leftJoin(DB::raw('(SELECT O.idScheda, SUM(O.DurataCalcolata) * 3600 AS DurataCalcolataOperatoriSec, COUNT(DISTINCT O.IdDipendente) AS TotaleOperatori FROM Operatori_Prod_Tbl O WHERE ISNULL(O.Annulla, 0) = 0 AND O.IdDipendente != 0 GROUP BY O.idScheda) AS D'),
                function ($join) {
                    $join->on(DB::raw('CASE WHEN ISNULL(SR.dacarica, 0) = 0 THEN PRD.IDProduzione ELSE PRD.IDSchedaPrdOrdineAcc END'), '=', 'D.idScheda');
                })
            ->leftJoin(DB::raw('(SELECT F.IDProduzione, SUM(CASE WHEN F.IdCausaleFermo = 10 THEN DATEDIFF(ss, F.DOInizio, F.DOFine) ELSE 0 END) as F1,
SUM(CASE WHEN F.IdCausaleFermo = 14 THEN DATEDIFF(ss, F.DOInizio, F.DOFine) ELSE 0 END) as F5
                               FROM Fermi F INNER JOIN Produzione P ON F.IdProduzione = P.IDProduzione INNER JOIN
                               Causali_Fermo CSLF ON F.IdCausaleFermo = CSLF.IDCausaleFermo 
                               WHERE ISNULL(CSLF.EscStt, 0) = 0 AND F.IdCausaleFermo IN (10,14) AND ISNULL(F.isAnnullato, 0) = 0
							   group by F.IDProduzione) AS FMac'),
                function ($join) {
                    $join->on('PRD.IDProduzione', '=', 'FMac.IDProduzione');
                })
            ->leftJoin(DB::raw('(SELECT F.IDProduzione, SUM(DATEDIFF(ss, F.DOInizio, F.DOFine)) as TotalFermi
                               FROM Fermi F INNER JOIN Produzione P ON F.IdProduzione = P.IDProduzione INNER JOIN
                               Causali_Fermo CSLF ON F.IdCausaleFermo = CSLF.IDCausaleFermo 
                               WHERE ISNULL(CSLF.EscStt, 0) = 0 AND F.IdCausaleFermo NOT IN (11,12) AND ISNULL(F.isAnnullato, 0) = 0
							   group by F.IDProduzione) AS FTMac'),
                function ($join) {
                    $join->on('PRD.IDProduzione', '=', 'FTMac.IDProduzione');
                })
            ->leftJoin(DB::raw('(SELECT F.IDProduzione, SUM(DATEDIFF(ss, F.DOInizio, F.DOFine)) as TotalFermi
                               FROM Fermi F INNER JOIN Produzione P ON F.IdProduzione = P.IDProduzione INNER JOIN
                               Causali_Fermo CSLF ON F.IdCausaleFermo = CSLF.IDCausaleFermo 
                               WHERE ISNULL(CSLF.EscStt, 0) = 0 AND F.IdCausaleFermo NOT IN (11,12) AND ISNULL(F.isAnnullato, 0) = 0
							   group by F.IDProduzione) AS FTMac'),
                function ($join) {
                    $join->on('PRD.IDProduzione', '=', 'FTMac.IDProduzione');
                })
            ->where('PRD.Confermato', 1)
            ->where('PRD.Significativo', 1)
            ->where('PRD.IdSchedaPrdOrdineAcc', 0)
            //->where('PRD.#Cicli', '>', 0)
            ->Where(function ($query) use ($dataBy) {
                $dataBy = explode(' to ', $dataBy);
                if (count($dataBy) == 2) {
                    $dataBy[0] = $dataBy[0] . ' 00:00:00.000';
                    $dataBy[1] = $dataBy[1] . ' 23:59:59.999';
                    $query->whereBetween('PRD.DataOraInizio', $dataBy);
                } else {
                    $query->whereDate('PRD.DataOraInizio', $dataBy[0]);
                }
            })
            //->whereBetween('PRD.DataOraInizio', ['2025-03-01 00:00:00:000', '2025-03-1 23:59:59:990'])
            ->select(
            //DB::raw("CONCAT( Year(DataOraInizio),'-',MONTH(DataOraInizio)) AS Periodo"),
                'R.Modello AS Macchina',
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ISNULL(FMac.F5, 0), 0)) / 3600, 2)) As F5'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ISNULL(FMac.F1, 0), 0)) / 3600, 2)) As F1'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ISNULL(FTMac.TotalFermi, 0), 0)) / 3600, 2)) As FermiTotal'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ISNULL(D.DurataCalcolataOperatoriSec, 0), 0)) / 3600, 2)) As ManodoperaH'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(DATEDIFF(ss, PRD.DataOraInizio, PRD.DataOraFine) * TotaleOperatori, 0)) / 3600, 2)) As ManodoperaCalcolataH'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(DATEDIFF(ss, PRD.DataOraInizio, PRD.DataOraFine), 0)) / 3600, 2)) As SchedaH'),
            )
            ->groupBy('R.Modello')
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($result);
    }

    public function machinesExport(Request $request)
    {
        $name_file = $request->periodo . '.xlsx';

        $export = new OraMacchinaExport($request->periodo);
        return Excel::download($export, $name_file);

    }


    private function calccalcolo_percentuale($valore, $target)
    {

        //$tmp = round(((($valore - $target) / $target) + 1) * 100, 4);
        $tmp = round(((($valore - $target) / $target) + 1) * 100, 2);

        return $tmp;
    }
}
