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

        $periodo = $this->periodo;

        $result = DB::connection('sqlsrv_root_gp')->table('Produzione as PRD')
            ->join('Risorse as R','R.IDRisorsa','=','PRD.IDRis')
            ->leftJoin(DB::raw('(SELECT idScheda, [Dichiarazione 1] as Dichiarazione1, [Dichiarazione 2] as Dichiarazione2, [Dichiarazione 1] as Dichiarazione3, [Dichiarazione 4] as Dichiarazione4 
                               FROM (SELECT idScheda, Operatore AS A, IDDipendente FROM ElencoOperatoriSchede) src PIVOT (MAX(IDDipendente) FOR A IN ([Dichiarazione 1], [Dichiarazione 2], [Dichiarazione 3], [Dichiarazione 4])) pvt) AS PVT'),
                function($join)
                {
                    $join->on('PRD.IDPRODUZIONE', '=', 'PVT.idScheda');
                })
            ->leftJoin('Dipendenti As Dip1','PVT.Dichiarazione1','Dip1.IDImpiegato')
            ->leftJoin('Dipendenti As Dip2','PVT.Dichiarazione2','Dip2.IDImpiegato')
            ->leftJoin('Dipendenti As Dip3','PVT.Dichiarazione3','Dip3.IDImpiegato')
            ->leftJoin('Dipendenti As Dip4','PVT.Dichiarazione4','Dip4.IDImpiegato')
            ->leftJoin('AGGDB_Produzione_Schede_Ricalcolate As SR','PRD.IDProduzione','SR.idscheda')
            ->leftJoin(DB::raw('(SELECT O.idScheda, SUM(O.DurataCalcolata) * 3600 AS DurataCalcolataOperatoriSec, COUNT(DISTINCT O.IdDipendente) AS TotaleOperatori FROM Operatori_Prod_Tbl O WHERE ISNULL(O.Annulla, 0) = 0 AND O.IdDipendente != 0 GROUP BY O.idScheda) AS D'),
                function($join)
                {
                    $join->on(DB::raw('CASE WHEN ISNULL(SR.dacarica, 0) = 0 THEN PRD.IDProduzione ELSE PRD.IDSchedaPrdOrdineAcc END'), '=', 'D.idScheda');
                })
            ->leftJoin(DB::raw('(SELECT F.IDProduzione, SUM(CASE WHEN F.IdCausaleFermo = 10 THEN DATEDIFF(ss, F.DOInizio, F.DOFine) ELSE 0 END) as F1,
SUM(CASE WHEN F.IdCausaleFermo = 14 THEN DATEDIFF(ss, F.DOInizio, F.DOFine) ELSE 0 END) as F5
                               FROM Fermi F INNER JOIN Produzione P ON F.IdProduzione = P.IDProduzione INNER JOIN
                               Causali_Fermo CSLF ON F.IdCausaleFermo = CSLF.IDCausaleFermo 
                               WHERE ISNULL(CSLF.EscStt, 0) = 0 AND F.IdCausaleFermo IN (10,14) AND ISNULL(F.isAnnullato, 0) = 0
							   group by F.IDProduzione) AS FMac'),
                function($join)
                {
                    $join->on('PRD.IDProduzione', '=', 'FMac.IDProduzione');
                })
            ->leftJoin(DB::raw('(SELECT F.IDProduzione, SUM(DATEDIFF(ss, F.DOInizio, F.DOFine)) as TotalFermi
                               FROM Fermi F INNER JOIN Produzione P ON F.IdProduzione = P.IDProduzione INNER JOIN
                               Causali_Fermo CSLF ON F.IdCausaleFermo = CSLF.IDCausaleFermo 
                               WHERE ISNULL(CSLF.EscStt, 0) = 0 AND F.IdCausaleFermo NOT IN (11, 12) AND ISNULL(F.isAnnullato, 0) = 0
							   group by F.IDProduzione) AS FTMac'),
                function($join)
                {
                    $join->on('PRD.IDProduzione', '=', 'FTMac.IDProduzione');
                })
            ->where('PRD.Confermato', 1)
            ->where('PRD.Significativo', 1)
            ->where('PRD.IdSchedaPrdOrdineAcc', 0)
            //->where('PRD.#Cicli', '>', 0)
            ->Where(function ($query) use ($periodo) {
                $dataBy = explode(' to ', $periodo);
                if (count($dataBy) == 2){
                    $dataBy[0] = $dataBy[0].' 00:00:00.000';
                    $dataBy[1] = $dataBy[1].' 23:59:59.999';
                    $query->whereBetween('PRD.DataOraInizio', $dataBy);
                }
                else{
                    $query->whereDate('PRD.DataOraInizio', $dataBy[0]);
                }
            })
            ->select(
                'R.Modello AS Macchina',
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(DATEDIFF(ss, PRD.DataOraInizio, PRD.DataOraFine), 0)) / 3600, 2)) As SchedaH'),
                DB::raw('SUM(ROUND(ISNULL(FTMac.TotalFermi, 0) / 3600, 2)  ) As FermiTotal'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ISNULL(FMac.F1, 0), 0)) / 3600, 2)) As F1'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ISNULL(FMac.F5, 0), 0)) / 3600, 2)) As F5'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ROUND(CONVERT(FLOAT, ROUND(DATEDIFF(ss, PRD.DataOraInizio, PRD.DataOraFine), 0)) / 3600, 2) - ROUND(ISNULL(FTMac.TotalFermi, 0) / 3600, 2), 0)), 2)) As Totale_Ore'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ISNULL(D.DurataCalcolataOperatoriSec, 0), 0)) / 3600, 2)) As ManodoperaH'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(DATEDIFF(ss, PRD.DataOraInizio, PRD.DataOraFine) * TotaleOperatori, 0)) / 3600, 2)) As ManodoperaCalcolataH'),
                //DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ROUND(CONVERT(FLOAT, ROUND(DATEDIFF(ss, PRD.DataOraInizio, PRD.DataOraFine), 0)) / 3600, 2) / ROUND(CONVERT(FLOAT, ROUND(ISNULL(D.DurataCalcolataOperatoriSec, 0), 0)) / 3600, 2), 0)), 2)) As Rapporto'),

            )
            ->groupBy('R.Modello')
            ->get();

        return $result;
    }

    public function headings(): array
    {
        return [
            'Macchina','Ore Macchina', 'Fermi Macchina', 'F1', 'F5','Totale Ore Macchina','Ore Manodopera',
             'Ore Manodopera Calcolata','Rapporto mac/man'
        ];
    }
}
