<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FabbisogniOrdiniExport implements FromQuery, WithChunkReading, WithHeadings, ShouldAutoSize
{
    private $ordine = null;

    public function __construct($ordine = null)
    {
        if ($ordine && $ordine !== 'undefined' && $ordine !== 'null') {
            $this->ordine = trim($ordine);
        }
    }

    public function query()
    {
        $query = DB::connection('sqlsrv_root_gp')
            ->table('dbo.ProduzioneFabbisogniUtilizzati_T as FB')
            ->select(
                'PO.NomeProdotto as MaterialeOrdine',
                'DM.NRigaOrd as NumeroOrdine',
                'LT.Lotto as Lotto',
                'P.NomeProdotto as MaterialeFabbisogno',
                'R.Modello as Macchina',
                'PRD.Testo1 as Lotto Prduzione',
                'PRD.#Cicli as Quantità',
            )
            ->join('dbo.Lotto_Archivio as LT', 'LT.IdLotto', '=', 'FB.PF_FK_Lotto')
            ->join('Prodotti as P', 'P.IDProdotto', '=', 'FB.PF_FK_Prodotti')
            ->join('Produzione as PRD', 'FB.PF_FK_Produzione', '=', 'PRD.IDProduzione')
            ->join('Dettagli_sugli_ordini as DSO', 'DSO.IDDettagliOrdini', '=', 'PRD.IDDettOrd')
            ->join('Dettagli_Master as DM', 'DM.idMaster', '=', 'DSO.IDMaster')
            ->join('Prodotti as PO', 'PO.IDProdotto', '=', 'DM.IDProdotto')
            ->join('Risorse as R', 'R.IDRisorsa', '=', 'PRD.IDRis');

        if ($this->ordine) {
            $rawOrdini = preg_split('/[\s,;\r\n\t]+/', $this->ordine, -1, PREG_SPLIT_NO_EMPTY);
            $rawOrdini = array_values(array_unique(array_filter(array_map('trim', $rawOrdini))));
            if (!empty($rawOrdini)) {
                $expandedOrdini = [];
                foreach ($rawOrdini as $ord) {
                    $clean = trim($ord);
                    if ($clean !== '') {
                        $expandedOrdini[] = $clean;
                        $unpadded = ltrim($clean, '0');
                        if ($unpadded !== '') {
                            $expandedOrdini[] = $unpadded;
                            $expandedOrdini[] = '0000' . $unpadded;
                        }
                    }
                }
                $expandedOrdini = array_values(array_unique($expandedOrdini));

                // SQL Server supporta max 2100 parametri bindati:
                // i valori vengono inline come letterali (escape apici)
                $ordineList = implode(',', array_map(function ($v) {
                    return "'" . str_replace("'", "''", $v) . "'";
                }, $expandedOrdini));
                $query->where(function ($q) use ($ordineList, $rawOrdini) {
                    $q->whereRaw("DM.NRigaOrd IN ($ordineList)");
                    if (count($rawOrdini) === 1) {
                        $single = reset($rawOrdini);
                        $q->orWhere('DM.NRigaOrd', 'LIKE', '%' . $single . '%');
                    }
                });
            }
        }

        // ordine deterministico necessario per il chunk reading
        return $query->orderBy('DM.NRigaOrd', 'asc');
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function headings(): array
    {
        return [
            'Materiale Ordine',
            'Numero Ordine',
            'Lotto',
            'Materiale Fabbisogno',
            'Macchina',
            'Lotto Produzione',
            'Quantità'
        ];
    }
}
