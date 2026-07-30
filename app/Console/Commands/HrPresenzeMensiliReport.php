<?php

namespace App\Console\Commands;

use App\Models\HrEmployee;
use App\Models\TeamSystemRisultati;
use App\Models\Utility;
use App\Mail\HrPresenzeMensili;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HrPresenzeMensiliReport extends Command
{
    protected $signature = 'app:hr_presenze_mensili {--mese=} {--dry-run}';

    protected $description = 'Genera report Excel presenze mensili da TeamSystem e invia via email il 1° del mese';

    private const CAUSALI_MAP = [
        'STR'    => ['ST35', 'ST50', 'ST60'],
        'Ferie'  => ['FE'],
        'Mal.'   => ['MAL'],
        'Inf.'   => ['INFO'],
        'Mat.'   => ['MATA', 'MATE', 'MATA', 'MATF'],
        'Sciop.' => ['SCIO'],
        'P.n.r.' => ['PNRE', 'ASNG'],
        'Pr.'    => ['PRET', '104F', '104P', '104L', 'PLUF'],
        'CIG'    => ['CIS'],
        'P.Sin.' => ['PSIN'],
        'R.O.L.' => ['ROL'],
    ];

    private const COL_LAVOR = 'N';
    private const COL_SUM   = 'P';

    private const MESES_IT = [
        1 => 'GENNAIO', 2 => 'FEBBRAIO', 3 => 'MARZO', 4 => 'APRILE',
        5 => 'MAGGIO', 6 => 'GIUGNO', 7 => 'LUGLIO', 8 => 'AGOSTO',
        9 => 'SETTEMBRE', 10 => 'OTTOBRE', 11 => 'NOVEMBRE', 12 => 'DICEMBRE',
    ];

    public function handle()
    {
        $meseOption = $this->option('mese');

        if ($meseOption) {
            $dataRiferimento = new \DateTime($meseOption . '-01');
        } else {
            $dataRiferimento = new \DateTime('first day of last month');
        }

        $anno = (int) $dataRiferimento->format('Y');
        $mese = (int) $dataRiferimento->format('n');
        $meseDescrizione = self::MESES_IT[$mese] . ' ' . $anno;

        $dataInizio = $dataRiferimento->format('Y-m-01');
        $dataFine = $dataRiferimento->format('Y-m-t');

        $this->info("Generazione report presenze: {$meseDescrizione} ({$dataInizio} -> {$dataFine})");

        $filePath = $this->generaExcel($anno, $mese, $dataInizio, $dataFine);

        if (!$filePath) {
            $this->error('Errore nella generazione del file Excel.');
            return 1;
        }

        $this->info("File Excel generato: {$filePath}");

        if ($this->option('dry-run')) {
            $this->info('Dry-run: email non inviata.');
            return 0;
        }

        $emails = Utility::users_notify(['hr_presenze_mensili']);

        if (empty($emails)) {
            $this->warn('Nessun destinatario configurato per hr_presenze_mensili.');
            return 0;
        }

        Mail::to($emails)->send(new HrPresenzeMensili($meseDescrizione, $filePath));

        $this->info('Report inviato a ' . count($emails) . ' destinatari.');

        Storage::disk('local')->delete(basename($filePath));
        @unlink($filePath);

        return 0;
    }

    private function generaExcel(int $anno, int $mese, string $dataInizio, string $dataFine): ?string
    {
        $allCausali = array_merge(...array_values(self::CAUSALI_MAP));

        $risultati = TeamSystemRisultati::whereIn('causale', $allCausali)
            ->where('azienda', '0000000249')
            ->where('data', '>=', $dataInizio)
            ->where('data', '<=', $dataFine)
            ->get();

        $perDipendente = [];
        foreach ($risultati as $r) {
            $matricola = $r->matricola;
            if (!isset($perDipendente[$matricola])) {
                $perDipendente[$matricola] = [
                    'matricola' => $matricola,
                    'nome' => '',
                    'ore' => array_fill_keys(array_keys(self::CAUSALI_MAP), 0),
                ];
            }
            foreach (self::CAUSALI_MAP as $colonna => $causali) {
                if (in_array($r->causale, $causali)) {
                    $oreSecondi = (float) $r->ore;
                    $perDipendente[$matricola]['ore'][$colonna] += $oreSecondi / 3600;
                }
            }
        }

        $employees = HrEmployee::where(function ($q) {
            $q->where('dimesso', 0)->orWhereNull('dimesso');
        })->get();
        $employeeMap = [];
        foreach ($employees as $emp) {
            $matricolaPadded = str_pad($emp->matricola, 10, '0', STR_PAD_LEFT);
            $employeeMap[$matricolaPadded] = $emp;
        }

        foreach ($perDipendente as $mat => &$dato) {
            $emp = $employeeMap[$mat] ?? null;
            if (!$emp) {
                unset($perDipendente[$mat]);
                continue;
            }
            $dato['nome'] = $emp->nome_completo;
            $dato['categoria'] = $this->getCategoria($mat);
        }
        unset($dato);

        foreach ($employeeMap as $mat => $emp) {
            if (!isset($perDipendente[$mat])) {
                $perDipendente[$mat] = [
                    'matricola' => $mat,
                    'nome' => $emp->nome_completo,
                    'categoria' => $this->getCategoria($mat),
                    'ore' => array_fill_keys(array_keys(self::CAUSALI_MAP), 0),
                ];
            }
        }

        $operai = array_filter($perDipendente, fn($d) => $d['categoria'] === 'OP');
        $impiegati = array_filter($perDipendente, fn($d) => $d['categoria'] === 'IMP');

        usort($operai, fn($a, $b) => strcmp($a['matricola'], $b['matricola']));
        usort($impiegati, fn($a, $b) => strcmp($a['matricola'], $b['matricola']));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Foglio1');

        $colonne = array_keys(self::CAUSALI_MAP);
        $colCount = count($colonne);

        $lastCausaliCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + $colCount);

        $sheet->setCellValue('A1', $anno);
        $sheet->setCellValue('B1', 'ORE MESE');
        $colLetter = 'C';
        foreach ($colonne as $col) {
            $sheet->setCellValue($colLetter . '1', $col);
            $colLetter++;
        }
        $sheet->setCellValue(self::COL_LAVOR . '1', 'LAVOR.');

        $sheet->setCellValue('A2', self::MESES_IT[$mese]);
        $sheet->setCellValue('B2', '=21*8');

        $headerRow = 4;
        $sheet->setCellValue('A' . $headerRow, 'Dipendente');
        $sheet->setCellValue('B' . $headerRow, '');
        $colLetter = 'C';
        foreach ($colonne as $col) {
            $sheet->setCellValue($colLetter . $headerRow, $col);
            $colLetter++;
        }
        $sheet->setCellValue(self::COL_LAVOR . $headerRow, 'LAVOR.');

        $lastColForStyle = self::COL_SUM;
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6C2BD9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:' . $lastColForStyle . '1')->applyFromArray($headerStyle);
        $sheet->getStyle('A4:' . $lastColForStyle . $headerRow)->applyFromArray($headerStyle);

        $row = 5;

        $operaiStart = $row;
        $row = $this->scriviSezione($sheet, $row, 'OPERAI', $operai, $colonne, 'E3F2FD');
        $operaiTotaliRow = $row;
        $row = $this->scriviTotali($sheet, $row, 'ORE TOTALI OPERAI', $operai, $colonne, $operaiStart, $row - 1);
        $row++;

        $impiegatiStart = $row;
        $row = $this->scriviSezione($sheet, $row, 'IMPIEGATI', $impiegati, $colonne, 'E8F5E9');
        $impiegatiTotaliRow = $row;
        $row = $this->scriviTotali($sheet, $row, 'TOTALE ORE IMPIEGATI', $impiegati, $colonne, $impiegatiStart, $row - 1);

        // --- Tabella riepilogo categorie ---
        $row += 2;
        $row = $this->scriviRiepilogoCategorie($sheet, $row, $colonne, $operaiTotaliRow, $impiegatiTotaliRow);

        $fileName = 'presenze_' . $dataInizio . '.xlsx';
        $tempPath = storage_path('app/' . $fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    private function scriviSezione($sheet, int $startRow, string $titolo, array $dipendenti, array $colonne, string $bgColor): int
    {
        $row = $startRow;

        foreach ($dipendenti as $dato) {
            $sheet->setCellValue('A' . $row, $dato['nome']);
            $colLetter = 'C';
            foreach ($colonne as $col) {
                $valore = round($dato['ore'][$col], 2);
                if ($valore > 0) {
                    $sheet->setCellValue($colLetter . $row, $valore);
                }
                $colLetter++;
            }
            // N = LAVOR. = B2 - D - E - F - G - H - I - J - L - M - K (assenze, non STR)
            $sheet->setCellValue(self::COL_LAVOR . $row, "=\$B\$2-D{$row}-E{$row}-F{$row}-G{$row}-H{$row}-I{$row}-J{$row}-L{$row}-M{$row}-K{$row}");
            // P = SUM(D:N)
            $sheet->setCellValue(self::COL_SUM . $row, "=SUM(D{$row}:N{$row})");
            // Colore sfondo riga
            $sheet->getStyle('A' . $row . ':' . self::COL_SUM . $row)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
            ]);
            $row++;
        }

        return $row;
    }

    private function scriviTotali($sheet, int $row, string $label, array $dipendenti, array $colonne, int $firstDataRow, int $lastDataRow): int
    {
        $sheet->setCellValue('A' . $row, $label);
        $colLetter = 'C';
        foreach ($colonne as $col) {
            $sheet->setCellValue($colLetter . $row, "=SUM({$colLetter}{$firstDataRow}:{$colLetter}{$lastDataRow})");
            $colLetter++;
        }
        // N (LAVOR.) total
        $sheet->setCellValue(self::COL_LAVOR . $row, "=SUM(" . self::COL_LAVOR . "{$firstDataRow}:" . self::COL_LAVOR . "{$lastDataRow})");
        // P (SUM) total
        $sheet->setCellValue(self::COL_SUM . $row, "=SUM(" . self::COL_SUM . "{$firstDataRow}:" . self::COL_SUM . "{$lastDataRow})");

        $sheet->getStyle('A' . $row . ':' . self::COL_SUM . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8E5F0']],
        ]);

        return $row + 1;
    }

    private function getCategoria(string $matricola): string
    {
        $matricolaClean = ltrim($matricola, '0');
        if (str_starts_with($matricolaClean, '1')) {
            return 'IMP';
        }
        if (str_starts_with($matricolaClean, '2')) {
            return 'OP';
        }
        return 'OP';
    }

    private function scriviRiepilogoCategorie($sheet, int $row, array $colonne, int $operaiTotaliRow, int $impiegatiTotaliRow): int
    {
        $summaryHeaders = ['CATEGORIA', 'STRA', 'FER', 'MAL', 'INF', 'MAT', 'SCIOP', 'PNR', 'PR', 'CIG', 'P.SIN', 'ROL', 'LAV.'];
        $colLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];

        // Header row
        foreach ($summaryHeaders as $i => $header) {
            $sheet->setCellValue($colLetters[$i] . $row, $header);
        }
        $sheet->getStyle('A' . $row . ':M' . $row)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6C2BD9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;

        $categorie = ['OPERAI INDIRETTI', 'OPERAI DIRETTI', 'DIRIGENTI', 'IMPIEGATI'];
        $firstCatRow = $row;

        foreach ($categorie as $cat) {
            $sheet->setCellValue('A' . $row, $cat);
            $row++;
        }

        // TOTALE row with SUM formulas
        $totaleRow = $row;
        $sheet->setCellValue('A' . $row, 'TOTALE');
        for ($i = 1; $i < count($colLetters); $i++) {
            $col = $colLetters[$i];
            $sheet->setCellValue($col . $row, "=SUM({$col}{$firstCatRow}:{$col}" . ($row - 1) . ")");
        }
        $sheet->getStyle('A' . $row . ':M' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8E5F0']],
        ]);

        // IMPIEGATI row = reference to impiegati totali row
        // Summary cols B-L map to main cols C-M; Summary M maps to main N
        $impiegatiRow = $firstCatRow + 3;
        for ($i = 0; $i < count($colonne); $i++) {
            $summaryCol = $colLetters[$i + 1]; // B, C, D, ...
            $mainCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3 + $i); // C, D, E, ...
            $sheet->setCellValue($summaryCol . $impiegatiRow, "={$mainCol}{$impiegatiTotaliRow}");
        }
        $sheet->setCellValue('M' . $impiegatiRow, "=N{$impiegatiTotaliRow}");

        // OPERAI DIRETTI row = operai totali - operai indiretti
        $operaiDirettiRow = $firstCatRow + 1;
        $operaiIndirettiRow = $firstCatRow;
        for ($i = 0; $i < count($colonne); $i++) {
            $summaryCol = $colLetters[$i + 1];
            $mainCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3 + $i);
            $sheet->setCellValue($summaryCol . $operaiDirettiRow, "={$mainCol}{$operaiTotaliRow}-{$summaryCol}{$operaiIndirettiRow}");
        }
        $sheet->setCellValue('M' . $operaiDirettiRow, "=N{$operaiTotaliRow}-M{$operaiIndirettiRow}");

        return $row + 1;
    }
}
