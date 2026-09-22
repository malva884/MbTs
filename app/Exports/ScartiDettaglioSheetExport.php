<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ScartiDettaglioSheetExport implements FromArray, WithHeadings, WithTitle
{
    private array $rows;
    private string $divisione;

    public function __construct(array $rows, string $divisione)
    {
        $this->rows = $rows;
        $this->divisione = $divisione;
    }

    public function title(): string
    {
        return 'Dettaglio ' . ucfirst($this->divisione);
    }

    public function headings(): array
    {
        return [
            'Mese', 'Settimana', 'Tipo', 'Reparto',
            'Materiale', 'Descrizione', 'UM',
            'Quantità', 'KFKM', 'Importo €', 'N. Movimenti',
        ];
    }

    public function array(): array
    {
        return array_map(fn ($r) => [
            $r['mese'],
            $r['settimana'],
            $r['tipo'],
            $r['reparto'],
            $r['materiale'],
            $r['descrizione'],
            $r['um'],
            round($r['quantita'], 3),
            round($r['kfkm'], 2),
            round($r['importo'], 2),
            $r['num_movimenti'],
        ], $this->rows);
    }
}
