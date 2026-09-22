<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ScartiExport implements WithMultipleSheets
{
    private array $dati;
    private array $dettaglio;

    public function __construct(array $dati, array $dettaglio = [])
    {
        $this->dati = $dati;
        $this->dettaglio = $dettaglio;
    }

    public function sheets(): array
    {
        return [
            new ScartiSheetExport($this->dati, 'ottico'),
            new ScartiSheetExport($this->dati, 'rame'),
            new ScartiDettaglioSheetExport($this->dettaglio['ottico'] ?? [], 'ottico'),
            new ScartiDettaglioSheetExport($this->dettaglio['rame'] ?? [], 'rame'),
        ];
    }
}
