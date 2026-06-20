<?php

namespace App\Exports;

use App\Models\QtCheckerReport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OraMacchinaExport implements FromCollection, WithHeadings
{
    private $periodo = null;

    public function __construct($periodo)
    {

        $this->periodo = $periodo;

    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $dataBy = $this->periodo;

        $data = explode(' to ', $dataBy);
        if (count($data) == 2){
            $data[0] = $data[0].' 00:00:00.000';
            $data[1] = $data[1].' 23:59:59.999';
        }
        else{
            $data[0] = $dataBy.' 00:00:00.000';
            $data[1] = $dataBy.' 23:59:59.999';
        }

        $result = DB::connection('sqlsrv_root_gp')->table('200134_MB.dbo.Produzione as PRD')
            ->join('Risorse as R','R.IDRisorsa','=','PRD.IDRis')
            ->leftJoin('AGGDB_Produzione_Schede_Ricalcolate as SR','PRD.IDProduzione','SR.idscheda')
            ->leftJoin(DB::raw('(SELECT O.idScheda, SUM(O.DurataCalcolata) * 3600 AS DurataCalcolataOperatoriSec, COUNT(DISTINCT O.IdDipendente) AS TotaleOperatori FROM Operatori_Prod_Tbl O WHERE ISNULL(O.Annulla, 0) = 0 AND O.IdDipendente != 0 GROUP BY O.idScheda) AS D'),
                function($join)
                {
                    $join->on(DB::raw('CASE WHEN ISNULL(SR.dacarica, 0) = 0 THEN PRD.IDProduzione ELSE PRD.IDSchedaPrdOrdineAcc END'), '=', 'D.idScheda');
                })
            ->leftJoin(DB::raw('(SELECT F.IDProduzione, SUM(CASE WHEN F.IdCausaleFermo = 10 THEN DATEDIFF(ss, F.DOInizio, F.DOFine) ELSE 0 END) as F1,
SUM(CASE WHEN F.IdCausaleFermo = 14 THEN DATEDIFF(ss, F.DOInizio, F.DOFine) ELSE 0 END) as F5
                               FROM Fermi F INNER JOIN Produzione P ON F.IdProduzione = P.IDProduzione INNER JOIN
                               Causali_Fermo CSLF ON F.IdCausaleFermo = CSLF.IDCausaleFermo
                               WHERE ISNULL(CSLF.EscStt, 0) = 0 AND F.IdCausaleFermo IN (10,14) AND ISNULL(F.isAnnullato, 0) = 0 AND P.Confermato = 1 AND P.Significativo = 1 AND P.IdSchedaPrdOrdineAcc = 0
                               group by F.IDProduzione) AS FMac'),
                function($join)
                {
                    $join->on('PRD.IDProduzione', '=', 'FMac.IDProduzione');
                })
            ->leftJoin(DB::raw('(SELECT F.IDProduzione, SUM(DATEDIFF(ss, F.DOInizio, F.DOFine)) as TotalFermi
                               FROM Fermi F INNER JOIN Produzione P ON F.IdProduzione = P.IDProduzione INNER JOIN
                               Causali_Fermo CSLF ON F.IdCausaleFermo = CSLF.IDCausaleFermo
                               WHERE ISNULL(CSLF.EscStt, 0) = 0 AND F.IdCausaleFermo NOT IN (11, 12) AND ISNULL(F.isAnnullato, 0) = 0 AND P.Confermato = 1 AND P.Significativo = 1 AND P.IdSchedaPrdOrdineAcc = 0
                               group by F.IDProduzione) AS FTMac'),
                function($join)
                {
                    $join->on('PRD.IDProduzione', '=', 'FTMac.IDProduzione');
                })
            ->select(
                'R.Modello AS Macchina',
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(DATEDIFF(ss, PRD.DataOraInizio, PRD.DataOraFine), 0)) / 3600, 2)) As SchedaH'),
                DB::raw('SUM(ROUND(ISNULL(FTMac.TotalFermi, 0) / 3600, 2)) As FermiTotal'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ISNULL(FMac.F1, 0), 0)) / 3600, 2)) As F1'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ISNULL(FMac.F5, 0), 0)) / 3600, 2)) As F5'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ISNULL(D.DurataCalcolataOperatoriSec, 0), 0)) / 3600, 2)) As ManodoperaH'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(DATEDIFF(ss, PRD.DataOraInizio, PRD.DataOraFine) * TotaleOperatori, 0)) / 3600, 2)) As ManodoperaCalcolataH')
            )
            ->where('PRD.Confermato', 1)
            ->where('PRD.Significativo', 1)
            ->where('PRD.IdSchedaPrdOrdineAcc', 0)
            ->whereBetween('PRD.DataOraInizio', [$data[0], $data[1]])
            ->groupBy('R.Modello')
            ->get();

        $items = [];
        foreach ($result as $r) {
            $schedaH = round($r->SchedaH, 2);
            $fermiTotal = round($r->FermiTotal, 2);
            $manodoperaH = round($r->ManodoperaH, 2);
            $totaleOre = round($schedaH - $fermiTotal, 2);
            $rapporto = ($totaleOre > 0) ? round($manodoperaH / $totaleOre, 2) : 0;
            $efficenza = ($schedaH > 0) ? round(($totaleOre / $schedaH) * 100, 2) : 0;

            $items[] = [
                $r->Macchina,
                $schedaH,
                $fermiTotal,
                round($r->F1, 2),
                round($r->F5, 2),
                $totaleOre,
                $manodoperaH,
                round($r->ManodoperaCalcolataH, 2),
                $rapporto,
                $efficenza
            ];
        }

        return collect($items);
    }

    public function headings(): array
    {
        return [
            'Macchina','Ore Macchina', 'Fermi Macchina', 'F1', 'F5','Totale Ore Macchina','Ore Manodopera',
             'Ore Manodopera Calcolata','Rapporto mac/man','Efficenza'
        ];
    }
}
