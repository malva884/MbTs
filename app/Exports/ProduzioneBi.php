<?php

namespace App\Exports;

use App\Models\QtConformita;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProduzioneBi implements FromCollection, WithHeadings
{
    private $tipologia = null;
    private $gruppo = null;
    private $data = null;
    private $macchina= null;

    private $materiale = null;
    public function __construct($tipologia,$gruppo,$data,$macchina,$materiale)
    {
        if($tipologia != 'undefined')
            $this->tipologia = $tipologia;
        if($gruppo != 'undefined')
            $this->gruppo = $gruppo;
        if($data != 'undefined')
            $this->data = $data;
        if($macchina != 'undefined')
            $this->macchina = $macchina;
        if($materiale != 'undefined')
            $this->materiale = $materiale;

    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {

        $dataBy = $this->data;
        $lavorazioneBy = $this->tipologia;
        $materialeBy = $this->materiale;
        $gruppoBy = $this->gruppo;
        $macchina = $this->macchina;

        $objs = DB::connection('sqlsrv_root_gp')
            ->table('Produzione as PRD')
            //->select(DB::raw('SUM(CASE WHEN UM.UM = "km" THEN PRD.#Cicli ELSE PRD.#Cicli/1000 END) as quantita'))
            //->select('P.NomeProdotto AS Prodotto', 'DM.NRigaOrd AS Ordine', 'P.Conversione12 As NumeroFibre','UMP.UM AS UM','PRD.#Cicli as Quantita')
            ->join('Dettagli_sugli_ordini as DSO', 'DSO.IDDettagliOrdini', '=', 'PRD.IDDettOrd')
            ->join('Dettagli_Master as DM', 'DM.idMaster', '=', 'DSO.IDMaster')
            ->join('Prodotti as P', 'P.IDProdotto', '=', 'PRD.IDArticolo')
            ->join('UM', 'UM.IDUM', '=', 'DSO.IDUM')
            ->join('Risorse as R', 'R.IDRisorsa', '=', 'PRD.IDRis')
            ->join('GP_NX_AGG.dbo.AGG_PRODOTTI_TMP AS ProdNuovi', 'ProdNuovi.cdProdotto', 'P.NomeProdotto')
            ->join("GP_NX_AGG.dbo.AGG_DETTAGLI_TMP as DT1", function ($join) {
                $join->on("DT1.cdOrdine", "=", DB::raw("replace(DM.NRigaOrd, '00009', '9')"))
                    ->on("DSO.CodPrel", "=", "DT1.numFase");
            })
            ->Where(function ($query) use ($dataBy) {
                if ($dataBy) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2)
                        $query->whereBetween('PRD.DataOraInizio', [$dataBy[0] . ' 00:00:00:000', $dataBy[1] . ' 23:59:59:990']);
                    else
                        $query->whereDate('PRD.DataOraInizio', $dataBy);
                }
            })
            ->whereIn('DT1.ControlKey', ['PP03', 'ZP03'])
            ->where('PRD.Confermato', 1)
            ->where('PRD.Significativo', 1)
            ->where('PRD.IdSchedaPrdOrdineAcc', 0)
            ->where('PRD.#Cicli', '>', 0)

            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->where('P.NomeProdotto', 'LIKE' ,'%'.$materialeBy.'%');
            })
            ->Where(function ($query) use ($lavorazioneBy) {
                if ($lavorazioneBy) {
                    switch ($lavorazioneBy) {
                        case 'bu':
                            $query->where('P.NomeProdotto', 'LIKE', 'BUF%');
                            break;
                        case 'sz':
                            $query->where('P.NomeProdotto', 'LIKE', 'SZ%');
                            break;
                        case 'sf':
                            $query->where('P.NomeProdotto', 'NOT LIKE', 'SFSPB1C0001%');
                            $query->Where('P.NomeProdotto', 'LIKE', 'SF%')->orWhere('P.NomeProdotto', 'LIKE', 'FC%');
                            $query->where('DM.NRigaOrd', 'NOT LIKE', '94%');
                            $query->where('R.Modello','NOT LIKE', 'B%');
                            break;
                        case 'mk':
                            $query->Where('P.NomeProdotto', 'LIKE', 'FC%');
                            $query->where('DM.NRigaOrd', 'LIKE', '94%');
                            break;
                        case 'f':
                            $query->Where('P.NomeProdotto', 'LIKE', 'F1%')
                                ->orWhere('P.NomeProdotto', 'LIKE', 'F2%')
                                ->orWhere('P.NomeProdotto', 'LIKE', 'F3%')
                                ->orWhere('P.NomeProdotto', 'LIKE', 'F4%')
                                ->orWhere('P.NomeProdotto', 'LIKE', 'F5%')
                                ->orWhere('P.NomeProdotto', 'LIKE', 'F6%')
                                ->orWhere('P.NomeProdotto', 'LIKE', 'F7%')
                                ->orWhere('P.NomeProdotto', 'LIKE', 'F9%');
                            break;
                        case 's':
                            $query->Where('P.NomeProdotto', 'LIKE', 'S1%')
                                ->orWhere('P.NomeProdotto', 'LIKE', 'S2%')
                                ->orWhere('P.NomeProdotto', 'LIKE', 'S3%')
                                ->orWhere('P.NomeProdotto', 'LIKE', 'S4%')
                                ->orWhere('P.NomeProdotto', 'LIKE', 'S5%')
                                ->orWhere('P.NomeProdotto', 'LIKE', 'S6%')
                                ->orWhere('P.NomeProdotto', 'LIKE', 'S7%')
                                ->orWhere('P.NomeProdotto', 'LIKE', 'S8%')
                                ->orWhere('P.NomeProdotto', 'LIKE', 'S9%');
                            break;
                    }
                }
            });


        if($this->gruppo == 'NumeroFibre')
            $result = $objs->select(DB::raw("CONCAT( Year(PRD.DataOraInizio),'-',MONTH(PRD.DataOraInizio)) AS Periodo"), 'ProdNuovi.cdMateriale AS Materiale', 'R.Modello AS Macchina', 'P.DescrizioneProdotto','ProdNuovi.dcRaggruppamentoPF as Classe', DB::raw('CAST(ProdNuovi.Conversione as INTEGER) As NumeroFibre') , 'UM.UM AS UM', DB::raw("SUM(CASE WHEN UM.UM = 'km' THEN PRD.#Cicli ELSE PRD.#Cicli/1000 END) as quantita"))
                ->groupBy( 'ProdNuovi.Conversione', 'UM.UM', 'R.Modello', 'ProdNuovi.cdMateriale', 'P.DescrizioneProdotto', 'ProdNuovi.dcRaggruppamentoPF', DB::raw('Year(DataOraInizio)'), DB::raw('Month(DataOraInizio)'))->get();
        else
            $result = $objs->select(DB::raw("CONCAT( Year(PRD.DataOraInizio),'-',MONTH(PRD.DataOraInizio)) AS Periodo"), 'ProdNuovi.cdMateriale AS Materiale', 'R.Modello AS Macchina', 'P.NomeProdotto AS Prodotto', 'P.DescrizioneProdotto','ProdNuovi.dcRaggruppamentoPF as Classe', DB::raw('CAST(ProdNuovi.Conversione as INTEGER) As NumeroFibre') , 'UM.UM AS UM', DB::raw("SUM(CASE WHEN UM.UM = 'km' THEN PRD.#Cicli ELSE PRD.#Cicli/1000 END) as quantita"))
                ->groupBy('P.NomeProdotto', 'ProdNuovi.Conversione', 'UM.UM', 'R.Modello', 'ProdNuovi.cdMateriale', 'P.DescrizioneProdotto', 'ProdNuovi.dcRaggruppamentoPF', DB::raw('Year(DataOraInizio)'), DB::raw('Month(DataOraInizio)'))->get();

        return $result;
    }

    public function headings(): array
    {
        return [
            'Periodo','Materiale','Macchina','Prodotto','DescrizioneProdotto','Classe','NumeroFibre','UM','quantita'
        ];
    }
}
