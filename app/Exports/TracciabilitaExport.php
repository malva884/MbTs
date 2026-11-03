<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TracciabilitaExport implements FromQuery, WithChunkReading, WithHeadings, ShouldAutoSize
{
    private $lotto = null;
    private $ordine = null;
    private $tipoLotto = null;

    public function __construct($lotto = null, $ordine = null, $tipoLotto = null)
    {
        if ($lotto && $lotto !== 'undefined' && $lotto !== 'null') {
            $this->lotto = trim($lotto);
        }
        if ($ordine && $ordine !== 'undefined' && $ordine !== 'null') {
            $this->ordine = trim($ordine);
        }
        if ($tipoLotto && $tipoLotto !== 'undefined' && $tipoLotto !== 'null') {
            $this->tipoLotto = trim($tipoLotto);
        }
    }

    public function query()
    {
        $query = DB::connection('sqlsrv_root_gp')
            ->table('Produzione as PRD')
            ->select(
                'LT.Lotto as LottoComponente',
                'P.NomeProdotto',
                'P.DescrizioneProdotto',
                'DM.NRigaOrd',
                'PRD.Testo1 as LottoProduzione',
                'PRD.DataOraInizio',
                'PRD.DataOraFine'
            )
            ->join('dbo.ProduzioneFabbisogniUtilizzati_T as FB', 'FB.PF_FK_Produzione', '=', 'PRD.IDProduzione')
            ->join('dbo.Lotto_Archivio as LT', 'LT.IdLotto', '=', 'FB.PF_FK_Lotto')
            ->join('Dettagli_sugli_ordini as DSO', 'DSO.IDDettagliOrdini', '=', 'PRD.IDDettOrd')
            ->join('Dettagli_Master as DM', 'DM.idMaster', '=', 'DSO.IDMaster')
            ->join('Prodotti as P', 'P.IDProdotto', '=', 'PRD.IDArticolo');

        if ($this->lotto) {
            $rawLotti = preg_split('/[\s,;\r\n\t]+/', $this->lotto, -1, PREG_SPLIT_NO_EMPTY);
            $rawLotti = array_values(array_unique(array_filter(array_map('trim', $rawLotti))));
            if (!empty($rawLotti)) {
                // SQL Server supporta max 2100 parametri bindati:
                // i valori vengono inline come letterali (escape apici)
                $lottoList = implode(',', array_map(function ($v) {
                    return "'" . str_replace("'", "''", $v) . "'";
                }, $rawLotti));
                $query->where(function ($q) use ($lottoList) {
                    if ($this->tipoLotto === 'componente') {
                        $q->whereRaw("LT.Lotto IN ($lottoList)");
                    } elseif ($this->tipoLotto === 'produzione') {
                        $q->whereRaw("PRD.Testo1 IN ($lottoList)");
                    } else {
                        $q->whereRaw("LT.Lotto IN ($lottoList)")
                          ->orWhereRaw("PRD.Testo1 IN ($lottoList)");
                    }
                });
            }
        }

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

                // stessa soluzione: letterali inline per evitare il limite dei 2100 parametri
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
        return $query->orderBy('DM.NRigaOrd', 'desc');
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function headings(): array
    {
        return [
            'Lotto Componente',
            'Nome Prodotto',
            'Descrizione Prodotto',
            'Numero Ordine',
            'Lotto Produzione',
            'Data Inizio',
            'Data Fine',
        ];
    }
}
