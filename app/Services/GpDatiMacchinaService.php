<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Replica di dbo.STL_DatiMacchina_V (connessione sqlsrv_root_gp).
 *
 * La vista originale fa CONVERT(INT, TVV.noteTstDttVDtt) nel JOIN con la
 * subquery PR: i valori nvarchar oltre il limite int (es. '2614401518')
 * provocano SQLSTATE 22003 su QUALSIASI query della vista, perche' il CASE
 * viene valutato prima dei filtri esterni.
 * Qui la conversione e' digit-only + BIGINT: non puo' andare in overflow
 * (TRY_CONVERT non e' disponibile su questo SQL Server). Righe con note non
 * convertibili in int non matchano comunque alcuno SchedaId, come prima.
 */
class GpDatiMacchinaService
{
    private const BASE_SQL = <<<'SQL'
SELECT LTRIM(RTRIM(ISNULL(D.Nome, ''))) + ' ' + LTRIM(RTRIM(D.Cognome)) AS OperatoreScheda,
       TVV.dataTstV AS DataRegistrazione,
       R.Modello AS Macchina,
       R.IDRisorsa,
       CV.nomeCaratV AS Caratteristica,
       TVV.valMisAssV AS ValoreRegistrato,
       CONVERT(BIT, TVV.PosControllataV) AS MacchinaAttiva,
       PR.IDSchedaACD AS SchedaId
FROM dbo.LAB_ArchivioProve_tbl AS M
INNER JOIN dbo.LAB_Test_tbl AS T ON M.idprova = T.idprova
INNER JOIN dbo.LAB_ArchProveDettVar_Tbl AS MV ON M.idprova = MV.idprova
INNER JOIN dbo.LAB_TestDettVar_Tbl AS TV ON T.idtest = TV.idtest AND MV.idproveDettV = TV.idprvDettV
INNER JOIN dbo.LAB_CaratteristicheVar_Tbl AS CV ON MV.idcaratV = CV.idcaratV
INNER JOIN dbo.LAB_TestDettVar_Dtt_Tbl AS TVV ON TV.idtestDettV = TVV.idTstDttV
LEFT OUTER JOIN (
    SELECT A.AP_FK_IDOperatore AS idOperatore, A.AP_FK_IDRisorsa AS IDRisorsa, A.AP_ID AS IDSchedaACD
    FROM dbo.Acd_Produzione_T AS A INNER JOIN dbo.Risorse AS R ON A.AP_FK_IDRisorsa = R.IDRisorsa
    WHERE (A.AP_IsImportata = 0) AND (A.AP_IsAnnullato = 0) AND (R.FKProtocol = 2)
    UNION ALL
    SELECT P.IDOperatore, P.IDRis AS IDRisorsa, P.FK_ADProduzioneNet AS IDSchedaACD
    FROM dbo.Produzione AS P INNER JOIN dbo.Risorse AS R ON P.IDRis = R.IDRisorsa
    WHERE (P.Confermato = 1) AND (P.Significativo = 1) AND (R.FKProtocol = 2)
) AS PR ON PR.IDSchedaACD = CASE
    WHEN LEN(LTRIM(RTRIM(TVV.noteTstDttVDtt))) BETWEEN 1 AND 18
     AND LTRIM(RTRIM(TVV.noteTstDttVDtt)) NOT LIKE '%[^0-9]%'
    THEN CONVERT(BIGINT, LTRIM(RTRIM(TVV.noteTstDttVDtt)))
    ELSE NULL END
INNER JOIN dbo.Risorse AS R ON PR.IDRisorsa = R.IDRisorsa
LEFT OUTER JOIN dbo.Dipendenti AS D ON PR.idOperatore = D.IDImpiegato
WHERE (TVV.annullatoV = 0) AND (T.annullato = 0)
  AND (M.idprova IN (237221, 237222, 237804, 237805, 237806, 237807, 238732, 238733, 238734, 241306, 241307))
  AND (YEAR(TVV.dataTstV) >= 2024)
SQL;

    /**
     * Builder sulla telemetria macchine 4.0: espone le stesse colonne di
     * STL_DatiMacchina_V (IDRisorsa, DataRegistrazione, Caratteristica,
     * ValoreRegistrato, OperatoreScheda, Macchina, MacchinaAttiva, SchedaId).
     */
    public static function query(): Builder
    {
        return DB::connection('sqlsrv_root_gp')
            ->query()
            ->fromSub(self::BASE_SQL, 'SDM');
    }
}
