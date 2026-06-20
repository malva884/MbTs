<?php

namespace App\Exports;

use App\Models\QtConformita;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class Produzione implements FromCollection, WithHeadings
{
    private $ordine = null;
    private $fibre = null;
    private $data = null;
    private $um= null;

    private $materiale = null;
    public function __construct($ordine,$materiale,$data,$fibre,$um)
    {
        if($ordine != 'undefined')
            $this->ordine = $ordine;
        if($fibre != 'undefined')
            $this->fibre = $fibre;
        if($data != 'undefined')
            $this->data = $data;
        if($um != 'undefined')
            $this->um = $um;
        if($materiale != 'undefined')
            $this->materiale = $materiale;

    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {

        if (empty($this->data))
            $this->data = date('Y-m-d');


        $result = DB::connection('sqlsrv_root_gp')
            ->table('Produzione as PRD')
            ->select('O.NumeroOrdineAcquisto','P.NomeProdotto', 'P.DescrizioneProdotto','R.Modello','PRD.DataOraInizio', 'PRD.DataOraFine','UM.UM', 'PRD.#Cicli as quantita','P.Conversione12')
            ->join('Dettagli_sugli_ordini as DSO', 'DSO.IDDettagliOrdini', '=', 'PRD.IDDettOrd')
            ->join('Dettagli_Master as DM', 'DM.idMaster', '=', 'DSO.IDMaster')
            ->join('Ordini as O', 'O.IDOrdine', 'DM.IDOrdine')
            ->join('Prodotti as P', 'P.IDProdotto', '=', 'PRD.IDArticolo')
            ->join('UM', 'UM.IDUM', '=', 'DSO.IDUM')
            ->join('Risorse as R', 'R.IDRisorsa', '=', 'PRD.IDRis')
            ->where('PRD.Confermato', 1)
            ->where('PRD.Significativo', 1)
            ->where('PRD.IdSchedaPrdOrdineAcc', 0)
            ->Where(function ($query) {
                if ($this->materiale)
                    $query->where('P.NomeProdotto','LIKE', '%'.$this->materiale.'%');
            })
            ->Where(function ($query) {
                if ($this->ordine)
                    $query->Where('O.NumeroOrdineAcquisto', 'LIKE', '%' . $this->ordine . '%');
            })
            ->Where(function ($query) {
                if ($this->um)
                    $query->Where('UM.UM', $this->um);
            })
            ->Where(function ($query) {
                if ($this->fibre)
                    $query->Where('P.Conversione12', $this->fibre);
            })
            ->Where(function ($query) {
                if ( $this->data) {
                    $dataBy = explode(' to ',  $this->data);
                    if (count($dataBy) == 2)
                        $query->whereBetween('PRD.DataOraInizio', [$dataBy[0] . ' 00:00:00:000', $dataBy[1] . ' 23:59:59:990']);
                    else
                        $query->whereDate('PRD.DataOraInizio', $dataBy);
                }
            })
            ->get();

        return $result;
    }

    public function headings(): array
    {
        return [
            'NumeroOrdineAcquisto','NomeProdotto','DescrizioneProdotto','Modello','DataOraInizio','DataOraFine','UM','quantita','Conversione12'
        ];
    }
}
