<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Gp extends Model
{
    use HasFactory, HasUuids;

    static function totaleDatiProduzione($lavorazione, $data)
    {
        $result = DB::connection('sqlsrv_root_gp')
            ->table('Produzione as PRD')
            ->select(DB::raw("CONCAT( Year(PRD.DataOraInizio),'-',MONTH(PRD.DataOraInizio)) AS Periodo"), DB::raw("SUM(CASE WHEN UM.UM = 'km' THEN PRD.#Cicli ELSE PRD.#Cicli/1000 END) as quantita"), DB::raw("SUM(PRD.#Cicli * P.Conversione12) as fkm"), DB::raw("SUM(PRD.#Cicli * ProdNuovi.Valore) as Valore"))
            ->join('Dettagli_sugli_ordini as DSO', 'DSO.IDDettagliOrdini', '=', 'PRD.IDDettOrd')
            ->join('Dettagli_Master as DM','DM.idMaster','=','DSO.IDMaster')
            ->join('Prodotti as P','P.IDProdotto','=','PRD.IDArticolo')
            ->join('UM','UM.IDUM','=','DSO.IDUM')
            ->join('Risorse as R','R.IDRisorsa','=','PRD.IDRis')
            ->join('GP_NX_AGG.dbo.AGG_PRODOTTI_TMP AS ProdNuovi','ProdNuovi.cdProdotto','P.NomeProdotto')
            ->join("GP_NX_AGG.dbo.AGG_DETTAGLI_TMP as DT1",function($join){
                $join->on("DT1.cdOrdine","=",DB::raw("replace(DM.NRigaOrd, '00009', '9')"))
                    ->on("DSO.CodPrel","=","DT1.numFase");
            })
            ->Where(function ($query) use ($data) {
                if ($data){
                    if(count($data) == 2)
                        $query->whereBetween('PRD.DataOraInizio', [$data[0].' 00:00:00:000',$data[1].' 23:59:59:990']);
                    else
                        $query->whereDate('PRD.DataOraInizio', $data);
                }
            })
            ->whereIn('DT1.ControlKey',['PP03','ZP03'])
            ->where('PRD.Confermato',1)
            ->where('PRD.Significativo',1)
            ->where('PRD.IdSchedaPrdOrdineAcc',0)
            ->where('PRD.#Cicli','>',0)
            ->Where(function ($query) use ($lavorazione) {
                if ($lavorazione){
                    switch ($lavorazione) {
                        case 'bu':
                            $query->where('P.NomeProdotto','LIKE','BUF%');
                            break;
                        case 'sz':
                            $query->where('P.NomeProdotto','LIKE','SZ%');
                            break;
                        case 'sf':
                            $query->where('P.NomeProdotto', 'NOT LIKE', 'SFSPB1C0001%');
                            $query->Where('P.NomeProdotto', 'LIKE', 'SF%')->orWhere('P.NomeProdotto', 'LIKE', 'FC%');
                            $query->where('DM.NRigaOrd', 'NOT LIKE', '94%');
                            $query->where('R.Modello','NOT LIKE', 'B%');
                            break;
                        case 'mk':
                            $query->Where('P.NomeProdotto','LIKE','FC%');
                            $query->where('DM.NRigaOrd','LIKE','94%');
                            break;
                        case 'f':
                            $query->Where('P.NomeProdotto','LIKE','F1%')
                                ->orWhere('P.NomeProdotto','LIKE','F2%')
                                ->orWhere('P.NomeProdotto','LIKE','F3%')
                                ->orWhere('P.NomeProdotto','LIKE','F4%')
                                ->orWhere('P.NomeProdotto','LIKE','F5%')
                                ->orWhere('P.NomeProdotto','LIKE','F6%')
                                ->orWhere('P.NomeProdotto','LIKE','F7%')
                                ->orWhere('P.NomeProdotto','LIKE','F9%');
                            break;
                        case 's':
                            $query->Where('P.NomeProdotto','LIKE','S1%')
                                ->orWhere('P.NomeProdotto','LIKE','S2%')
                                ->orWhere('P.NomeProdotto','LIKE','S3%')
                                ->orWhere('P.NomeProdotto','LIKE','S4%')
                                ->orWhere('P.NomeProdotto','LIKE','S5%')
                                ->orWhere('P.NomeProdotto','LIKE','S6%')
                                ->orWhere('P.NomeProdotto','LIKE','S7%')
                                ->orWhere('P.NomeProdotto','LIKE','S8%')
                                ->orWhere('P.NomeProdotto','LIKE','S9%');
                            break;
                    }
                }
            })
            ->groupBy( DB::raw('Year(DataOraInizio)'), DB::raw('Month(DataOraInizio)'))
            ->get();

        if($result->count() == 1)
            return $result->first();

        return $result;
    }

    static function numeroFibre($matariale)
    {
        $obj = DB::connection('sqlsrv_gp')->table('AGG_PRODOTTI_TMP')
            ->select('Conversione')
            ->where('cdProdotto',$matariale)
            ->first();

        return $obj;
    }

    static function oreMacchina($data)
    {
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
                               WHERE ISNULL(CSLF.EscStt, 0) = 0 AND F.IdCausaleFermo <> 11 AND ISNULL(F.isAnnullato, 0) = 0
							   group by F.IDProduzione) AS FTMac'),
                function($join)
                {
                    $join->on('PRD.IDProduzione', '=', 'FTMac.IDProduzione');
                })
            ->where('PRD.Confermato', 1)
            ->where('PRD.Significativo', 1)
            ->where('PRD.IdSchedaPrdOrdineAcc', 0)
            ->where('PRD.#Cicli', '>', 0)
            ->Where(function ($query) use ($data) {
                if ($data){
                    if(count($data) == 2)
                        $query->whereBetween('PRD.DataOraInizio', [$data[0].' 00:00:00:000',$data[1].' 23:59:59:990']);
                    else
                        $query->whereDate('PRD.DataOraInizio', $data);
                }
            })
            //->whereBetween('PRD.DataOraInizio', ['2025-03-01 00:00:00:000', '2025-03-1 23:59:59:990'])
            ->select(
                DB::raw("CONCAT( Year(DataOraInizio),'-',MONTH(DataOraInizio)) AS Periodo"),
                'R.Modello AS Macchina',
                DB::raw('SUM(ROUND(ISNULL(FMac.F5, 0), 0)) As F5'),
                DB::raw('SUM(ROUND(ISNULL(FMac.F1, 0), 0)) As F1'),
                DB::raw('SUM(ROUND(ISNULL(FTMac.TotalFermi, 0), 0)) As FermiTotal'),
                DB::raw('SUM(ROUND(ISNULL(D.DurataCalcolataOperatoriSec, 0), 0)) As ManodoperaSec'),
                DB::raw('SUM(ROUND(DATEDIFF(ss, PRD.DataOraInizio, PRD.DataOraFine), 0)) As SchedaSec'),
            )
            ->groupBy( DB::raw('Year(PRD.DataOraInizio)'), DB::raw('Month(PRD.DataOraInizio)'), 'R.Modello')
            ->get();

        return $result;
    }

    static public function reportCodMerceologico($codice,$anno, $mese)
    {
        $result = DB::connection('sqlsrv_root_gp')
            ->table('Produzione as PRD')
            ->select('PRD.#Cicli as quantita','ProdNuovi.cdProdotto','ProdNuovi.cdUM','ProdNuovi.cdUM','ProdNuovi.dcRaggruppamentoPF')
            ->join('Prodotti as P','P.IDProdotto','=','PRD.IDArticolo')
            ->join('GP_NX_AGG.dbo.AGG_PRODOTTI_TMP AS ProdNuovi','ProdNuovi.cdProdotto','P.NomeProdotto')
            ->whereYear('DataOraFine',$anno)
            ->whereMonth('DataOraFine',$mese)
            ->where('ProdNuovi.cdProdotto','LIKE','FC%')
            ->Where(function ($query) use ($codice) {
                if (!empty($codice))
                    $query->where('ProdNuovi.dcRaggruppamentoPF', $codice);
                else
                    $query->whereNull('ProdNuovi.dcRaggruppamentoPF');

            });


        return $result;
    }



}
