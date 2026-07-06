<?php

namespace App\Http\Controllers;

use App\Models\TeamSystemGiustificazioni;
use App\Models\TeamSystemTimbrature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeamSystemReportController extends Controller
{
    /**
     * Determina il gruppo di un centro di costo in base al pattern nel nome
     */
    private function getCostCenterGroup($cdcName)
    {
        if (stripos($cdcName, 'OFC') !== false) {
            return 'BlueCollar OFC';
        }
        if (stripos($cdcName, 'CC') !== false) {
            return 'BlueCollar CC';
        }
        if (stripos($cdcName, 'Warehouse') !== false || stripos($cdcName, 'Werehouse') !== false) {
            return 'Warehouse CC';
        }
        if (stripos($cdcName, 'Quality') !== false) {
            return 'Quality';
        }
        if (stripos($cdcName, 'Logistics') !== false) {
            return 'Logistics';
        }
        if (stripos($cdcName, 'Maintenance') !== false) {
            return 'Maintenance';
        }
        return 'Office';
    }

    /**
     * Report straordinari per centro di costo
     */
    public function straordinariPerCentroDiCosto(Request $request)
    {

        /*$request->validate([
            'data_inizio' => 'nullable|date',
            'data_fine' => 'nullable|date|after_or_equal:data_inizio',
            'causali' => 'nullable|array',
            'causali.*' => 'string',
        ]);
*/
        $query = TeamSystemGiustificazioni::query();

        // Filtro per periodo
        if ($request->has('data_inizio') && !empty($request->data_inizio)) {
            $query->where('inizio', '>=', $request->data_inizio.' 00:00:00.000');
        }
        if ($request->has('data_fine') && !empty($request->data_fine)) {
            $query->where('fine', '<=', $request->data_fine.' 23:59:59.000');
        }

        // Filtro per causali (se non specificate, usa RSTR come default)
        $causali = $request->causali ?? ['RSTR'];
        // Assicurati che causali sia un array
        if (!is_array($causali)) {
            $causali = [$causali];
        }
        $query->whereIn('causale', $causali);

        // Recupera tutte le giustificazioni
        $giustificazioni = $query->get();

        // Recupera timbrature manuali (flag *) per straordinari non in giustificazioni
        $timbratureQuery = TeamSystemTimbrature::query();
        
        // Filtra solo per dipendenti presenti nel sistema HR
        $allEmployees = \App\Models\HrEmployee::all();
        $matricoleHr = [];
        foreach ($allEmployees as $employee) {
            $matricoleHr[] = str_pad($employee->matricola, 10, '0', STR_PAD_LEFT);
        }
        
        if (!empty($matricoleHr)) {
            $timbratureQuery->whereIn('matricola', $matricoleHr);
        }
        
        // Filtro per periodo
        if ($request->has('data_inizio') && !empty($request->data_inizio)) {
            $timbratureQuery->where('data', '>=', $request->data_inizio);
        }
        if ($request->has('data_fine') && !empty($request->data_fine)) {
            $timbratureQuery->where('data', '<=', $request->data_fine);
        }
        
        // Filtra solo timbrature manuali con flag * (straordinari inseriti manualmente)
        $timbratureQuery->where('flag', '*');
        $timbratureQuery->whereNull('terminale');

        $timbrature = $timbratureQuery->get();

        // Recupera tutti i centri di costo
        $allCostCenters = \App\Models\HrCostCenter::all();

        // Definisci i gruppi possibili
        $groups = ['BlueCollar OFC', 'BlueCollar CC', 'Quality', 'Maintenance', 'Logistics', 'Office', 'Warehouse CC'];

        // Raggruppa per gruppo di centro di costo
        $results = [];
        $trovati = 0;

        // Inizializza tutti i gruppi con valori a 0
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

        // Aggiorna i valori con le giustificazioni trovate
        foreach ($giustificazioni as $giustificazione) {
            // Normalizza la matricola (rimuovi zeri leading)
            $matricolaNormalizzata = ltrim($giustificazione->matricola, '0');

            // Trova il dipendente per matricola normalizzata o con zeri leading
            $employee = \App\Models\HrEmployee::where('matricola', $matricolaNormalizzata)
                ->orWhere('matricola', $giustificazione->matricola)
                ->first();

            if ($employee && $employee->centro_id) {
                $centroCosto = \App\Models\HrCostCenter::find($employee->centro_id);
                if ($centroCosto) {
                    // Determina il gruppo del centro di costo
                    $group = $this->getCostCenterGroup($centroCosto->centro_di_costo);
                    if (isset($results[$group])) {
                        $results[$group]['numero_giustificazioni']++;
                        $results[$group]['totali_ore'] += $giustificazione->ore;
                        if (!in_array($matricolaNormalizzata, $results[$group]['matricole'])) {
                            $results[$group]['matricole'][] = $matricolaNormalizzata;
                            $results[$group]['numero_dipendenti']++;
                        }

                        // Raggruppa per settimana (4 settimane del mese)
                        if ($giustificazione->inizio) {
                            $dayOfMonth = (int)$giustificazione->inizio->format('d');
                            $weekNumber = ceil($dayOfMonth / 7); // 1-4 in base al giorno del mese
                            $weekKey = 'Settimana ' . $weekNumber;

                            if (!isset($results[$group]['ore_per_settimana'][$weekKey])) {
                                $results[$group]['ore_per_settimana'][$weekKey] = 0;
                            }
                            $results[$group]['ore_per_settimana'][$weekKey] += $giustificazione->ore;
                        }

                        $trovati++;
                    }
                }
            }
        }

        // Aggiungi ore dalle timbrature manuali (straordinari non in giustificazioni)
        // Raggruppa per matricola e data per calcolare le ore per ogni giorno
        $timbraturePerGiorno = [];
        
        foreach ($timbrature as $timbro) {
            // Normalizza la matricola - rimuovi tutti gli zeri leading
            $matricolaNormalizzata = ltrim($timbro->matricola, '0');
            $key = $matricolaNormalizzata . '_' . $timbro->data->format('Y-m-d');
            
            if (!isset($timbraturePerGiorno[$key])) {
                $timbraturePerGiorno[$key] = [
                    'matricola' => $matricolaNormalizzata,
                    'data' => $timbro->data,
                    'entrate' => [],
                    'uscite' => []
                ];
            }
            
            if ($timbro->verso == 'E') {
                $timbraturePerGiorno[$key]['entrate'][] = $timbro->orario_in_seconds;
            } elseif ($timbro->verso == 'U') {
                $timbraturePerGiorno[$key]['uscite'][] = $timbro->orario_in_seconds;
            }
        }
        
        // Calcola ore lavorate per ogni giorno dalle timbrature
        foreach ($timbraturePerGiorno as $giorno) {
            $oreLavorate = 0;
            
            // Calcola ore lavorate sommando le differenze tra entrate e uscite
            $entrate = $giorno['entrate'];
            $uscite = $giorno['uscite'];
            
            // Ordina entrate e uscite
            sort($entrate);
            sort($uscite);
            
            // Calcola ore per ogni coppia entrata-uscita
            $minCount = min(count($entrate), count($uscite));
            for ($i = 0; $i < $minCount; $i++) {
                $diff = $uscite[$i] - $entrate[$i];
                $oreLavorate += $diff;
            }
            
            // Se ci sono ore lavorate, aggiungi ai risultati
            if ($oreLavorate > 0) {
                $matricolaNormalizzata = $giorno['matricola'];
                
                // Trova il dipendente
                $employee = \App\Models\HrEmployee::where('matricola', $matricolaNormalizzata)
                    ->orWhere('matricola', str_pad($matricolaNormalizzata, 10, '0', STR_PAD_LEFT))
                    ->first();
                
                if ($employee && $employee->centro_id) {
                    $centroCosto = \App\Models\HrCostCenter::find($employee->centro_id);
                    if ($centroCosto) {
                        $group = $this->getCostCenterGroup($centroCosto->centro_di_costo);
                        if (isset($results[$group])) {
                            $results[$group]['numero_giustificazioni']++;
                            $results[$group]['totali_ore'] += $oreLavorate;
                            if (!in_array($matricolaNormalizzata, $results[$group]['matricole'])) {
                                $results[$group]['matricole'][] = $matricolaNormalizzata;
                                $results[$group]['numero_dipendenti']++;
                            }
                            
                            // Raggruppa per settimana
                            $dayOfMonth = (int)$giorno['data']->format('d');
                            $weekNumber = ceil($dayOfMonth / 7);
                            $weekKey = 'Settimana ' . $weekNumber;
                            
                            if (!isset($results[$group]['ore_per_settimana'][$weekKey])) {
                                $results[$group]['ore_per_settimana'][$weekKey] = 0;
                            }
                            $results[$group]['ore_per_settimana'][$weekKey] += $oreLavorate;
                        }
                    }
                }
            }
        }

        // Converti in array e ordina per totali_ore
        $results = array_values($results);
        usort($results, function($a, $b) {
            return $b['totali_ore'] <=> $a['totali_ore'];
        });

        // Rimuovi il campo matricole dal risultato e converti le settimane
        foreach ($results as &$result) {
            unset($result['matricole']);

            // Converti le settimane in array ordinato
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
     * Dettaglio giustificazioni per centro di costo
     */
    public function dettaglioGiustificazioni(Request $request)
    {
        $request->validate([
            'cdc' => 'required|string',
            'data_inizio' => 'nullable|date',
            'data_fine' => 'nullable|date|after_or_equal:data_inizio',
            'causali' => 'nullable|array',
            'causali.*' => 'string',
        ]);

        // Trova il centro di costo
        $centroCosto = \App\Models\HrCostCenter::where('centro_di_costo', $request->cdc)->first();
        if (!$centroCosto) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        // Trova tutti i dipendenti di questo centro di costo
        $employees = \App\Models\HrEmployee::where('centro_id', $centroCosto->id)->get();
        $matricole = [];
        foreach ($employees as $employee) {
            // Aggiungi matricola con zeri leading per TeamSystem
            $matricole[] = str_pad($employee->matricola, 10, '0', STR_PAD_LEFT);
        }

        if (empty($matricole)) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $query = TeamSystemGiustificazioni::query();

        // Filtra per matricole dei dipendenti del centro di costo
        $query->whereIn('matricola', $matricole);

        // Filtro per periodo
        if ($request->has('data_inizio') && !empty($request->data_inizio)) {
            $query->where('inizio', '>=', $request->data_inizio.' 00:00:00.000');
        }
        if ($request->has('data_fine') && !empty($request->data_fine)) {
            $query->where('fine', '<=', $request->data_fine.' 00:00:00.000');
        }

        // Filtro per causali
        if ($request->has('causali')) {
            $causali = $request->causali;
            // Assicurati che causali sia un array
            if (!is_array($causali)) {
                $causali = [$causali];
            }
            $query->whereIn('causale', $causali);
        }

        $results = $query->orderBy('inizio', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
