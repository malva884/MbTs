<?php

namespace App\Exports;

use App\Services\GpDatiMacchinaService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VelocitaOrdiniExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping, ShouldAutoSize
{
    private $ordine = null;
    private $macchina = null;
    private $cavo = null;
    private $dataDa = null;
    private $dataA = null;
    private $velocitaMinime = null;

    public function __construct($ordine = null, $macchina = null, $cavo = null, $dataDa = null, $dataA = null)
    {
        if ($ordine && $ordine !== 'undefined' && $ordine !== 'null') {
            $this->ordine = trim($ordine);
        }
        if ($macchina && $macchina !== 'undefined' && $macchina !== 'null') {
            $this->macchina = trim($macchina);
        }
        if ($cavo && $cavo !== 'undefined' && $cavo !== 'null') {
            $this->cavo = trim($cavo);
        }
        if ($dataDa && $dataDa !== 'undefined' && $dataDa !== 'null') {
            $this->dataDa = $dataDa;
        }
        if ($dataA && $dataA !== 'undefined' && $dataA !== 'null') {
            $this->dataA = $dataA;
        }
    }

    public function query()
    {
        // La velocita' media viene calcolata in PHP nel map(): una subquery
        // correlata su STL_DatiMacchina_V rendeva l'esportazione troppo lenta.
        $query = DB::connection('sqlsrv_root_gp')
            ->table('Produzione as PRD')
            ->select(
                'DM.NRigaOrd as NumeroOrdine',
                'R.Modello as Macchina',
                'P.NomeProdotto as Prodotto',
                'P.DescrizioneProdotto',
                'PRD.IDRis',
                'PRD.DataOraInizio',
                'PRD.DataOraFine'
            )
            ->join('Dettagli_sugli_ordini as DSO', 'DSO.IDDettagliOrdini', '=', 'PRD.IDDettOrd')
            ->join('Dettagli_Master as DM', 'DM.idMaster', '=', 'DSO.IDMaster')
            ->join('Prodotti as P', 'P.IDProdotto', '=', 'PRD.IDArticolo')
            ->join('Risorse as R', 'R.IDRisorsa', '=', 'PRD.IDRis')
            ->where('PRD.Confermato', 1)
            ->where('PRD.Significativo', 1)
            ->where('PRD.IdSchedaPrdOrdineAcc', 0)
            ->when($this->macchina, function ($q) {
                $q->where('R.Modello', $this->macchina);
            })
            ->when($this->cavo, function ($q) {
                $q->where('P.NomeProdotto', 'LIKE', '%' . $this->cavo . '%');
            })
            ->when($this->dataDa && $this->dataA, function ($q) {
                $q->whereBetween('PRD.DataOraInizio', [$this->dataDa, $this->dataA]);
            });

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
        return $query->orderBy('DM.NRigaOrd', 'asc')->orderBy('PRD.IDProduzione', 'asc');
    }

    public function map($row): array
    {
        // il DB restituisce stringhe in Windows-1252: converti per PhpSpreadsheet
        $conv = function ($v) {
            return is_string($v) ? mb_convert_encoding($v, 'UTF-8', 'Windows-1252') : $v;
        };

        // Media 'Velocità Linea' sull'intervallo [DataOraInizio, DataOraFine],
        // escludendo i campioni sotto velocita_minima della macchina
        $media = null;
        if (!empty($row->IDRis) && !empty($row->DataOraInizio)) {
            $soglia = $this->velocitaMinima($row->IDRis);
            $media = GpDatiMacchinaService::query()
                ->where('IDRisorsa', (string) $row->IDRis)
                ->where('Caratteristica', 'Velocità Linea')
                ->when($soglia !== null, fn ($q) => $q->where('ValoreRegistrato', '>=', $soglia))
                ->where('DataRegistrazione', '>=', $row->DataOraInizio)
                ->where('DataRegistrazione', '<=', !empty($row->DataOraFine) ? $row->DataOraFine : date('Y-m-d H:i:s'))
                ->avg('ValoreRegistrato');
            if (is_numeric($media) && (float) $media <= 0) {
                $media = null;
            }
        }

        return [
            $conv($row->NumeroOrdine ?? null),
            $conv($row->Macchina ?? null),
            $conv($row->Prodotto ?? null),
            $conv($row->DescrizioneProdotto ?? null),
            $row->DataOraInizio,
            $row->DataOraFine,
            is_numeric($media) ? round((float) $media, 2) : null,
        ];
    }

    /**
     * Soglia di velocita' della risorsa (machineries.velocita_minima via id_gp).
     * Mappa caricata una sola volta; null se la risorsa non e' configurata.
     */
    private function velocitaMinima($idRis): ?float
    {
        if ($this->velocitaMinime === null) {
            try {
                $this->velocitaMinime = DB::table('machineries')
                    ->pluck('velocita_minima', 'id_gp')
                    ->all();
            } catch (\Exception $e) {
                $this->velocitaMinime = [];
            }
        }
        $v = $this->velocitaMinime[(string) $idRis] ?? null;
        return is_numeric($v) ? (float) $v : null;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function headings(): array
    {
        return [
            'Numero Ordine',
            'Macchina',
            'Prodotto',
            'Descrizione Prodotto',
            'Data Inizio',
            'Data Fine',
            'Velocità Media',
        ];
    }
}
