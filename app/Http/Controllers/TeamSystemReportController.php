<?php

namespace App\Http\Controllers;

use App\Models\TeamSystemDipenTurnazioni;
use App\Models\TeamSystemGiustificazioni;
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
                        // Arrotonda per difetto a multipli di 30 minuti (1800 secondi).
                        $oreInSecondi = (float)$giustificazione->ore;
                        $oreArrotondate = floor($oreInSecondi / 1800) * 1800;

                        // Scarta giustificazioni inferiori a 30 minuti.
                        if ($oreArrotondate < 1800) {
                            continue;
                        }

                        $results[$group]['numero_giustificazioni']++;
                        $results[$group]['totali_ore'] += $oreArrotondate;
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
                            $results[$group]['ore_per_settimana'][$weekKey] += $oreArrotondate;
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
            
            // Se ci sono ore lavorate, confrontale con il turno del dipendente
            if ($oreLavorate > 0) {
                $matricolaNormalizzata = $giorno['matricola'];
                $matricolaPadded = str_pad($matricolaNormalizzata, 10, '0', STR_PAD_LEFT);
                $giornoSettimana = (int)$giorno['data']->format('w');

                // Recupera la turnazione del dipendente
                $dipenTurnazione = TeamSystemDipenTurnazioni::where('matricola', $matricolaPadded)
                    ->first();

                $oreTurno = 0;
                if ($dipenTurnazione && $dipenTurnazione->turnazioneRel) {
                    $oreTurno = $this->calcolaOreTurno($dipenTurnazione->turnazioneRel->descrizione, $giornoSettimana);
                }

                // Solo la differenza oltre il turno è straordinario; il resto è smart working / lavoro normale.
                $oreTurnoInSecondi = $oreTurno * 3600;
                if ($oreLavorate > $oreTurnoInSecondi) {
                    $straordinarioInSecondi = $oreLavorate - $oreTurnoInSecondi;
                    // Arrotonda per difetto a multipli di 30 minuti (1800 secondi).
                    $straordinarioInSecondi = floor($straordinarioInSecondi / 1800) * 1800;

                    // Se inferiore a 30 minuti, scarta (smart working / uscita leggermente ritardata).
                    if ($straordinarioInSecondi < 1800) {
                        continue;
                    }

                    // Trova il dipendente
                    $employee = \App\Models\HrEmployee::where('matricola', $matricolaNormalizzata)
                        ->orWhere('matricola', $matricolaPadded)
                        ->first();

                    if ($employee && $employee->centro_id) {
                        $centroCosto = \App\Models\HrCostCenter::find($employee->centro_id);
                        if ($centroCosto) {
                            $group = $this->getCostCenterGroup($centroCosto->centro_di_costo);
                            if (isset($results[$group])) {
                                $results[$group]['numero_giustificazioni']++;
                                $results[$group]['totali_ore'] += $straordinarioInSecondi;
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
                                $results[$group]['ore_per_settimana'][$weekKey] += $straordinarioInSecondi;
                            }
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

    /**
     * Espande una sequenza di giorni in un array di numeri (1=lunedì ... 6=sabato, 0=domenica).
     * Esempio: 'L-G' -> [1,2,3,4], 'V' -> [5]
     */
    private function espandiGiorniTurno($giornoInizio, $giornoFine)
    {
        $map = [
            'l' => 1,
            'g' => 4,
            'v' => 5,
            's' => 6,
            'd' => 0,
        ];

        $inizio = isset($map[$giornoInizio]) ? $map[$giornoInizio] : null;
        $fine = !empty($giornoFine) && isset($map[$giornoFine]) ? $map[$giornoFine] : $inizio;

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
        preg_match_all('/([lgvsd])(?:\s*-\s*([lgvsd]))?/i', $descrizioneLower, $giorniMatches, PREG_OFFSET_CAPTURE);

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

        // Calcola inizio e fine mese
        $mese = $request->mese;
        $dataInizio = $mese . '-01 00:00:00.000';
        $dataFine = date('Y-m-t', strtotime($mese . '-01')) . ' 23:59:59.000';

        // Recupera TUTTE le giustificazioni per il periodo (come in straordinariPerCentroDiCosto)
        $query = TeamSystemGiustificazioni::query();
        $query->where('inizio', '>=', $dataInizio);
        $query->where('fine', '<=', $dataFine);
        $query->where('causale', 'RSTR');
        $allGiustificazioni = $query->get();

        // Filtra solo quelle del dipendente
        $giustificazioni = [];
        foreach ($allGiustificazioni as $giustificazione) {
            $matricolaNormalizzata = ltrim($giustificazione->matricola, '0');

            // Cerca corrispondenza con matricola normalizzata o originale
            if ($matricolaNormalizzata == $employee->matricola || $giustificazione->matricola == $employee->matricola) {
                $giustificazioni[] = $giustificazione;
            }
        }

        // Recupera timbrature manuali per il dipendente
        $matricolaPadded = str_pad($employee->matricola, 10, '0', STR_PAD_LEFT);
        $timbrature = TeamSystemTimbrature::where('matricola', $matricolaPadded)
            ->where('flag', '*')
            ->whereNull('terminale')
            ->where('data', '>=', substr($dataInizio, 0, 10))
            ->where('data', '<=', substr($dataFine, 0, 10))
            ->get();

        // Raggruppa timbrature per giorno e calcola ore
        $timbraturePerGiorno = [];
        foreach ($timbrature as $timbro) {
            $key = $timbro->data->format('Y-m-d');
            if (!isset($timbraturePerGiorno[$key])) {
                $timbraturePerGiorno[$key] = [
                    'entrate' => [],
                    'uscite' => [],
                ];
            }
            if ($timbro->verso == 'E') {
                $timbraturePerGiorno[$key]['entrate'][] = $timbro->orario_in_seconds;
            } elseif ($timbro->verso == 'U') {
                $timbraturePerGiorno[$key]['uscite'][] = $timbro->orario_in_seconds;
            }
        }

        // Calcola ore per ogni giorno dalle timbrature
        $straordinariDaTimbrature = [];
        foreach ($timbraturePerGiorno as $data => $giorno) {
            $oreLavorate = 0;
            $entrate = $giorno['entrate'];
            $uscite = $giorno['uscite'];
            sort($entrate);
            sort($uscite);

            $minCount = min(count($entrate), count($uscite));
            for ($i = 0; $i < $minCount; $i++) {
                $diff = $uscite[$i] - $entrate[$i];
                $oreLavorate += $diff;
            }

            if ($oreLavorate > 0) {
                $oreInDecimali = $oreLavorate / 3600;

                // Recupera la turnazione del dipendente
                $dipenTurnazione = TeamSystemDipenTurnazioni::where('matricola', $matricolaPadded)
                    ->first();

                $oreTurno = 0;
                if ($dipenTurnazione && $dipenTurnazione->turnazioneRel) {
                    $giornoSettimana = (int)date('w', strtotime($data));
                    $oreTurno = $this->calcolaOreTurno($dipenTurnazione->turnazioneRel->descrizione, $giornoSettimana);
                }

                // Se le ore timbrate superano quelle previste dal turno, la differenza è straordinario.
                // Altrimenti è smart working / lavoro normale e viene scartato.
                if ($oreInDecimali > $oreTurno) {
                    $oreStraordinario = $oreInDecimali - $oreTurno;
                    // Arrotonda per difetto al multiplo di 30 minuti.
                    $oreStraordinario = floor($oreStraordinario * 2) / 2;

                    // Se inferiore a 30 minuti, non considerarlo straordinario.
                    if ($oreStraordinario >= 0.5) {
                        $straordinariDaTimbrature[$data] = [
                            'data' => $data,
                            'ore' => $oreStraordinario,
                            'tipo' => 'Straordinario manuale',
                            'causale' => 'TIMBRATURA',
                        ];
                    }
                }
            }
        }

        // Converti giustificazioni in formato calendario
        $straordinariDaGiustificazioni = [];
        foreach ($giustificazioni as $giustificazione) {
            $data = $giustificazione->inizio->format('Y-m-d');
            // Le ore sono in secondi: arrotonda per difetto a multipli di 30 minuti.
            $oreInSecondi = (float)$giustificazione->ore;
            $oreInSecondiArrotondate = floor($oreInSecondi / 1800) * 1800;

            // Scarta giustificazioni inferiori a 30 minuti.
            if ($oreInSecondiArrotondate < 1800) {
                continue;
            }

            $oreInDecimali = $oreInSecondiArrotondate / 3600;

            if (!isset($straordinariDaGiustificazioni[$data])) {
                $straordinariDaGiustificazioni[$data] = [
                    'data' => $data,
                    'ore' => 0,
                    'tipo' => 'Straordinario giustificato',
                    'causale' => $giustificazione->causale,
                ];
            }
            $straordinariDaGiustificazioni[$data]['ore'] += $oreInDecimali;
        }

        // Unisci i risultati
        $tuttiStraordinari = array_merge($straordinariDaGiustificazioni, $straordinariDaTimbrature);

        // Ordina per data
        ksort($tuttiStraordinari);

        return response()->json([
            'success' => true,
            'data' => array_values($tuttiStraordinari),
        ]);
    }
}
