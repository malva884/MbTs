<?php

namespace App\Http\Controllers;

use App\Models\OvertimeCost;
use App\Http\Controllers\TeamSystemReportController;
use Illuminate\Http\Request;

class OvertimeCostController extends Controller
{
    /**
     * Tariffario orario per gruppo di centro di costo (€/ora)
     */
    private function getDefaultHourlyRates()
    {
        return [
            'BlueCollar OFC' => 15.00,
            'BlueCollar CC' => 14.00,
            'Quality' => 16.00,
            'Maintenance' => 17.00,
            'Logistics' => 13.00,
            'Office' => 20.00,
            'Warehouse CC' => 14.50,
        ];
    }

    /**
     * Calcola e salva i costi degli straordinari per un mese specifico
     */
    public function calculateAndSaveCosts(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $year = $request->year;
        $month = $request->month;

        // Recupera i dati degli straordinari dal TeamSystemReportController
        $teamSystemController = new TeamSystemReportController();
        $dataInizio = sprintf('%04d-%02d-01', $year, $month);
        $dataFine = sprintf('%04d-%02d-%d', $year, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year));

        $fakeRequest = new Request([
            'data_inizio' => $dataInizio,
            'data_fine' => $dataFine,
        ]);

        $response = $teamSystemController->straordinariPerCentroDiCosto($fakeRequest);
        $overtimeData = json_decode($response->getContent(), true);

        if (!$overtimeData['success']) {
            return response()->json(['success' => false, 'message' => 'Errore nel recupero dati straordinari'], 500);
        }

        $hourlyRates = $this->getDefaultHourlyRates();
        $savedCount = 0;

        foreach ($overtimeData['data'] as $groupData) {
            $group = $groupData['cdc'];
            $rate = $hourlyRates[$group] ?? 0;

            foreach ($groupData['ore_per_settimana'] as $weekData) {
                $weekNumber = (int) str_replace('Settimana ', '', $weekData['settimana']);
                $hours = $weekData['ore'];
                $cost = $hours * $rate;

                // Upsert del record
                OvertimeCost::updateOrCreate(
                    [
                        'year' => $year,
                        'month' => $month,
                        'cost_center_group' => $group,
                        'week_number' => $weekNumber,
                    ],
                    [
                        'hours' => $hours,
                        'cost' => $cost,
                    ]
                );

                $savedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Salvati $savedCount record di costi",
        ]);
    }

    /**
     * Recupera i costi storici degli straordinari
     */
    public function getHistoricalCosts(Request $request)
    {
        $query = OvertimeCost::query();

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        if ($request->has('cost_center_group')) {
            $query->where('cost_center_group', $request->cost_center_group);
        }

        $costs = $query->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('week_number', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $costs,
        ]);
    }

    /**
     * Recupera i costi storici in formato matrice per reparto e settimana
     */
    public function getHistoricalCostsMatrix(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));

        \Log::info('getHistoricalCostsMatrix chiamato', ['year' => $year, 'month' => $month]);

        $defaultGroups = [
            'BlueCollar OFC',
            'BlueCollar CC',
            'Maintenance',
            'Quality',
            'Logistic OFC',
            'Warehouse CC',
            'Offices',
        ];

        $costs = OvertimeCost::where('year', $year)
            ->where('month', $month)
            ->get()
            ->groupBy('cost_center_group');

        \Log::info('Costi trovati', ['groups' => array_keys($costs->toArray()), 'count' => $costs->count()]);

        $matrix = [];

        foreach ($defaultGroups as $group) {
            $row = $this->buildMatrixRow($group, $costs->get($group, collect()));
            $matrix[] = $row;
        }

        foreach ($costs as $group => $groupCosts) {
            if (!in_array($group, $defaultGroups)) {
                $matrix[] = $this->buildMatrixRow($group, $groupCosts);
            }
        }

        \Log::info('Matrix generata', ['rows_count' => count($matrix), 'departments' => array_column($matrix, 'department')]);

        $totals = [
            'department' => 'Totals',
            'week1' => 0,
            'week2' => 0,
            'week3' => 0,
            'week4' => 0,
            'week5' => 0,
            'cost' => 0,
            'hours' => 0,
        ];

        foreach ($matrix as $row) {
            for ($i = 1; $i <= 5; $i++) {
                $totals["week{$i}"] += $row["week{$i}"];
            }
            $totals['cost'] += $row['cost'];
            $totals['hours'] += $row['hours'];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'rows' => $matrix,
                'totals' => $totals,
            ],
        ]);
    }

    private function buildMatrixRow($group, $groupCosts)
    {
        $row = [
            'department' => $group,
            'week1' => 0,
            'week2' => 0,
            'week3' => 0,
            'week4' => 0,
            'week5' => 0,
            'cost' => 0,
            'hours' => 0,
        ];

        foreach ($groupCosts as $cost) {
            $week = (int) $cost->week_number;
            if ($week >= 1 && $week <= 5) {
                $row["week{$week}"] += (float) $cost->hours;
                $row['hours'] += (float) $cost->hours;
                $row['cost'] += (float) $cost->cost;
            }
        }

        return $row;
    }

    /**
     * Calcola automaticamente le ore da TeamSystem
     */
    public function calculateHoursFromTeamSystem(Request $request)
    {
        \Log::info('calculateHoursFromTeamSystem chiamato', ['year' => $request->year, 'month' => $request->month, 'week' => $request->week]);

        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'week' => 'nullable|integer|min:1|max:5',
        ]);

        $year = $request->year;
        $month = $request->month;
        $week = $request->week;

        // Calcola le date di inizio e fine del mese
        $dataInizio = sprintf('%04d-%02d-01', $year, $month);
        $dataFine = date('Y-m-t', strtotime($dataInizio));

        \Log::info('Date calcolate', ['data_inizio' => $dataInizio, 'data_fine' => $dataFine]);

        // Chiama il controller TeamSystem per ottenere le ore
        try {
            $teamSystemController = new TeamSystemReportController();
            $teamSystemRequest = new Request([
                'data_inizio' => $dataInizio,
                'data_fine' => $dataFine,
                'causali' => ['RSTR', 'STRP', 'STRN'],
            ]);

            $response = $teamSystemController->straordinariPerCentroDiCosto($teamSystemRequest);
            $teamSystemData = json_decode($response->getContent(), true);

            \Log::info('Risposta TeamSystem', ['success' => $teamSystemData['success'] ?? false, 'data_count' => count($teamSystemData['data'] ?? [])]);

            if (!$teamSystemData['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore nel recupero dei dati da TeamSystem',
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Errore chiamata TeamSystem', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage(),
            ]);
        }

        // Mappa i gruppi TeamSystem ai reparti
        $groupMapping = [
            'BlueCollar OFC' => 'BlueCollar OFC',
            'BlueCollar CC' => 'BlueCollar CC',
            'Quality' => 'Quality',
            'Maintenance' => 'Maintenance',
            'Logistics' => 'Logistic OFC',
            'Office' => 'Offices',
            'Warehouse CC' => 'Warehouse CC',
            'Werehouse CC' => 'Warehouse CC', // Legacy mapping
        ];

        $defaultDepartments = [
            'BlueCollar OFC',
            'BlueCollar CC',
            'Maintenance',
            'Quality',
            'Logistic OFC',
            'Warehouse CC',
            'Offices',
        ];

        // Inizializza la matrice con valori a 0
        $matrix = [];
        foreach ($defaultDepartments as $dept) {
            $matrix[] = [
                'department' => $dept,
                'week1' => 0,
                'week2' => 0,
                'week3' => 0,
                'week4' => 0,
                'week5' => 0,
                'cost' => 0,
                'hours' => 0,
            ];
        }

        // Popola con i dati di TeamSystem
        foreach ($teamSystemData['data'] as $groupData) {
            $cdc = $groupData['cdc'];
            $mappedDept = $groupMapping[$cdc] ?? null;

            if ($mappedDept) {
                // Trova la riga corrispondente
                foreach ($matrix as &$row) {
                    if ($row['department'] === $mappedDept) {
                        // Mappa le settimane da TeamSystem
                        foreach ($groupData['ore_per_settimana'] as $weekData) {
                            $weekNum = (int) str_replace('Settimana ', '', $weekData['settimana']);
                            if ($weekNum >= 1 && $weekNum <= 5 && ($week === null || $weekNum === $week)) {
                                $row["week{$weekNum}"] += $weekData['ore'] / 3600; // Usa += per sommare invece di sovrascrivere
                            }
                        }
                        break;
                    }
                }
                unset($row); // Importante: rimuove il riferimento per evitare bug nei foreach successivi
            }
        }

        // Calcola totali per ogni riga
        foreach ($matrix as &$row) {
            $row['hours'] = $row['week1'] + $row['week2'] + $row['week3'] + $row['week4'] + $row['week5'];
        }
        unset($row); // Importante: rimuove il riferimento per evitare bug nei foreach successivi

        // Calcola totali generali
        $totals = [
            'department' => 'Totals',
            'week1' => 0,
            'week2' => 0,
            'week3' => 0,
            'week4' => 0,
            'week5' => 0,
            'cost' => 0,
            'hours' => 0,
        ];

        foreach ($matrix as $row) {
            for ($i = 1; $i <= 5; $i++) {
                $totals["week{$i}"] += $row["week{$i}"];
            }
            $totals['hours'] += $row['hours'];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'rows' => $matrix,
                'totals' => $totals,
            ],
        ]);
    }

    /**
     * Salva i dati manuali inseriti dall'utente
     */
    public function saveManualData(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'rows' => 'required|array',
        ]);

        $year = $request->year;
        $month = $request->month;
        $rows = $request->rows;

        $savedCount = 0;

        foreach ($rows as $row) {
            $department = $row['department'];

            for ($week = 1; $week <= 5; $week++) {
                $hours = $row["week{$week}"] ?? 0;
                $cost = $row['cost'] ?? 0;

                if ($hours > 0 || $cost > 0) {
                    OvertimeCost::updateOrCreate(
                        [
                            'year' => $year,
                            'month' => $month,
                            'cost_center_group' => $department,
                            'week_number' => $week,
                        ],
                        [
                            'hours' => $hours,
                            'cost' => $cost / 5,
                        ]
                    );
                    $savedCount++;
                } else {
                    OvertimeCost::where('year', $year)
                        ->where('month', $month)
                        ->where('cost_center_group', $department)
                        ->where('week_number', $week)
                        ->delete();
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Salvati $savedCount record di costi",
        ]);
    }

    /**
     * Aggiorna il tariffario orario
     */
    public function updateHourlyRates(Request $request)
    {
        $request->validate([
            'rates' => 'required|array',
            'rates.*' => 'required|numeric|min:0',
        ]);

        // Per ora salviamo in session o config, in futuro potremmo creare una tabella dedicata
        session(['overtime_hourly_rates' => $request->rates]);

        return response()->json([
            'success' => true,
            'message' => 'Tariffario aggiornato',
            'rates' => $request->rates,
        ]);
    }

    /**
     * Recupera il tariffario orario corrente
     */
    public function getHourlyRates()
    {
        $rates = session('overtime_hourly_rates', $this->getDefaultHourlyRates());

        return response()->json([
            'success' => true,
            'data' => $rates,
        ]);
    }

    /**
     * Report mensile ore e costi per reparto, calcolato dai dati TeamSystem
     */
    public function getMonthlyReport(Request $request)
    {
        $request->validate([
            'data_inizio' => 'required|date',
            'data_fine' => 'required|date',
            'causali' => 'nullable|array',
            'causali.*' => 'string',
        ]);

        $dataInizio = $request->data_inizio;
        $dataFine = $request->data_fine;
        $causali = $request->causali ?: ['RSTR', 'STRP', 'STRN'];

        try {
            $teamSystemController = new TeamSystemReportController();
            $teamSystemRequest = new Request([
                'data_inizio' => $dataInizio,
                'data_fine' => $dataFine,
                'causali' => $causali,
            ]);

            $response = $teamSystemController->straordinariPerCentroDiCosto($teamSystemRequest);
            $teamSystemData = json_decode($response->getContent(), true);

            if (!$teamSystemData['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore nel recupero dei dati da TeamSystem',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage(),
            ]);
        }

        $groupMapping = [
            'BlueCollar OFC' => 'BlueCollar OFC',
            'BlueCollar CC' => 'BlueCollar CC',
            'Quality' => 'Quality',
            'Maintenance' => 'Maintenance',
            'Logistics' => 'Logistic OFC',
            'Office' => 'Offices',
            'Warehouse CC' => 'Warehouse CC',
            'Werehouse CC' => 'Warehouse CC',
        ];

        $hourlyRates = $this->getDefaultHourlyRates();
        $report = [];

        foreach ($teamSystemData['data'] as $groupData) {
            $cdc = $groupData['cdc'];
            $mappedDept = $groupMapping[$cdc] ?? null;

            if (!$mappedDept) {
                continue;
            }

            $hours = ($groupData['totali_ore'] ?? 0) / 3600;
            $rate = $hourlyRates[$mappedDept] ?? 0;
            $cost = $hours * $rate;

            if (!isset($report[$mappedDept])) {
                $report[$mappedDept] = [
                    'department' => $mappedDept,
                    'hours' => 0,
                    'cost' => 0,
                ];
            }

            $report[$mappedDept]['hours'] += $hours;
            $report[$mappedDept]['cost'] += $cost;
        }

        return response()->json([
            'success' => true,
            'data' => array_values($report),
        ]);
    }

    /**
     * Report annuale ore e costi per reparto, ultimi 4 anni
     */
    public function getAnnualReport(Request $request)
    {
        $currentYear = date('Y');
        $years = [];

        for ($i = 3; $i >= 0; $i--) {
            $years[] = $currentYear - $i;
        }

        $report = [];

        foreach ($years as $year) {
            $yearData = OvertimeCost::where('year', $year)
                ->selectRaw('SUM(hours) as hours')
                ->selectRaw('SUM(cost) as cost')
                ->first();

            $report[] = [
                'year' => $year,
                'hours' => $yearData ? $yearData->hours : 0,
                'cost' => $yearData ? $yearData->cost : 0,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }
}
