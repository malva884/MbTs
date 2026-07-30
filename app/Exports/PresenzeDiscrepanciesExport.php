<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PresenzeDiscrepanciesExport implements FromCollection, WithHeadings, WithStyles
{
    private $discrepancies;

    public function __construct($discrepancies)
    {
        $this->discrepancies = collect($discrepancies);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->discrepancies;
    }

    public function headings(): array
    {
        return [
            'Matricola',
            'Data',
            'Tipologia Vecchio Sistema',
            'Tipologia Nuovo Sistema',
            'Ore Vecchio Sistema',
            'Ore Nuovo Sistema',
            'Tipo Discrepanza',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
