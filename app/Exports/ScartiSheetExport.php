<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ScartiSheetExport implements FromArray, WithHeadings, WithTitle
{
    private array $dati;
    private string $divisione;

    private const REPARTI = [
        'ottico' => ['JACK', 'SZD', 'BUF', 'PE', 'FO'],
        'rame' => ['PF', 'SM', 'MR', 'WR'],
    ];

    public function __construct(array $dati, string $divisione)
    {
        $this->dati = $dati;
        $this->divisione = $divisione;
    }

    public function title(): string
    {
        return ucfirst($this->divisione);
    }

    public function headings(): array
    {
        return [
            'Mese', 'Voce',
            'W1 €', 'W2 €', 'W3 €', 'W4 €', 'Totale €',
            'W1 %', 'W2 %', 'W3 %', 'W4 %', 'Totale %',
        ];
    }

    public function array(): array
    {
        $isRame = $this->divisione === 'rame';
        $reparti = self::REPARTI[$this->divisione];
        $rows = [];

        foreach ($this->dati as $mese => $items) {
            $label = $mese;

            foreach ($reparti as $reparto) {
                $row = [$label, 'Scarto ' . $reparto];
                for ($w = 1; $w <= 4; $w++) {
                    $row[] = $this->num($items[$reparto][$w]['Scarto'] ?? null);
                }
                $row[] = $this->num($items[$reparto]['t_scarto'] ?? null);
                for ($w = 1; $w <= 4; $w++) {
                    $row[] = $this->num($items[$reparto][$w]['Dif'] ?? null);
                }
                $row[] = $this->num($items[$reparto]['t_dif'] ?? null);
                $rows[] = $row;
                $label = '';
            }

            // Riga consumi (valori € nelle colonne settimanali, colonne % vuote)
            $row = [$label, 'Consumi'];
            for ($w = 1; $w <= 4; $w++) {
                $row[] = $this->num($isRame
                    ? ($items['rame'][$w]['Consumi'] ?? null)
                    : ($items[$w]['Consumi'] ?? null));
            }
            $row[] = $this->num($isRame ? ($items['Consumi_Rame'] ?? null) : ($items['Consumi'] ?? null));
            $rows[] = array_pad($row, 12, null);
            $label = '';

            // Riga totale scarti + diff
            $row = [$label, 'Totale Scarti'];
            for ($w = 1; $w <= 4; $w++) {
                $sum = 0;
                foreach ($reparti as $reparto) {
                    $v = $items[$reparto][$w]['Scarto'] ?? null;
                    if (is_numeric($v)) {
                        $sum += $v;
                    }
                }
                $row[] = $sum > 0 ? round($sum, 2) : null;
            }
            $row[] = $this->num($isRame ? ($items['totale_scarto_rame'] ?? null) : ($items['totale_scarto'] ?? null));
            $keyDifSettimana = $isRame ? 'totale_dif_settimana_rame' : 'totale_dif_settimana';
            for ($w = 1; $w <= 4; $w++) {
                $row[] = $this->num($items[$keyDifSettimana][$w] ?? null);
            }
            $row[] = $this->num($isRame ? ($items['totale_dif_rame'] ?? null) : ($items['totale_dif'] ?? null));
            $rows[] = $row;
        }

        return $rows;
    }

    private function num($value)
    {
        return is_numeric($value) ? round((float) $value, 2) : null;
    }
}
