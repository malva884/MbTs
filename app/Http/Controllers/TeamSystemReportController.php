<?php

namespace App\Http\Controllers;

use App\Models\TeamSystemDipenTurnazioni;
use App\Models\TeamSystemGiustificazioni;
use App\Models\TeamSystemRisultati;
use App\Models\TeamSystemTimbrature;
use App\Models\TeamSystemTurnazioni;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamSystemReportController extends Controller
{
    /**
     * Determina il gruppo di un centro di costo in base al raggruppamento richiesto
     */
    private function getCostCenterGroup($cdcName)
    {
        // Normalizza il nome: minuscolo, senza spazi, senza &, senza accenti
        $centro = mb_strtolower(trim($cdcName));
        $centro = str_replace([' ', '&', '-', '_'], '', $centro);
        $centro = iconv('UTF-8', 'ASCII//TRANSLIT', $centro);
        $centro = preg_replace('/[^a-z0-9]/', '', $centro);

        if (str_contains($centro, 'warehouse') && str_contains($centro, 'ofc'))
            $centro = 'bluecollar_ofc';
        elseif (str_contains($centro, 'warehouse') && str_contains($centro, 'cc'))
            $centro = 'bluecollar_cc';
        elseif (str_contains($centro, 'marcatura') || str_contains($centro, 'taglio'))
            $centro = 'bluecollar_ofc';
        elseif (str_contains($centro, 'qualitylab') || str_contains($centro, 'qualitychecker') || str_contains($centro, 'qualitaassurance'))
            $centro = 'quality';
        elseif (str_contains($centro, 'approvazione') && str_contains($centro, 'produzione'))
            $centro = 'bluecollar_ofc';
        elseif (str_contains($centro, 'logistic'))
            $centro = 'logistic_ofc';
        elseif (str_contains($centro, 'maintenance'))
            $centro = 'maintenance';
        elseif (str_contains($centro, 'bluecollar') && str_contains($centro, 'ofc'))
            $centro = 'bluecollar_ofc';
        elseif (str_contains($centro, 'bluecollar') && str_contains($centro, 'cc'))
            $centro = 'bluecollar_cc';
        elseif (str_contains($centro, 'quality'))
            $centro = 'quality';

        if (!in_array($centro, ['bluecollar_ofc', 'bluecollar_cc', 'maintenance', 'quality', 'logistic_ofc', 'warehouse_cc']))
            $centro = 'offices';

        $labels = [
            'bluecollar_ofc' => 'BlueCollar OFC',
            'bluecollar_cc' => 'BlueCollar CC',
            'maintenance' => 'Maintenance',
            'quality' => 'Quality',
            'logistic_ofc' => 'Logistics',
            'warehouse_cc' => 'Warehouse CC',
            'offices' => 'Office',
        ];

        return $labels[$centro] ?? 'Office';
    }

    /**
     * Report straordinari per centro di costo
     */
    public function straordinariPerCentroDiCosto(Request $request)
    {
        $dataInizio = $request->data_inizio ?? date('Y-m-01');
        $dataFine = $request->data_fine ?? date('Y-m-t');

        // Recupera tutti i dipendenti HR per mappare matricola -> centro di costo
        $allEmployees = \App\Models\HrEmployee::all();
        $employeeMap = [];
        foreach ($allEmployees as $employee) {
            $matricolaPadded = str_pad($employee->matricola, 10, '0', STR_PAD_LEFT);
            $employeeMap[$matricolaPadded] = $employee;
        }

        // Query diretta su risultati per straordinari (causale LIKE 'ST%')
        $risultati = TeamSystemRisultati::whereIn('causale', ['ST35','ST50','ST60'])
            ->where('azienda', '0000000249')
            ->where('data', '>=', $dataInizio)
            ->where('data', '<=', $dataFine)
            ->get();

        // Definisci i gruppi possibili
        $groups = ['BlueCollar OFC', 'BlueCollar CC', 'Quality', 'Maintenance', 'Logistics', 'Office', 'Warehouse CC'];

        $results = [];
        foreach ($groups as $group) {
            $results[$group] = [
                'cdc' => $group,
                'numero_giustificazioni' => 0,
                'totali_ore' => 0,
                'numero_dipendenti' => 0,
                'matricole' => [],
                'ore_per_settimana' => []
            ];
        }

        foreach ($risultati as $risultato) {
            $matricolaPadded = $risultato->matricola;
            $matricolaNormalizzata = ltrim($matricolaPadded, '0');

            $employee = $employeeMap[$matricolaPadded] ?? null;
            if (!$employee || !$employee->centro_id) {
                continue;
            }

            $centroCosto = \App\Models\HrCostCenter::find($employee->centro_id);
            if (!$centroCosto) {
                continue;
            }

            $group = $this->getCostCenterGroup($centroCosto->centro_di_costo);
            if (!isset($results[$group])) {
                continue;
            }

            // ore è in secondi, arrotonda per difetto a multipli di 30 minuti (1800 secondi)
            $oreInSecondi = (float)$risultato->ore;
            $oreArrotondate = floor($oreInSecondi / 1800) * 1800;

            if ($oreArrotondate < 1800) {
                continue;
            }

            $results[$group]['numero_giustificazioni']++;
            $results[$group]['totali_ore'] += $oreArrotondate;
            if (!in_array($matricolaNormalizzata, $results[$group]['matricole'])) {
                $results[$group]['matricole'][] = $matricolaNormalizzata;
                $results[$group]['numero_dipendenti']++;
            }

            // Raggruppa per settimana
            $dayOfMonth = (int)$risultato->data->format('d');
            $weekNumber = ceil($dayOfMonth / 7);
            $weekKey = 'Settimana ' . $weekNumber;

            if (!isset($results[$group]['ore_per_settimana'][$weekKey])) {
                $results[$group]['ore_per_settimana'][$weekKey] = 0;
            }
            $results[$group]['ore_per_settimana'][$weekKey] += $oreArrotondate;
        }

        // Converti in array e ordina per totali_ore
        $results = array_values($results);
        usort($results, function($a, $b) {
            return $b['totali_ore'] <=> $a['totali_ore'];
        });

        // Rimuovi il campo matricole e converti le settimane
        foreach ($results as &$result) {
            unset($result['matricole']);

            $settimaneArray = [];
            ksort($result['ore_per_settimana']);
            foreach ($result['ore_per_settimana'] as $week => $ore) {
                $settimaneArray[] = [
                    'settimana' => $week,
                    'ore' => $ore
                ];
            }
            $result['ore_per_settimana'] = $settimaneArray;
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Dettaglio straordinari per centro di costo, raggruppati per dipendente
     */
    public function dettaglioGiustificazioni(Request $request)
    {
        $request->validate([
            'cdc' => 'required|string',
            'data_inizio' => 'nullable|date',
            'data_fine' => 'nullable|date|after_or_equal:data_inizio',
        ]);

        $dataInizio = $request->data_inizio ?? date('Y-m-01');
        $dataFine = $request->data_fine ?? date('Y-m-t');

        // Il cdc passato è un gruppo (es. 'BlueCollar OFC'), trova tutti i centri di costo che mappano a questo gruppo
        $allCostCenters = \App\Models\HrCostCenter::all();
        $centroIds = [];
        foreach ($allCostCenters as $cc) {
            if ($this->getCostCenterGroup($cc->centro_di_costo) === $request->cdc) {
                $centroIds[] = $cc->id;
            }
        }

        if (empty($centroIds)) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        // Trova tutti i dipendenti dei centri di costo di questo gruppo
        $centroCostoMap = \App\Models\HrCostCenter::whereIn('id', $centroIds)->pluck('centro_di_costo', 'id');
        $employees = \App\Models\HrEmployee::whereIn('centro_id', $centroIds)->get();
        $employeeMap = [];
        $matricole = [];
        foreach ($employees as $employee) {
            $matricolaPadded = str_pad($employee->matricola, 10, '0', STR_PAD_LEFT);
            $matricole[] = $matricolaPadded;
            $employeeMap[$matricolaPadded] = $employee;
        }

        if (empty($matricole)) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        // Query diretta su risultati per straordinari (causale LIKE 'ST%')
        $risultati = TeamSystemRisultati::whereIn('matricola', $matricole)
            ->whereIn('causale', ['ST35','ST50','ST60'])
            ->where('data', '>=', $dataInizio)
            ->where('data', '<=', $dataFine)
            ->where('azienda', '0000000249')
            ->orderBy('data', 'asc')
            ->get();

        // Raggruppa per dipendente
        $perDipendente = [];
        foreach ($risultati as $risultato) {
            $matricolaPadded = $risultato->matricola;
            $employee = $employeeMap[$matricolaPadded] ?? null;
            if (!$employee) {
                continue;
            }

            $oreInSecondi = (float)$risultato->ore;
            $oreArrotondate = floor($oreInSecondi / 1800) * 1800;

            if ($oreArrotondate < 1800) {
                continue;
            }

            $oreInDecimali = $oreArrotondate / 3600;
            $data = $risultato->data->format('Y-m-d');

            if (!isset($perDipendente[$matricolaPadded])) {
                $perDipendente[$matricolaPadded] = [
                    'matricola' => ltrim($matricolaPadded, '0'),
                    'nome' => $employee->nome ?? '',
                    'cognome' => $employee->cognome ?? '',
                    'full_name' => trim(($employee->nome ?? '') . ' ' . ($employee->cognome ?? '')),
                    'centro_di_costo' => $centroCostoMap[$employee->centro_id] ?? '',
                    'totale_ore' => 0,
                    'giorni' => [],
                ];
            }

            $perDipendente[$matricolaPadded]['giorni'][] = [
                'data' => $data,
                'ore' => $oreInDecimali,
                'causale' => $risultato->causale,
            ];
            $perDipendente[$matricolaPadded]['totale_ore'] += $oreInDecimali;
        }

        // Aggiungi anche i dipendenti senza straordinari (per trasparenza)
        foreach ($employeeMap as $matricolaPadded => $employee) {
            if (!isset($perDipendente[$matricolaPadded])) {
                $perDipendente[$matricolaPadded] = [
                    'matricola' => ltrim($matricolaPadded, '0'),
                    'nome' => $employee->nome ?? '',
                    'cognome' => $employee->cognome ?? '',
                    'full_name' => trim(($employee->nome ?? '') . ' ' . ($employee->cognome ?? '')),
                    'centro_di_costo' => $centroCostoMap[$employee->centro_id] ?? '',
                    'totale_ore' => 0,
                    'giorni' => [],
                ];
            }
        }

        // Ordina per totale ore decrescente
        $results = array_values($perDipendente);
        usort($results, function($a, $b) {
            return $b['totale_ore'] <=> $a['totale_ore'];
        });

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Espande una sequenza di giorni in un array di numeri (1=lunedì ... 6=sabato, 0=domenica).
     * Esempio: 'L-G' -> [1,2,3,4], 'V' -> [5]
     */
    private function espandiGiorniTurno($giornoInizio, $giornoFine)
    {
        $map = [
            'l' => 1,
            'm' => 2,
            'g' => 4,
            'v' => 5,
            's' => 6,
            'd' => 0,
        ];

        $inizio = isset($map[$giornoInizio]) ? $map[$giornoInizio] : null;
        // Gestione M-M (Martedì-Mercoledì): il secondo M è Mercoledì (3)
        if (!empty($giornoFine) && strtolower($giornoFine) === 'm' && strtolower($giornoInizio) === 'm') {
            $fine = 3;
        } else {
            $fine = !empty($giornoFine) && isset($map[$giornoFine]) ? $map[$giornoFine] : $inizio;
        }

        if ($inizio === null) {
            return [];
        }

        $giorni = [];
        $current = $inizio;
        while (true) {
            $giorni[] = $current;
            if ($current === $fine) {
                break;
            }
            $current = ($current % 7) + 1;
            if ($current === $inizio) {
                break;
            }
        }

        return $giorni;
    }

    /**
     * Calcola le ore previste da una descrizione turnazione per un giorno specifico.
     * Esempio: '8:30-17:00 L-G 8:30-16:00 V' -> il giovedì conta 8.5 ore, il venerdì 7.5.
     *
     * @param string $descrizione
     * @param int $giornoSettimana 0=domenica, 1=lunedì, ..., 6=sabato
     */
    private function calcolaOreTurno($descrizione, $giornoSettimana)
    {
        if (empty($descrizione)) {
            return 0;
        }

        $descrizioneLower = strtolower($descrizione);

        // Estrae tutti gli intervalli orari con la loro posizione.
        preg_match_all('/(\d{1,2})\s*(?:[:\.](\d{2}))?\s*-\s*(\d{1,2})\s*(?:[:\.](\d{2}))?/i', $descrizioneLower, $intervalliMatches, PREG_OFFSET_CAPTURE);

        if (empty($intervalliMatches[0])) {
            return 0;
        }

        // Estrae le sequenze di giorni con la loro posizione (L-G, L-V, V, S, D, etc.)
        preg_match_all('/([lgvsmd])(?:\s*-\s*([lgvsmd]))?/i', $descrizioneLower, $giorniMatches, PREG_OFFSET_CAPTURE);

        // Se non ci sono giorni, applica tutti gli intervalli a prescindere dal giorno.
        if (empty($giorniMatches[0])) {
            $oreTotali = 0;
            foreach ($intervalliMatches[0] as $i => $_) {
                $oreTotali += $this->parseIntervalloOre($intervalliMatches, $i);
            }
            return $oreTotali;
        }

        // Costruisce i blocchi giorni (testo, posizione, giorni espansi).
        $blocchiGiorni = [];
        foreach ($giorniMatches[0] as $i => $match) {
            $blocchiGiorni[] = [
                'posizione' => $match[1],
                'giorni' => $this->espandiGiorniTurno(
                    strtolower($giorniMatches[1][$i][0]),
                    $giorniMatches[2][$i][0] ?? null
                ),
            ];
        }

        // Per ogni intervallo trova il blocco giorni più vicino che lo precede.
        $oreTotali = 0;
        foreach ($intervalliMatches[0] as $i => $match) {
            $posIntervallo = $match[1];
            $bloccoGiorni = null;

            foreach ($blocchiGiorni as $blocco) {
                if ($blocco['posizione'] < $posIntervallo) {
                    $bloccoGiorni = $blocco;
                }
                else {
                    break;
                }
            }

            // Se l'intervallo precede tutti i giorni, usa il primo blocco.
            if ($bloccoGiorni === null) {
                $bloccoGiorni = $blocchiGiorni[0];
            }

            // Se il giorno corrente rientra nel blocco, aggiungi le ore.
            if (in_array($giornoSettimana, $bloccoGiorni['giorni'], true)) {
                $oreTotali += $this->parseIntervalloOre($intervalliMatches, $i);
            }
        }

        return $oreTotali;
    }

    /**
     * Calcola la durata in ore di un singolo intervallo dai match della regex.
     */
    private function parseIntervalloOre($intervalliMatches, $index)
    {
        $oraInizio = (int)$intervalliMatches[1][$index][0];
        $minutoInizio = !empty($intervalliMatches[2][$index][0]) ? (int)$intervalliMatches[2][$index][0] : 0;
        $oraFine = (int)$intervalliMatches[3][$index][0];
        $minutoFine = !empty($intervalliMatches[4][$index][0]) ? (int)$intervalliMatches[4][$index][0] : 0;

        $inizio = ($oraInizio * 3600) + ($minutoInizio * 60);
        $fine = ($oraFine * 3600) + ($minutoFine * 60);

        // Turno notturno che passa mezzanotte.
        if ($fine < $inizio) {
            $fine += 24 * 3600;
        }

        return ($fine - $inizio) / 3600;
    }

    /**
     * Straordinari per singolo dipendente in calendario mensile
     */
    public function straordinariPerDipendente(Request $request)
    {
        $request->validate([
            'employee_id' => 'required',
            'mese' => 'required|date_format:Y-m',
        ]);

        $employee = \App\Models\HrEmployee::find($request->employee_id);
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Dipendente non trovato',
            ], 404);
        }

        $mese = $request->mese;
        $dataInizio = $mese . '-01';
        $dataFine = date('Y-m-t', strtotime($mese . '-01'));

        $matricolaPadded = str_pad($employee->matricola, 10, '0', STR_PAD_LEFT);

        // Query diretta su risultati per straordinari (causale LIKE 'ST%')
        $risultati = TeamSystemRisultati::where('matricola', $matricolaPadded)
            ->whereIn('causale', ['ST35','ST50','ST60'])
            ->where('azienda', '0000000249')
            ->where('data', '>=', $dataInizio)
            ->where('data', '<=', $dataFine)
            ->get();

        $straordinari = [];
        foreach ($risultati as $risultato) {
            $data = $risultato->data->format('Y-m-d');

            // ore è in secondi, arrotonda per difetto a multipli di 30 minuti
            $oreInSecondi = (float)$risultato->ore;
            $oreArrotondate = floor($oreInSecondi / 1800) * 1800;

            if ($oreArrotondate < 1800) {
                continue;
            }

            $oreInDecimali = $oreArrotondate / 3600;

            if (!isset($straordinari[$data])) {
                $straordinari[$data] = [
                    'data' => $data,
                    'ore' => 0,
                    'tipo' => 'Straordinario',
                    'causale' => $risultato->causale,
                ];
            }
            $straordinari[$data]['ore'] += $oreInDecimali;
        }

        ksort($straordinari);

        return response()->json([
            'success' => true,
            'data' => array_values($straordinari),
        ]);
    }
}
