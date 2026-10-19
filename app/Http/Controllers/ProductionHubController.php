<?php

namespace App\Http\Controllers;

use App\Models\DdtSpedizione;
use App\Models\FiShippedRow;
use App\Models\PrMovement;
use App\Models\QtCheckerReport;
use App\Models\QtConformita;
use App\Models\QtCprTest;
use App\Models\QtFai;
use App\Models\QtTypeTest;
use App\Models\WfDocument;
use App\Models\WfOrder;
use App\Services\GoogleDrive;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionHubController extends Controller
{
    /**
     * Normalizza l'input del codice per le varie query.
     */
    private function cleanCode(string $code): string
    {
        return trim($code);
    }

    /**
     * Converte in modo sicuro caratteri Windows-1252 o stringhe binarie in UTF-8
     */
    private function utf8ize($data)
    {
        if ($data instanceof \Illuminate\Support\Enumerable) {
            $data = $data->all();
        }
        if (is_array($data)) {
            return array_map([$this, 'utf8ize'], $data);
        }
        if ($data instanceof \stdClass) {
            return (object) array_map([$this, 'utf8ize'], (array) $data);
        }
        if (is_object($data)) {
            if (method_exists($data, 'toArray')) {
                return $this->utf8ize($data->toArray());
            }
            return (object) array_map([$this, 'utf8ize'], get_object_vars($data));
        }
        if (is_string($data)) {
            return mb_convert_encoding($data, 'UTF-8', 'Windows-1252, UTF-8');
        }
        return $data;
    }

    /**
     * Autocomplete / suggerimenti per la ricerca rapida per OL o Commessa.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = $this->cleanCode((string) $request->get('q', ''));
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = collect();

        // 1. Cerca in AGG_MASTER_TMP (GP) per cdOrdine, Commessa o cdProdotto
        try {
            $gpOrders = DB::connection('sqlsrv_gp')
                ->table('AGG_MASTER_TMP')
                ->select('cdOrdine', 'Commessa', 'cdProdotto', 'QtaOrdinata', 'cdUM', 'dataInserimento')
                ->where('cdOrdine', 'LIKE', '%' . $query . '%')
                ->orWhere('Commessa', 'LIKE', '%' . $query . '%')
                ->orWhere('cdProdotto', 'LIKE', '%' . $query . '%')
                ->limit(20)
                ->get();

            foreach ($gpOrders as $o) {
                $cdOrd = trim($o->cdOrdine ?? '');
                $comm = trim($o->Commessa ?? '');

                // Risultato per OL
                if (!empty($cdOrd) && (stripos($cdOrd, $query) !== false || empty($comm))) {
                    if (!$results->contains('code', $cdOrd)) {
                        $results->push([
                            'code' => $cdOrd,
                            'type' => 'OL',
                            'commessa' => $comm ?: null,
                            'product' => trim($o->cdProdotto ?? ''),
                            'quantity' => $o->QtaOrdinata ?? null,
                            'um' => trim($o->cdUM ?? ''),
                            'date' => $o->dataInserimento ?? null,
                        ]);
                    }
                }

                // Risultato per Commessa
                if (!empty($comm) && stripos($comm, $query) !== false) {
                    if (!$results->contains('code', $comm)) {
                        $results->push([
                            'code' => $comm,
                            'type' => 'Commessa',
                            'ol' => $cdOrd ?: null,
                            'product' => trim($o->cdProdotto ?? ''),
                            'quantity' => $o->QtaOrdinata ?? null,
                            'um' => trim($o->cdUM ?? ''),
                            'date' => $o->dataInserimento ?? null,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ProductionHubController@autocomplete sqlsrv_gp error: ' . $e->getMessage());
        }

        // 2. Cerca in WfOrder (Commesse)
        try {
            $wfOrders = WfOrder::select('commessa', 'commessa_sistema', 'revisione', 'stato')
                ->where('commessa', 'LIKE', '%' . $query . '%')
                ->orWhere('commessa_sistema', 'LIKE', '%' . $query . '%')
                ->limit(10)
                ->get();

            foreach ($wfOrders as $wf) {
                $code = trim($wf->commessa);
                if (!$results->contains('code', $code)) {
                    $results->push([
                        'code' => $code,
                        'type' => 'Commessa WF',
                        'product' => $wf->commessa_sistema ?? '',
                        'quantity' => null,
                        'um' => '',
                        'date' => null,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ProductionHubController@autocomplete WfOrder error: ' . $e->getMessage());
        }

        return response()->json($this->utf8ize($results->take(15)->values()));
    }

    /**
     * Dashboard riassuntiva del fascicolo 4.0 per OL o Commessa.
     */
    public function summary(Request $request): JsonResponse
    {
        $code = $this->cleanCode((string) $request->get('code', ''));
        if (empty($code)) {
            return response()->json([
                'found' => false,
                'message' => 'Inserisci un codice OL o Commessa valido.'
            ], 400);
        }

        // 1. Risolvi OL e Commessa in modo bidirezionale
        $resolved = $this->resolveCodes($code);
        $orderInfo = $this->resolveOrderInfo($code);

        // 2. Conteggio e totali strisciate
        $strisciateSummary = $this->getStrisciateSummary($resolved);

        // 3. Conteggio fabbisogni
        $fabbisogniCount = $this->getFabbisogniCount($resolved);

        // 4. Qualità & Non Conformità
        $qualitaSummary = $this->getQualitaSummary($resolved);

        // 5. Documenti caricati
        $documentiCount = $this->getDocumentiCount($resolved, $orderInfo['wf_order_id'] ?? null);

        // 6. Spedizioni
        $spedizioniSummary = $this->getSpedizioniSummary($resolved, $orderInfo['wf_order_id'] ?? null);

        return response()->json($this->utf8ize([
            'found' => true,
            'code' => $code,
            'order_info' => $orderInfo,
            'kpi' => [
                'strisciate_count' => $strisciateSummary['count'],
                'quantita_prodotta_totale' => $strisciateSummary['quantita_totale'],
                'fabbisogni_count' => $fabbisogniCount,
                'nc_totali' => $qualitaSummary['nc_count'],
                'nc_aperte' => $qualitaSummary['nc_aperte'],
                'prove_qualita_count' => $qualitaSummary['prove_count'],
                'documenti_count' => $documentiCount,
                'spedizioni_count' => $spedizioniSummary['count'],
                'quantita_spedita_totale' => $spedizioniSummary['quantita_totale'],
            ],
        ]));
    }

    /**
     * Tab Strisciate: Dettaglio avanzamenti di produzione da GP
     */
    public function strisciate(Request $request): JsonResponse
    {
        $code = $this->cleanCode((string) $request->get('code', ''));
        if (empty($code)) {
            return response()->json([]);
        }

        $resolved = $this->resolveCodes($code);
        $searchCodes = $resolved['all_codes'];
        $records = collect();

        // 1. Da Produzione (sqlsrv_root_gp)
        try {
            $rows = DB::connection('sqlsrv_root_gp')
                ->table('Produzione as PRD')
                ->select(
                    'PRD.IDProduzione',
                    'PRD.IDRis as IDRisorsa',
                    'PRD.DataOraInizio',
                    'PRD.DataOraFine',
                    'O.NumeroOrdineAcquisto',
                    'DM.NRigaOrd',
                    'R.Modello as Macchina',
                    'P.NomeProdotto',
                    'P.DescrizioneProdotto',
                    'P.Conversione12',
                    'UM.UM',
                    'PRD.#Cicli as Quantita',
                    'PRD.Confermato',
                    'PRD.Significativo',
                    'PRD.Testo1 as Bobbina'
                )
                ->join('Dettagli_sugli_ordini as DSO', 'DSO.IDDettagliOrdini', '=', 'PRD.IDDettOrd')
                ->join('Dettagli_Master as DM', 'DM.idMaster', '=', 'DSO.IDMaster')
                ->join('Ordini as O', 'O.IDOrdine', '=', 'DM.IDOrdine')
                ->join('Prodotti as P', 'P.IDProdotto', '=', 'PRD.IDArticolo')
                ->join('UM', 'UM.IDUM', '=', 'DSO.IDUM')
                ->join('Risorse as R', 'R.IDRisorsa', '=', 'PRD.IDRis')
                ->where(function ($q) use ($searchCodes) {
                    foreach ($searchCodes as $c) {
                        $q->orWhere('O.NumeroOrdineAcquisto', 'LIKE', '%' . $c . '%')
                          ->orWhere('DM.NRigaOrd', 'LIKE', '%' . $c . '%')
                          ->orWhere('DM.NRigaOrd', 'LIKE', '%0000' . $c . '%');
                    }
                })
                ->orderBy('PRD.DataOraInizio', 'asc')
                ->get();

            // Macchine con report diametri (CSV Google Drive)
            $telemetriaMachines = DB::table('machineries')
                ->where('report_gp', true)
                ->get(['nome', 'name_gp'])
                ->keyBy(fn ($m) => (string) $m->name_gp);

            // Macchine 4.0: presenti in STL_Info_Ordine_V (id_gp/name_gp = MacchinaId)
            $machines40 = DB::table('machineries')
                ->where(function ($q) {
                    $q->whereNotNull('id_gp')->orWhereNotNull('name_gp');
                })
                ->get(['nome', 'name_gp', 'id_gp']);
            $machines40ById = $machines40->filter(fn ($m) => !empty($m->id_gp))
                ->keyBy(fn ($m) => (string) $m->id_gp);
            $machines40ByGp = $machines40->filter(fn ($m) => !empty($m->name_gp))
                ->keyBy(fn ($m) => (string) $m->name_gp);

            foreach ($rows as $row) {
                $olProd = trim($row->NumeroOrdineAcquisto ?? '');
                if ($olProd === '') {
                    $olProd = preg_replace('/^0+(?=\d)/', '', trim($row->NRigaOrd ?? ''));
                }

                $idRis = trim((string) ($row->IDRisorsa ?? ''));
                $machObj = $telemetriaMachines->get($idRis);
                $mach40 = $machines40ById->get($idRis) ?? $machines40ByGp->get($idRis);
                $hasTelemetria = $machObj !== null || $mach40 !== null;
                $macchinaTelemetria = $machObj->nome ?? ($mach40->nome ?? null);

                $records->push([
                    'id' => $row->IDProduzione,
                    'id_risorsa' => $idRis,
                    'has_telemetria' => $hasTelemetria,
                    'has_diametri' => $machObj !== null,
                    'has_processo_40' => $mach40 !== null,
                    'macchina_telemetria' => $macchinaTelemetria,
                    'data_inizio' => $row->DataOraInizio,
                    'data_fine' => $row->DataOraFine,
                    'ol_produzione' => $olProd !== '' ? $olProd : '-',
                    'is_ricercato' => $olProd !== '' && $olProd === $code,
                    'ordine' => trim($row->NumeroOrdineAcquisto ?? ''),
                    'riga_ord' => trim($row->NRigaOrd ?? ''),
                    'macchina' => trim($row->Macchina ?? ''),
                    'prodotto' => trim($row->NomeProdotto ?? ''),
                    'descrizione' => trim($row->DescrizioneProdotto ?? ''),
                    'fibre' => $row->Conversione12,
                    'um' => trim($row->UM ?? ''),
                    'quantita' => round((float) ($row->Quantita ?? 0), 2),
                    'confermato' => (bool) $row->Confermato,
                    'significativo' => (bool) $row->Significativo,
                    'bobbina' => trim($row->Bobbina ?? ''),
                    'fonte' => 'GP Produzione',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('ProductionHubController@strisciate sqlsrv_root_gp: ' . $e->getMessage());
        }

        // 2. Se vuoto o per completare, controlla AGG_EXP_PRODUZIONE_TMP (sqlsrv_gp)
        if ($records->isEmpty()) {
            try {
                $rowsAgg = DB::connection('sqlsrv_gp')
                    ->table('AGG_EXP_PRODUZIONE_TMP')
                    ->where(function ($q) use ($searchCodes) {
                        foreach ($searchCodes as $c) {
                            $q->orWhere('Ordine', 'LIKE', '%' . $c . '%');
                        }
                    })
                    ->orderBy('DataInizio', 'asc')
                    ->get();

                foreach ($rowsAgg as $row) {
                    $olAgg = trim($row->Ordine ?? '');
                    $records->push([
                        'id' => $row->IDProduzione ?? null,
                        'id_risorsa' => null,
                        'has_telemetria' => false,
                        'macchina_telemetria' => null,
                        'data_inizio' => $row->DataInizio ?? null,
                        'data_fine' => $row->DataFine ?? null,
                        'ol_produzione' => $olAgg !== '' ? $olAgg : '-',
                        'is_ricercato' => $olAgg !== '' && $olAgg === $code,
                        'ordine' => $olAgg,
                        'riga_ord' => '',
                        'macchina' => trim($row->Macchina ?? $row->cdRisorsa ?? ''),
                        'prodotto' => trim($row->cdProdotto ?? ''),
                        'descrizione' => '',
                        'fibre' => $row->NumeroFibre ?? null,
                        'um' => trim($row->cdUM ?? ''),
                        'quantita' => round((float) ($row->Qta ?? 0), 2),
                        'confermato' => true,
                        'significativo' => true,
                        'fonte' => 'Interscambio GP',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('ProductionHubController@strisciate sqlsrv_gp: ' . $e->getMessage());
            }
        }

        // 3. Collega gli scarti di produzione: AGG_EXP_PRODUZIONE_TMP.LottoOUT -> pr_movements.lotto
        //    I movimenti di scarto SAP hanno tipo_movimento che inizia con '5' (es. 551, 552, 555)
        try {
            $idsProd = $records->pluck('id')
                ->filter(fn ($v) => $v !== null && $v !== '')
                ->map(fn ($v) => (string) $v)
                ->unique()
                ->values()
                ->all();

            $olsScarto = $records->pluck('ol_produzione')
                ->merge($records->pluck('ordine'))
                ->map(fn ($v) => ltrim(trim((string) $v), '0'))
                ->filter(fn ($v) => $v !== '' && $v !== '-' && $v !== '0')
                ->unique()
                ->values()
                ->all();

            $lottoById = collect();
            $lottoByOrdine = collect();

            if (!empty($idsProd) || !empty($olsScarto)) {
                $rowsLotto = DB::connection('sqlsrv_gp')
                    ->table('AGG_EXP_PRODUZIONE_TMP')
                    ->where(function ($q) use ($idsProd, $olsScarto) {
                        if (!empty($idsProd)) {
                            $q->whereIn('IDProduzione', $idsProd);
                        }
                        foreach ($olsScarto as $ol) {
                            $q->orWhere('Ordine', 'LIKE', '%' . $ol);
                        }
                    })
                    ->get(['IDProduzione', 'Ordine', 'LottoOUT']);

                $rowsLotto = $rowsLotto->filter(fn ($r) => trim((string) ($r->LottoOUT ?? '')) !== '');
                $lottoById = $rowsLotto
                    ->mapWithKeys(fn ($r) => [(string) $r->IDProduzione => trim((string) $r->LottoOUT)]);
                $lottoByOrdine = $rowsLotto
                    ->mapWithKeys(fn ($r) => [ltrim(trim((string) ($r->Ordine ?? '')), '0') => trim((string) $r->LottoOUT)]);
            }

            $lotti = $lottoById->merge($lottoByOrdine)->unique()->values()->all();

            $movimentiByLotto = collect();
            if (!empty($lotti)) {
                $movimentiByLotto = PrMovement::whereIn('lotto', $lotti)
                    ->where('tipo_movimento', 'LIKE', '5%')
                    ->orderBy('data_pubblicazione')
                    ->get([
                        'id', 'materiale', 'descrizione', 'quantita', 'importo', 'um',
                        'tipo_movimento', 'documento_materiale', 'data_pubblicazione',
                        'lotto', 'testo_movimento', 'user',
                    ])
                    ->groupBy('lotto');
            }

            $records->transform(function ($rec) use ($lottoById, $lottoByOrdine, $movimentiByLotto) {
                $recId = ($rec['id'] ?? null) !== null ? (string) $rec['id'] : null;
                $olNorm = ltrim(trim((string) ($rec['ordine'] ?? '')), '0');
                if ($olNorm === '' || $olNorm === '0') {
                    $olNorm = ltrim(trim((string) ($rec['ol_produzione'] ?? '')), '0');
                }

                $lottoOut = ($recId !== null ? $lottoById->get($recId) : null)
                    ?? ($olNorm !== '' ? $lottoByOrdine->get($olNorm) : null);

                $movs = $lottoOut ? ($movimentiByLotto->get($lottoOut) ?? collect()) : collect();

                $rec['lotto_out'] = $lottoOut;
                $rec['scarti_count'] = $movs->count();
                $rec['scarto_qta'] = round($movs->sum(fn ($m) => abs((float) $m->quantita)), 3);
                $rec['scarto_valore'] = round($movs->sum(fn ($m) => abs((float) $m->importo)), 2);
                $rec['scarti'] = $movs->map(fn ($m) => [
                    'id' => $m->id,
                    'materiale' => $m->materiale,
                    'descrizione' => $m->descrizione,
                    'quantita' => (float) $m->quantita,
                    'importo' => (float) $m->importo,
                    'um' => $m->um,
                    'tipo_movimento' => $m->tipo_movimento,
                    'documento_materiale' => $m->documento_materiale,
                    'data_pubblicazione' => $m->data_pubblicazione,
                    'testo_movimento' => $m->testo_movimento,
                    'user' => $m->user,
                ])->values()->all();

                return $rec;
            });
        } catch (\Throwable $e) {
            Log::warning('ProductionHubController@strisciate scarti: ' . $e->getMessage());
        }

        // 4. Calcola metriche di efficienza e fermi aggregati per l'intero lotto / ordine
        //    - Tempo di lavoro (somma durate strisciate)
        //    - Fermi macchina sovrapposti (ACD_Fermi_T da sqlsrv_root_gp)
        //    - Disponibilità = TempoLavoro / (TempoLavoro + Fermi)
        //    - Resa Qualitativa (Yield) = QtaBuona / (QtaBuona + Scarto)
        //    - OEE semplificato = Disponibilità * ResaQualitativa (Performance normalizzata al 100% in assenza di target orario)
        $kpiEfficienza = [
            'tempo_lavoro_minuti' => 0,
            'tempo_fermo_minuti' => 0,
            'numero_fermi' => 0,
            'disponibilita_perc' => null,
            'resa_qualita_perc' => null,
            'oee_perc' => null,
            'quantita_buona' => 0,
            'scarto_totale_qta' => 0,
            'scarto_totale_valore' => 0,
            'fermi_principali' => [],
        ];

        try {
            $tempoLavoroMin = 0;
            $qtaBuona = 0;
            $scartoTotQta = 0;
            $scartoTotValore = 0;

            // Raccogli intervalli temporali e risorsa per la query fermi
            $intervalliPerRisorsa = [];

            foreach ($records as $r) {
                $qtaBuona += (float) ($r['quantita'] ?? 0);
                $scartoTotQta += (float) ($r['scarto_qta'] ?? 0);
                $scartoTotValore += (float) ($r['scarto_valore'] ?? 0);

                $dInizio = $r['data_inizio'] ?? null;
                $dFine = $r['data_fine'] ?? null;
                if ($dInizio && $dFine) {
                    $tsI = strtotime((string) $dInizio);
                    $tsF = strtotime((string) $dFine);
                    if ($tsF > $tsI) {
                        $durMin = (int) round(($tsF - $tsI) / 60);
                        $tempoLavoroMin += $durMin;

                        $idRis = trim((string) ($r['id_risorsa'] ?? ''));
                        if ($idRis !== '') {
                            $intervalliPerRisorsa[$idRis][] = [
                                'inizio_str' => date('Y-m-d H:i:s', $tsI),
                                'fine_str' => date('Y-m-d H:i:s', $tsF),
                                'ts_inizio' => $tsI,
                                'ts_fine' => $tsF,
                            ];
                        }
                    }
                }
            }

            // Calcolo fermi macchina da ACD_Fermi_T per ogni risorsa coinvolta
            $totaleFermiMin = 0;
            $fermiPerCausale = [];
            $numeroFermi = 0;

            foreach ($intervalliPerRisorsa as $idRis => $intervalli) {
                // Min inizio e max fine per la query
                $minInizio = min(array_column($intervalli, 'inizio_str'));
                $maxFine = max(array_column($intervalli, 'fine_str'));

                $fermiRows = DB::connection('sqlsrv_root_gp')
                    ->table('ACD_Fermi_T as F')
                    ->leftJoin('Acd_Produzione_T as P', 'F.AF_FK_AcdProduzione', '=', 'P.AP_ID')
                    ->leftJoin('Causali_Fermo as CF', 'F.AF_FK_CausaleFermo', '=', 'CF.IDCausaleFermo')
                    ->where('P.AP_FK_IDRisorsa', $idRis)
                    ->where('F.AF_DataOraInizio', '<=', $maxFine)
                    ->where(function ($q) use ($minInizio) {
                        $q->whereNull('F.AF_DataOraFine')
                          ->orWhere('F.AF_DataOraFine', '>=', $minInizio);
                    })
                    ->select(
                        'F.AF_DataOraInizio',
                        'F.AF_DataOraFine',
                        'CF.DescrizioneFermo'
                    )
                    ->get();

                foreach ($fermiRows as $f) {
                    $fInizioTs = $f->AF_DataOraInizio ? strtotime((string) $f->AF_DataOraInizio) : null;
                    $fFineTs = $f->AF_DataOraFine ? strtotime((string) $f->AF_DataOraFine) : time();
                    if (!$fInizioTs) {
                        continue;
                    }

                    // Verifica sovrapposizione con almeno un intervallo di lavoro di questa risorsa
                    foreach ($intervalli as $inv) {
                        $startTs = max($fInizioTs, $inv['ts_inizio']);
                        $endTs = min($fFineTs, $inv['ts_fine']);
                        if ($endTs > $startTs) {
                            $dur = (int) round(($endTs - $startTs) / 60);
                            $totaleFermiMin += $dur;
                            $numeroFermi++;
                            $causale = trim((string) ($f->DescrizioneFermo ?? 'Non specificato'));
                            $fermiPerCausale[$causale] = ($fermiPerCausale[$causale] ?? 0) + $dur;
                            break; // evito doppio conteggio se gli intervalli si toccano
                        }
                    }
                }
            }

            // Calcolo percentuali
            $tempoTotale = $tempoLavoroMin + $totaleFermiMin;
            $disponibilita = $tempoTotale > 0 ? round(($tempoLavoroMin / $tempoTotale) * 100, 1) : null;

            $totalePezzi = $qtaBuona + $scartoTotQta;
            $resaQualita = $totalePezzi > 0 ? round(($qtaBuona / $totalePezzi) * 100, 1) : 100.0;

            // OEE semplificato: Disponibilità * Qualità (con fattori in [0, 1])
            $oee = null;
            if ($disponibilita !== null && $resaQualita !== null) {
                $oee = round(($disponibilita * $resaQualita) / 100, 1);
            }

            arsort($fermiPerCausale);
            $fermiPrincipali = [];
            foreach (array_slice($fermiPerCausale, 0, 5, true) as $c => $min) {
                $fermiPrincipali[] = ['causale' => $c, 'minuti' => $min];
            }

            $kpiEfficienza = [
                'tempo_lavoro_minuti' => $tempoLavoroMin,
                'tempo_fermo_minuti' => $totaleFermiMin,
                'numero_fermi' => $numeroFermi,
                'disponibilita_perc' => $disponibilita,
                'resa_qualita_perc' => $resaQualita,
                'oee_perc' => $oee,
                'quantita_buona' => round($qtaBuona, 2),
                'scarto_totale_qta' => round($scartoTotQta, 3),
                'scarto_totale_valore' => round($scartoTotValore, 2),
                'fermi_principali' => $fermiPrincipali,
            ];
        } catch (\Throwable $e) {
            Log::warning('ProductionHubController@strisciate kpi efficienza: ' . $e->getMessage());
        }

        return response()->json($this->utf8ize([
            'data' => $records->values()->all(),
            'structure' => $resolved['structure'] ?? [],
            'kpi' => $kpiEfficienza,
        ]));
    }

    /**
     * Tab Fabbisogni: Distinta componenti e materiali necessari
     */
    public function fabbisogni(Request $request): JsonResponse
    {
        $code = $this->cleanCode((string) $request->get('code', ''));
        if (empty($code)) {
            return response()->json([]);
        }

        $resolved = $this->resolveCodes($code);
        $searchCodes = $resolved['all_codes'];
        $records = collect();

        // 1. Prova AGG_EXP_PRODUZIONE_FABB_TMP (sqlsrv_gp)
        try {
            $rows = DB::connection('sqlsrv_gp')
                ->table('AGG_EXP_PRODUZIONE_FABB_TMP')
                ->where(function ($q) use ($searchCodes) {
                    foreach ($searchCodes as $c) {
                        $q->orWhere('Ordine', 'LIKE', '%' . $c . '%');
                    }
                })
                ->orderBy('DataEsportazione', 'desc')
                ->get();

            foreach ($rows as $row) {
                $records->push([
                    'id' => $row->ID ?? $row->IDProduzione ?? null,
                    'ordine' => trim($row->Ordine ?? ''),
                    'prodotto' => trim($row->cdProdotto ?? ''),
                    'descrizione' => trim($row->dsProdotto ?? ''),
                    'quantita' => (float) ($row->Qta ?? 0),
                    'um' => trim($row->cdUM ?? ''),
                    'fibre' => $row->NumeroFibre ?? null,
                    'data_esportazione' => $row->DataEsportazione ?? null,
                    'esportato' => $row->Esportato ?? null,
                    'avanzamento' => $row->Avanzamento ?? null,
                    'fonte' => 'Avanzamento Fabbisogni GP',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('ProductionHubController@fabbisogni AGG_EXP_PRODUZIONE_FABB_TMP: ' . $e->getMessage());
        }

        // 2. Se vuoto, controlla AGG_FABBISOGNI_TMP
        if ($records->isEmpty()) {
            try {
                $rowsFabb = DB::connection('sqlsrv_gp')
                    ->table('AGG_FABBISOGNI_TMP')
                    ->where(function ($q) use ($searchCodes) {
                        foreach ($searchCodes as $c) {
                            $q->orWhere('cdOrdine', 'LIKE', '%' . $c . '%');
                        }
                    })
                    ->get();

                foreach ($rowsFabb as $row) {
                    $records->push([
                        'id' => null,
                        'ordine' => trim($row->cdOrdine ?? ''),
                        'prodotto' => trim($row->cdProdotto ?? ''),
                        'descrizione' => trim($row->dsProdotto ?? ''),
                        'quantita' => (float) ($row->QtaOrdinata ?? $row->Qta ?? 0),
                        'um' => trim($row->cdUM ?? ''),
                        'fibre' => null,
                        'data_esportazione' => null,
                        'esportato' => null,
                        'avanzamento' => null,
                        'fonte' => 'Fabbisogni Ordine',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('ProductionHubController@fabbisogni AGG_FABBISOGNI_TMP: ' . $e->getMessage());
            }
        }

        return response()->json($this->utf8ize($records->values()->all()));
    }

    /**
     * Tab Qualità: Non Conformità e Prove di Collaudo
     */
    public function qualita(Request $request): JsonResponse
    {
        $code = $this->cleanCode((string) $request->get('code', ''));
        if (empty($code)) {
            return response()->json([
                'non_conformita' => [],
                'checker_reports' => [],
                'cpr_tests' => [],
                'type_tests' => [],
                'fai_tests' => [],
            ]);
        }

        $resolved = $this->resolveCodes($code);
        $searchCodes = $resolved['all_codes'];

        // Mappa OL -> prodotto ricavata dalla struttura di produzione (AGG_MASTER_TMP)
        $prodottiPerOl = collect($resolved['structure'] ?? [])
            ->mapWithKeys(fn ($s) => [
                preg_replace('/^0+(?=\d)/', '', trim((string) ($s['ol'] ?? ''))) => trim((string) ($s['prodotto'] ?? '')),
            ]);

        $prodottoPerOl = function ($ol) use ($prodottiPerOl) {
            $key = preg_replace('/^0+(?=\d)/', '', trim((string) $ol));

            return $prodottiPerOl->get($key, '');
        };

        // Non Conformità
        try {
            $ncList = QtConformita::with(['macchinary', 'defect', 'fiberTipe'])
                ->where(function ($q) use ($searchCodes) {
                    foreach ($searchCodes as $c) {
                        $q->orWhere('ol', 'LIKE', '%' . $c . '%');
                    }
                })
                ->orderBy('data_apertura', 'desc')
                ->get()
                ->map(function ($nc) use ($prodottoPerOl) {
                    return [
                        'id' => $nc->id,
                        'numero' => $nc->numero,
                        'anno' => $nc->anno,
                        'ol' => $nc->ol,
                        'materiale' => $nc->materiale,
                        'prodotto' => $prodottoPerOl($nc->ol),
                        'bobina' => $nc->bobina,
                        'macchina' => $nc->macchinary->nome ?? $nc->macchina,
                        'difetto' => $nc->defect->difetto ?? $nc->difetto,
                        'tipologia_fibra' => $nc->fiberTipe->nome ?? $nc->tipologia_fibra,
                        'stato' => $nc->stato,
                        'stato_label' => $nc->stato_label(),
                        'soluzione' => $nc->soluzione,
                        'data_apertura' => $nc->data_apertura,
                        'data_chiusura' => $nc->data_chiusura,
                        'motivazione_chiusura' => $nc->motivazione_chiusura_text,
                        'note' => $nc->note,
                        'google_drive_id' => $nc->google_drive_id,
                    ];
                });
        } catch (\Throwable $e) {
            Log::warning('ProductionHubController@qualita QtConformita error: ' . $e->getMessage());
            $ncList = collect();
        }

        // Checker Reports
        try {
            $checkerList = QtCheckerReport::where(function ($q) use ($searchCodes) {
                    foreach ($searchCodes as $c) {
                        $q->orWhere('ol', 'LIKE', '%' . $c . '%');
                    }
                })
                ->orderBy('date_create', 'desc')
                ->get()
                ->map(function ($c) use ($prodottoPerOl) {
                    return [
                        'id' => $c->id,
                        'ol' => $c->ol,
                        'prodotto' => $prodottoPerOl($c->ol),
                        'coil' => $c->coil,
                        'km' => $c->km,
                        'num_fo' => $c->num_fo,
                        'fo_try' => $c->fo_try,
                        'stage' => $c->stage,
                        'not_conformity' => $c->not_conformity,
                        'date_create' => $c->date_create,
                        'note' => $c->note,
                        'tipo_cavo' => $c->tipo_cavo,
                    ];
                });
        } catch (\Throwable $e) {
            Log::warning('ProductionHubController@qualita QtCheckerReport error: ' . $e->getMessage());
            $checkerList = collect();
        }

        // CPR Tests
        try {
            $cprList = QtCprTest::with('categoriaTipo')
                ->where(function ($q) use ($searchCodes) {
                    foreach ($searchCodes as $c) {
                        $q->orWhere('ol', 'LIKE', '%' . $c . '%');
                    }
                })
                ->orderBy('data_prova', 'desc')
                ->get()
                ->map(function ($t) use ($prodottoPerOl) {
                    return [
                        'id' => $t->id,
                        'ol' => $t->ol,
                        'prodotto' => $prodottoPerOl($t->ol),
                        'materiale' => $t->materiale,
                        'descrizione' => $t->descrizione,
                        'esito' => $t->esito,
                        'standard' => $t->standard,
                        'specifica' => $t->specifica,
                        'categoria' => $t->categoriaTipo->categoria ?? null,
                        'class' => $t->class,
                        'data_prova' => $t->data_prova,
                        'cliente' => $t->cliente,
                        'note' => $t->note,
                        'path_drive' => $t->path_drive,
                    ];
                });
        } catch (\Throwable $e) {
            Log::warning('ProductionHubController@qualita QtCprTest error: ' . $e->getMessage());
            $cprList = collect();
        }

        // Type Tests
        try {
            $typeTests = QtTypeTest::with('categoriaTipo')
                ->where(function ($q) use ($searchCodes) {
                    foreach ($searchCodes as $c) {
                        $q->orWhere('ol', 'LIKE', '%' . $c . '%');
                    }
                })
                ->orderBy('data_prova', 'desc')
                ->get()
                ->map(function ($t) use ($prodottoPerOl) {
                    return [
                        'id' => $t->id,
                        'ol' => $t->ol,
                        'prodotto' => $prodottoPerOl($t->ol),
                        'materiale' => $t->materiale,
                        'descrizione' => $t->descrizione,
                        'esito' => $t->esito,
                        'standard' => $t->standard,
                        'specifica' => $t->specifica,
                        'categoria' => $t->categoriaTipo->categoria ?? null,
                        'data_prova' => $t->data_prova,
                        'cliente' => $t->cliente,
                        'note' => $t->note,
                        'path_drive' => $t->path_drive,
                    ];
                });
        } catch (\Throwable $e) {
            Log::warning('ProductionHubController@qualita QtTypeTest error: ' . $e->getMessage());
            $typeTests = collect();
        }

        // FAI
        try {
            $faiList = QtFai::where(function ($q) use ($searchCodes) {
                    foreach ($searchCodes as $c) {
                        $q->orWhere('ol', 'LIKE', '%' . $c . '%');
                    }
                })
                ->orderBy('data_creazione', 'desc')
                ->get();
        } catch (\Throwable $e) {
            Log::warning('ProductionHubController@qualita QtFai error: ' . $e->getMessage());
            $faiList = collect();
        }

        return response()->json($this->utf8ize([
            'non_conformita' => $ncList->values()->all(),
            'checker_reports' => $checkerList->values()->all(),
            'cpr_tests' => $cprList->values()->all(),
            'type_tests' => $typeTests->values()->all(),
            'fai_tests' => $faiList->values()->all(),
        ]));
    }

    /**
     * Tab Documenti: Documenti caricati e cartelle Drive della commessa / OL
     */
    public function documenti(Request $request): JsonResponse
    {
        $code = $this->cleanCode((string) $request->get('code', ''));
        if (empty($code)) {
            return response()->json([]);
        }

        $resolved = $this->resolveCodes($code);
        $searchCommesse = collect([$code])
            ->merge($resolved['commesse'])
            ->filter()
            ->unique()
            ->values();

        $documents = collect();

        // Trova i WfOrder correlati (per commessa o commessa_sistema)
        $wfOrders = WfOrder::where(function ($q) use ($searchCommesse) {
            foreach ($searchCommesse as $cm) {
                $q->orWhere('commessa', '=', $cm)
                  ->orWhere('commessa', 'LIKE', '%' . $cm . '%')
                  ->orWhere('commessa_sistema', 'LIKE', '%' . $cm . '%');
            }
        })->get();

        $wfOrderIds = $wfOrders->pluck('id')->toArray();

        if (!empty($wfOrderIds)) {
            $docs = DB::table('wf_documents')
                ->where(function ($q) use ($wfOrderIds) {
                    $q->whereIn('model_id', $wfOrderIds)
                      ->orWhereIn('model_head_id', $wfOrderIds);
                })
                ->distinct()
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($docs as $d) {
                $documents->push([
                    'id' => $d->id,
                    'nome_file' => $d->nome_file,
                    'tipologia' => $d->tipologia,
                    'riferimento' => $d->riferimento,
                    'id_file_drive' => $d->id_file_drive,
                    'created_at' => $d->created_at,
                    'fonte' => 'Workflow Commessa',
                ]);
            }
        }

        // Cartelle Drive associate ai WfOrder
        $driveFolders = $wfOrders->map(function ($wf) {
            return [
                'commessa' => $wf->commessa,
                'revisione' => $wf->revisione,
                'stato' => $wf->stato,
                'folder_drive' => $wf->folder_drive,
                'id_file_drive' => $wf->id_file_drive,
            ];
        })->filter(fn($f) => !empty($f['folder_drive']) || !empty($f['id_file_drive']))->values();

        return response()->json($this->utf8ize([
            'documents' => $documents->values()->all(),
            'drive_folders' => $driveFolders->values()->all(),
        ]));
    }

    /**
     * Tab Spedizioni: DDT e righe spedite
     */
    public function spedizioni(Request $request): JsonResponse
    {
        $code = $this->cleanCode((string) $request->get('code', ''));
        if (empty($code)) {
            return response()->json([
                'ddt' => [],
                'righe_spedite' => [],
            ]);
        }

        $resolved = $this->resolveCodes($code);
        $searchCodes = $resolved['all_codes'];
        $searchCommesse = $resolved['commesse'];

        // 1. DDT da ddt_spedizioni
        $ddtList = DdtSpedizione::where(function ($q) use ($searchCodes, $searchCommesse) {
                foreach ($searchCodes as $c) {
                    $q->orWhere('riferimento_interno', 'LIKE', '%' . $c . '%')
                      ->orWhere('ns_ovd', 'LIKE', '%' . $c . '%');
                }
                if (!empty($searchCommesse)) {
                    $orderIds = WfOrder::whereIn('commessa', $searchCommesse)->pluck('id');
                    if ($orderIds->isNotEmpty()) {
                        $q->orWhereIn('wf_order_id', $orderIds);
                    }
                }
            })
            ->orderBy('data_ddt', 'desc')
            ->get();

        // 2. Righe spedite da fi_shipped_rows usando DB::table per evitare global scopes errati
        try {
            $shippedRows = DB::table('fi_shipped_rows')
                ->where(function ($q) use ($searchCodes) {
                    foreach ($searchCodes as $c) {
                        $q->orWhere('order', 'LIKE', '%' . $c . '%')
                          ->orWhere('commessa', 'LIKE', '%' . $c . '%');
                    }
                })
                ->orderBy('date_row', 'desc')
                ->get();
        } catch (\Throwable $e) {
            Log::warning('ProductionHubController@spedizioni fi_shipped_rows error: ' . $e->getMessage());
            $shippedRows = collect();
        }

        return response()->json($this->utf8ize([
            'ddt' => $ddtList->values()->all(),
            'righe_spedite' => $shippedRows->values()->all(),
        ]));
    }

    // --- Helper interni di aggregazione e risoluzione codici ---

    /**
     * Risolve in modo bidirezionale OL e Commessa attraverso AGG_MASTER_TMP e WfOrder.
     * - Se si cerca un OL (es. 90015116), recupera la Commessa (es. 4610010570).
     * - Se si cerca una Commessa (es. 4610010570), recupera tutti gli OL associati (es. 90015116, 90015117).
     */
    private function resolveCodes(string $code): array
    {
        $code = $this->cleanCode($code);
        $ols = collect();
        $commesse = collect();
        $structure = collect();

        // 1. Cerca in AGG_MASTER_TMP (GP)
        try {
            $rows = DB::connection('sqlsrv_gp')
                ->table('AGG_MASTER_TMP')
                ->select('cdOrdine', 'Commessa')
                ->where(function ($q) use ($code) {
                    $q->where('cdOrdine', '=', $code)
                      ->orWhere('Commessa', '=', $code)
                      ->orWhere('cdOrdine', 'LIKE', '%' . $code . '%')
                      ->orWhere('Commessa', 'LIKE', '%' . $code . '%');
                })
                ->get();

            foreach ($rows as $row) {
                $cd = trim($row->cdOrdine ?? '');
                $cm = trim($row->Commessa ?? '');
                if (!empty($cd)) {
                    $ols->push($cd);
                }
                if (!empty($cm)) {
                    $commesse->push($cm);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ProductionHubController@resolveCodes AGG_MASTER_TMP error: ' . $e->getMessage());
        }

        // 2. Cerca in WfOrder se abbiamo trovato una commessa o cerchiamo direttamente per commessa
        try {
            $wfOrders = WfOrder::where(function ($q) use ($code, $commesse) {
                $q->where('commessa', '=', $code)
                  ->orWhere('commessa', 'LIKE', '%' . $code . '%');
                if ($commesse->isNotEmpty()) {
                    $q->orWhereIn('commessa', $commesse->all());
                }
            })->get();

            foreach ($wfOrders as $wf) {
                $cm = trim($wf->commessa ?? '');
                if (!empty($cm)) {
                    $commesse->push($cm);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ProductionHubController@resolveCodes WfOrder error: ' . $e->getMessage());
        }

        // 3. Se abbiamo commesse ma nessun OL (es. commessa creata da WF),
        // risaliamo agli OL in AGG_MASTER_TMP
        if ($ols->isEmpty() && $commesse->isNotEmpty()) {
            try {
                $moreRows = DB::connection('sqlsrv_gp')
                    ->table('AGG_MASTER_TMP')
                    ->select('cdOrdine')
                    ->whereIn('Commessa', $commesse->unique()->all())
                    ->get();

                foreach ($moreRows as $r) {
                    $cd = trim($r->cdOrdine ?? '');
                    if (!empty($cd)) {
                        $ols->push($cd);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('ProductionHubController@resolveCodes secondary AGG_MASTER_TMP error: ' . $e->getMessage());
            }
        }

        // 4. Espandi la struttura di produzione tramite OrderLink.
        //    - Figli: cdOrdine -> OrderLink = OL padre.
        //    - Padre: cdOrdine stesso (la riga padre ha OrderLink NULL).
        try {
            $baseOls = $ols->unique()->filter()->values()->all();

            $orderLinks = collect($baseOls)
                ->merge(
                    DB::connection('sqlsrv_gp')
                        ->table('AGG_MASTER_TMP')
                        ->whereIn('cdOrdine', $baseOls)
                        ->whereNotNull('OrderLink')
                        ->pluck('OrderLink')
                )
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->unique()
                ->values();

            if ($orderLinks->isNotEmpty()) {
                $famRows = DB::connection('sqlsrv_gp')
                    ->table('AGG_MASTER_TMP')
                    ->select('cdOrdine', 'Commessa', 'cdProdotto', 'OrderLink', 'OrderSeq')
                    ->where(function ($q) use ($orderLinks) {
                        $q->whereIn('OrderLink', $orderLinks->all())
                          ->orWhereIn('cdOrdine', $orderLinks->all());
                    })
                    ->orderBy('OrderSeq')
                    ->orderBy('cdOrdine')
                    ->get();

                foreach ($famRows as $r) {
                    $cd = trim($r->cdOrdine ?? '');
                    $cm = trim($r->Commessa ?? '');
                    if (!empty($cd)) {
                        $ols->push($cd);
                    }
                    if (!empty($cm)) {
                        $commesse->push($cm);
                    }
                    $structure->push([
                        'ol' => $cd,
                        'commessa' => $cm,
                        'prodotto' => trim($r->cdProdotto ?? ''),
                        'order_seq' => trim($r->OrderSeq ?? ''),
                        'order_link' => trim($r->OrderLink ?? ''),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ProductionHubController@resolveCodes OrderLink error: ' . $e->getMessage());
        }

        $olsUnique = $ols->unique()->values();
        $commesseUnique = $commesse->unique()->values();

        $primaryOl = $olsUnique->first() ?? $code;
        $primaryCommessa = $commesseUnique->first() ?? null;

        if ($commesseUnique->contains($code)) {
            $primaryCommessa = $code;
        }

        $allCodes = collect([$code])
            ->merge($olsUnique)
            ->merge($commesseUnique)
            ->unique()
            ->filter()
            ->values()
            ->all();

        return [
            'input' => $code,
            'primary_ol' => $primaryOl,
            'primary_commessa' => $primaryCommessa,
            'ols' => $olsUnique->all(),
            'commesse' => $commesseUnique->all(),
            'all_codes' => $allCodes,
            'structure' => $structure->unique('ol')->values()->all(),
        ];
    }

    private function resolveOrderInfo(string $code): array
    {
        $resolved = $this->resolveCodes($code);

        $info = [
            'codice_richiesto' => $code,
            'ordine' => $resolved['primary_ol'],
            'commessa' => $resolved['primary_commessa'],
            'ol_correlati' => $resolved['ols'],
            'commesse_correlate' => $resolved['commesse'],
            'prodotto' => null,
            'descrizione' => null,
            'quantita_ordinata' => null,
            'um' => null,
            'data_ordine' => null,
            'cliente' => null,
            'note' => null,
            'wf_order_id' => null,
            'wf_stato' => null,
            'folder_drive' => null,
        ];

        // 1. Cerca su AGG_MASTER_TMP con tutti i codici correlati
        try {
            $gp = DB::connection('sqlsrv_gp')->table('AGG_MASTER_TMP as M')
                ->leftJoin('AGG_PRODOTTI_TMP as P', 'P.cdProdotto', '=', 'M.cdProdotto')
                ->leftJoin('AGG_CLIENTI_TMP as C', 'C.cdCliente', '=',
                    DB::raw("RIGHT('0000000000' + CAST(M.cdCliente AS varchar(10)), 10)"))
                ->select('M.*', 'P.dsProdotto', 'P.Conversione', 'C.dsCliente as cliente_anagrafica')
                ->where(function ($q) use ($resolved, $code) {
                    $q->where('M.cdOrdine', '=', $code)
                      ->orWhere('M.Commessa', '=', $code);
                    if (!empty($resolved['ols'])) {
                        $q->orWhereIn('M.cdOrdine', $resolved['ols']);
                    }
                    if (!empty($resolved['commesse'])) {
                        $q->orWhereIn('M.Commessa', $resolved['commesse']);
                    }
                })
                ->first();

            if ($gp) {
                if (!empty($gp->cdOrdine)) {
                    $info['ordine'] = trim($gp->cdOrdine);
                }
                if (!empty($gp->Commessa)) {
                    $info['commessa'] = trim($gp->Commessa);
                }
                $info['prodotto'] = trim($gp->cdProdotto ?? '');
                $info['descrizione'] = trim($gp->dsProdotto ?? '');
                $info['quantita_ordinata'] = (float) ($gp->QtaOrdinata ?? 0);
                $info['um'] = trim($gp->cdUM ?? '');
                $info['data_ordine'] = $gp->dtOrdine ?? $gp->dataInserimento ?? null;
                $info['cliente'] = trim($gp->cliente_anagrafica ?? $gp->cdCliente ?? '');
                $info['note'] = trim($gp->Note ?? '');
            }
        } catch (\Throwable $e) {
            Log::warning('resolveOrderInfo sqlsrv_gp error: ' . $e->getMessage());
        }

        // 2. Cerca su WfOrder con la commessa risolta o con il codice cercato
        try {
            $searchCommesse = collect([$info['commessa'], $code])
                ->merge($resolved['commesse'])
                ->filter()
                ->unique()
                ->values();

            $wf = WfOrder::where(function ($q) use ($searchCommesse) {
                foreach ($searchCommesse as $cm) {
                    $q->orWhere('commessa', '=', $cm)
                      ->orWhere('commessa', 'LIKE', '%' . $cm . '%')
                      ->orWhere('commessa_sistema', 'LIKE', '%' . $cm . '%');
                }
            })->first();

            if ($wf) {
                $info['commessa'] = $wf->commessa;
                $info['wf_order_id'] = $wf->id;
                $info['wf_stato'] = $wf->stato;
                $info['folder_drive'] = $wf->folder_drive;
                if (empty($info['prodotto']) && !empty($wf->commessa_sistema)) {
                    $info['prodotto'] = $wf->commessa_sistema;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('resolveOrderInfo WfOrder error: ' . $e->getMessage());
        }

        return $info;
    }

    private function getStrisciateSummary(array $resolved): array
    {
        $count = 0;
        $quantita = 0.0;
        $searchCodes = $resolved['all_codes'];

        // OL di cui calcolare la quantità prodotta: l'OL cercato, oppure il primary_ol (ricerca per commessa)
        $targetOl = in_array($resolved['input'], $resolved['ols'] ?? [], true)
            ? $resolved['input']
            : ($resolved['primary_ol'] ?? $resolved['input']);

        try {
            $res = DB::connection('sqlsrv_root_gp')
                ->table('Produzione as PRD')
                ->join('Dettagli_sugli_ordini as DSO', 'DSO.IDDettagliOrdini', '=', 'PRD.IDDettOrd')
                ->join('Dettagli_Master as DM', 'DM.idMaster', '=', 'DSO.IDMaster')
                ->join('Ordini as O', 'O.IDOrdine', '=', 'DM.IDOrdine')
                ->where('PRD.Confermato', 1)
                ->where('PRD.Significativo', 1)
                ->where('PRD.IdSchedaPrdOrdineAcc', 0)
                ->where(function ($q) use ($searchCodes) {
                    foreach ($searchCodes as $c) {
                        $q->orWhere('O.NumeroOrdineAcquisto', 'LIKE', '%' . $c . '%')
                          ->orWhere('DM.NRigaOrd', 'LIKE', '%' . $c . '%');
                    }
                })
                ->select(DB::raw('COUNT(*) as total_rows'))
                ->first();

            if ($res) {
                $count = (int) ($res->total_rows ?? 0);
            }

            // Quantità prodotta: solo strisciate dell'OL cercato (non dell'intera famiglia OrderLink)
            if (!empty($targetOl)) {
                $quantita = (float) DB::connection('sqlsrv_root_gp')
                    ->table('Produzione as PRD')
                    ->join('Dettagli_sugli_ordini as DSO', 'DSO.IDDettagliOrdini', '=', 'PRD.IDDettOrd')
                    ->join('Dettagli_Master as DM', 'DM.idMaster', '=', 'DSO.IDMaster')
                    ->join('Ordini as O', 'O.IDOrdine', '=', 'DM.IDOrdine')
                    ->where('PRD.Confermato', 1)
                    ->where('PRD.Significativo', 1)
                    ->where('PRD.IdSchedaPrdOrdineAcc', 0)
                    ->where(function ($q) use ($targetOl) {
                        $q->where('O.NumeroOrdineAcquisto', '=', $targetOl)
                          ->orWhere('DM.NRigaOrd', '=', $targetOl)
                          ->orWhere('DM.NRigaOrd', '=', '0000' . $targetOl);
                    })
                    ->sum('PRD.#Cicli');
            }
        } catch (\Throwable $e) {
            Log::warning('getStrisciateSummary sqlsrv_root_gp: ' . $e->getMessage());
        }

        return ['count' => $count, 'quantita_totale' => round($quantita, 2)];
    }

    private function getFabbisogniCount(array $resolved): int
    {
        $searchCodes = $resolved['all_codes'];

        try {
            return DB::connection('sqlsrv_gp')
                ->table('AGG_EXP_PRODUZIONE_FABB_TMP')
                ->where(function ($q) use ($searchCodes) {
                    foreach ($searchCodes as $c) {
                        $q->orWhere('Ordine', 'LIKE', '%' . $c . '%');
                    }
                })
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getQualitaSummary(array $resolved): array
    {
        $ncCount = 0;
        $ncAperte = 0;
        $proveCount = 0;
        $searchCodes = $resolved['all_codes'];

        try {
            $ncCount = QtConformita::where(function ($q) use ($searchCodes) {
                foreach ($searchCodes as $c) {
                    $q->orWhere('ol', 'LIKE', '%' . $c . '%');
                }
            })->count();

            $ncAperte = QtConformita::where(function ($q) use ($searchCodes) {
                foreach ($searchCodes as $c) {
                    $q->orWhere('ol', 'LIKE', '%' . $c . '%');
                }
            })
            ->whereIn('stato', [1, 2])
            ->count();

            $proveCount += QtCheckerReport::where(function ($q) use ($searchCodes) {
                foreach ($searchCodes as $c) {
                    $q->orWhere('ol', 'LIKE', '%' . $c . '%');
                }
            })->count();

            $proveCount += QtCprTest::where(function ($q) use ($searchCodes) {
                foreach ($searchCodes as $c) {
                    $q->orWhere('ol', 'LIKE', '%' . $c . '%');
                }
            })->count();

            $proveCount += QtTypeTest::where(function ($q) use ($searchCodes) {
                foreach ($searchCodes as $c) {
                    $q->orWhere('ol', 'LIKE', '%' . $c . '%');
                }
            })->count();

            $proveCount += QtFai::where(function ($q) use ($searchCodes) {
                foreach ($searchCodes as $c) {
                    $q->orWhere('ol', 'LIKE', '%' . $c . '%');
                }
            })->count();
        } catch (\Throwable $e) {
            Log::warning('getQualitaSummary error: ' . $e->getMessage());
        }

        return [
            'nc_count' => $ncCount,
            'nc_aperte' => $ncAperte,
            'prove_count' => $proveCount,
        ];
    }

    private function getDocumentiCount(array $resolved, ?string $wfOrderId): int
    {
        try {
            $searchCommesse = $resolved['commesse'];
            $wfOrderIds = WfOrder::where(function ($q) use ($searchCommesse) {
                foreach ($searchCommesse as $cm) {
                    $q->orWhere('commessa', '=', $cm)
                      ->orWhere('commessa', 'LIKE', '%' . $cm . '%')
                      ->orWhere('commessa_sistema', 'LIKE', '%' . $cm . '%');
                }
            })->pluck('id')->toArray();

            if ($wfOrderId && !in_array($wfOrderId, $wfOrderIds)) {
                $wfOrderIds[] = $wfOrderId;
            }

            if (!empty($wfOrderIds)) {
                return DB::table('wf_documents')
                    ->where(function ($q) use ($wfOrderIds) {
                        $q->whereIn('model_id', $wfOrderIds)
                          ->orWhereIn('model_head_id', $wfOrderIds);
                    })
                    ->distinct()
                    ->count('id');
            }
        } catch (\Throwable $e) {
            Log::warning('getDocumentiCount error: ' . $e->getMessage());
        }

        return 0;
    }

    private function getSpedizioniSummary(array $resolved, ?string $wfOrderId): array
    {
        $count = 0;
        $quantita = 0.0;
        $searchCodes = $resolved['all_codes'];
        $searchCommesse = $resolved['commesse'];

        try {
            // Conta DDT
            $ddtCount = DdtSpedizione::where(function ($q) use ($searchCodes, $searchCommesse, $wfOrderId) {
                foreach ($searchCodes as $c) {
                    $q->orWhere('riferimento_interno', 'LIKE', '%' . $c . '%')
                      ->orWhere('ns_ovd', 'LIKE', '%' . $c . '%');
                }
                if ($wfOrderId) {
                    $q->orWhere('wf_order_id', $wfOrderId);
                } elseif (!empty($searchCommesse)) {
                    $orderIds = WfOrder::whereIn('commessa', $searchCommesse)->pluck('id');
                    if ($orderIds->isNotEmpty()) {
                        $q->orWhereIn('wf_order_id', $orderIds);
                    }
                }
            })->count();

            // Conta righe spedite
            $shipped = DB::table('fi_shipped_rows')->where(function ($q) use ($searchCodes) {
                foreach ($searchCodes as $c) {
                    $q->orWhere('order', 'LIKE', '%' . $c . '%')
                      ->orWhere('commessa', 'LIKE', '%' . $c . '%');
                }
            })
            ->select(DB::raw('COUNT(*) as total_rows'), DB::raw('SUM(delivered_qty) as total_delivered'))
            ->first();

            $shippedRowsCount = $shipped ? (int) $shipped->total_rows : 0;
            $quantita = $shipped ? (float) ($shipped->total_delivered ?? 0.0) : 0.0;

            $count = $ddtCount + $shippedRowsCount;
        } catch (\Throwable $e) {
            Log::warning('getSpedizioniSummary error: ' . $e->getMessage());
        }

        return [
            'count' => $count,
            'quantita_totale' => round($quantita, 2),
        ];
    }

    /**
     * Recupera da Google Drive i log CSV dei diametri PLC (es. Diametro, Diam_X, Diam_Y, DataEvento)
     * registrati dalla macchina nell'intervallo orario della lavorazione.
     * Applica campionamento adattivo e calcola KPI statistici e di conformità.
     */
    public function telemetria(Request $request): JsonResponse
    {
        $macchinaInput = trim((string) $request->get('macchina', ''));
        $dataInizio = trim((string) $request->get('data_inizio', ''));
        $dataFine = trim((string) $request->get('data_fine', ''));
        $targetDiametro = $request->filled('target') ? (float) $request->get('target') : null;
        $tolleranza = $request->filled('tolleranza') ? (float) $request->get('tolleranza') : null;

        if ($macchinaInput === '' || $dataInizio === '') {
            return response()->json([
                'success' => false,
                'message' => 'Parametri macchina e data_inizio obbligatori.',
                'punti' => [],
                'kpi' => null,
            ], 422);
        }

        // Risolvi il nome cartella della macchina su Google Drive dalla tabella machineries
        $machinery = DB::table('machineries')
            ->where(function ($q) use ($macchinaInput) {
                $q->where('nome', $macchinaInput)
                  ->orWhere('name_gp', $macchinaInput);
                if (is_numeric($macchinaInput)) {
                    $q->orWhere('id_gp', (int) $macchinaInput);
                }
            })
            ->first();

        $macchinaNome = $machinery ? $machinery->nome : $macchinaInput;
        $idRisorsa = $machinery ? (string) ($machinery->name_gp ?? $machinery->id_gp ?? $macchinaInput) : $macchinaInput;
        $macchinaId40 = $machinery ? (string) ($machinery->id_gp ?? $machinery->name_gp ?? '') : (is_numeric($macchinaInput) ? $macchinaInput : '');
        $velocitaMinimaTeorica = $machinery && is_numeric($machinery->velocita_minima) ? (float) $machinery->velocita_minima : null;

        try {
            $tsInizio = Carbon::parse($dataInizio);
            $tsFine = $dataFine !== '' ? Carbon::parse($dataFine) : (clone $tsInizio)->endOfDay();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Formato date non valido: ' . $e->getMessage(),
                'punti' => [],
                'kpi' => null,
            ], 422);
        }

        if ($tsInizio->gt($tsFine)) {
            [$tsInizio, $tsFine] = [$tsFine, $tsInizio];
        }

        // Elenco date coperte dall'intervallo (es. lavorazioni a cavallo della mezzanotte)
        $dateList = [];
        $cur = (clone $tsInizio)->startOfDay();
        $endDay = (clone $tsFine)->startOfDay();
        while ($cur->lte($endDay)) {
            $dateList[] = $cur->format('Y-m-d');
            $cur->addDay();
        }

        $rootFolderId = '0AHg0OVQB2sJiUk9PVA';
        $rawRows = [];

        foreach ($dateList as $dateStr) {
            $year = substr($dateStr, 0, 4);

            try {
                // Cache ID cartella anno (24 ore)
                $yearFolderId = Cache::remember("drive_plc_year_{$year}", 86400, function () use ($rootFolderId, $year) {
                    return GoogleDrive::search($rootFolderId, 'google', 'dir', $year);
                });

                if (!$yearFolderId) {
                    continue;
                }

                // Cache ID cartella macchina (24 ore)
                $cacheKeyMachine = "drive_plc_m_{$year}_" . md5($macchinaNome);
                $machineFolderId = Cache::remember($cacheKeyMachine, 86400, function () use ($yearFolderId, $macchinaNome) {
                    return GoogleDrive::search($yearFolderId, 'google', 'dir', $macchinaNome);
                });

                if (!$machineFolderId) {
                    continue;
                }

                // Cerca file CSV della data esatta (es. 2026-09-07.csv)
                $fileName = $dateStr . '.csv';
                $fileId = GoogleDrive::search($machineFolderId, 'google', 'file', $fileName);

                if (!$fileId) {
                    continue;
                }

                // Download del file da Google Drive
                $csvContent = GoogleDrive::download($fileId);
                if (!$csvContent) {
                    continue;
                }

                $lines = preg_split("/\r\n|\n|\r/", trim($csvContent));
                if (empty($lines)) {
                    continue;
                }

                foreach ($lines as $idx => $line) {
                    if ($line === '') {
                        continue;
                    }
                    $cols = str_getcsv($line);
                    if ($idx === 0 && isset($cols[0]) && strtolower(trim($cols[0])) === 'diametro') {
                        continue;
                    }

                    if (count($cols) < 4) {
                        continue;
                    }

                    $diam = (float) trim($cols[0]);
                    $diamX = (float) trim($cols[1]);
                    $diamY = (float) trim($cols[2]);
                    $dtStr = trim($cols[3], " \"'");

                    try {
                        $rowDt = Carbon::parse($dtStr);
                    } catch (\Throwable $e) {
                        continue;
                    }

                    // Filtra sull'intervallo temporale della lavorazione
                    if ($rowDt->gte($tsInizio) && $rowDt->lte($tsFine)) {
                        if ($diam > 0.001) {
                            $rawRows[] = [
                                'ts' => $rowDt->timestamp,
                                'dt_str' => $rowDt->format('Y-m-d H:i:s'),
                                'd' => round($diam, 3),
                                'x' => round($diamX, 3),
                                'y' => round($diamY, 3),
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("ProductionHubController@telemetria errore per data {$dateStr}: " . $e->getMessage());
            }
        }

        $fonteDati = 'Google Drive';

        // Se Google Drive non contiene campioni (file non sincronizzato o recente), recupera direttamente da GP DB
        // Nota: STL_DIAMETRI_V.IDRisorsa è INT — salta il fallback se il valore risolto non è numerico
        // (es. macchine solo 4.0 con name_gp testuale come 'TR 45 MM1')
        if (empty($rawRows) && is_numeric($idRisorsa)) {
            try {
                $dbRows = DB::connection('sqlsrv_gp')
                    ->table('STL_DIAMETRI_V')
                    ->where('IDRisorsa', $idRisorsa)
                    ->whereBetween('DataEvento', [$tsInizio->format('Y-m-d H:i:s'), $tsFine->format('Y-m-d H:i:s')])
                    ->orderBy('DataEvento', 'asc')
                    ->get(['Diametro', 'Diam_X', 'Diam_Y', 'DataEvento']);

                if ($dbRows->isNotEmpty()) {
                    $fonteDati = 'Database GP (STL_DIAMETRI_V)';
                    foreach ($dbRows as $row) {
                        $diam = (float) ($row->Diametro ?? 0);
                        if ($diam > 0.001) {
                            $rowDt = Carbon::parse($row->DataEvento);
                            $rawRows[] = [
                                'ts' => $rowDt->timestamp,
                                'dt_str' => $rowDt->format('Y-m-d H:i:s'),
                                'd' => round($diam, 3),
                                'x' => round((float) ($row->Diam_X ?? 0), 3),
                                'y' => round((float) ($row->Diam_Y ?? 0), 3),
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('ProductionHubController@telemetria fallback DB error: ' . $e->getMessage());
            }
        }

        // -------------------------------------------------------------
        // Dati 4.0 da SQL Server (STL_Info_Ordine_V + ACD_Fermi_T)
        // -------------------------------------------------------------
        $processo40 = null;
        if ($macchinaId40 !== '' && is_numeric($macchinaId40)) {
            try {
                $inizioStr = $tsInizio->format('Y-m-d H:i:s');
                $fineStr = $tsFine->format('Y-m-d H:i:s');

                // 1. Serie temporali di processo (Velocità Linea, Velocità Estrusore, Metri Prodotti, Diametro)
                $rows40 = DB::connection('sqlsrv_root_gp')
                    ->table('STL_Info_Ordine_V')
                    ->where('MacchinaId', $macchinaId40)
                    ->whereBetween('DataMisurazione', [$inizioStr, $fineStr])
                    ->select('Caratteristica', 'ValoreMisurato', 'DataMisurazione', 'OperatoreScheda')
                    ->orderBy('DataMisurazione', 'asc')
                    ->get();

                if ($rows40->isNotEmpty()) {
                    $speedVals = [];
                    $extruderVals = [];
                    $operatori = [];
                    $timelinePoints = [];
                    $usaDiametro40 = empty($rawRows);

                    foreach ($rows40 as $r40) {
                        $val = (float) $r40->ValoreMisurato;
                        $dt = substr((string) $r40->DataMisurazione, 0, 19);
                        if (!empty($r40->OperatoreScheda)) {
                            $operatori[trim($r40->OperatoreScheda)] = true;
                        }

                        if (!isset($timelinePoints[$dt])) {
                            $timelinePoints[$dt] = [
                                't' => $dt,
                                'v' => null,
                                'e' => null,
                                'm' => null,
                            ];
                        }

                        $car = (string) $r40->Caratteristica;
                        if ($car === 'Velocità Linea') {
                            $speedVals[] = $val;
                            $timelinePoints[$dt]['v'] = round($val, 2);
                        } elseif ($car === 'Velocità Estrusore') {
                            $extruderVals[] = $val;
                            $timelinePoints[$dt]['e'] = round($val, 2);
                        } elseif ($car === 'Metri Prodotti') {
                            $timelinePoints[$dt]['m'] = round($val, 1);
                        } elseif ($car === 'Diametro' && $val > 0.001) {
                            // Fallback: macchina 4.0 senza CSV → usa il diametro della vista
                            if ($usaDiametro40) {
                                $dtObj = Carbon::parse($dt);
                                $rawRows[] = [
                                    'ts' => $dtObj->timestamp,
                                    'dt_str' => $dt,
                                    'd' => round($val, 3),
                                    'x' => 0,
                                    'y' => 0,
                                ];
                            }
                        }
                    }

                    if (!empty($rawRows) && $fonteDati === 'Google Drive') {
                        $fonteDati = 'STL_Info_Ordine_V (4.0)';
                    }

                    // Campionamento delle serie 4.0 a max 300 punti per evitare overhead
                    $timelineArray = array_values($timelinePoints);
                    $totalT40 = count($timelineArray);
                    $sampled40 = [];
                    $maxPts40 = 300;
                    if ($totalT40 <= $maxPts40) {
                        $sampled40 = $timelineArray;
                    } else {
                        $step = (float) $totalT40 / $maxPts40;
                        for ($i = 0; $i < $maxPts40; $i++) {
                            $idx = min((int) floor($i * $step), $totalT40 - 1);
                            $sampled40[] = $timelineArray[$idx];
                        }
                    }

                    // 2. Fermi macchina che si sovrappongono alla lavorazione
                    $fermiRows = DB::connection('sqlsrv_root_gp')
                        ->table('ACD_Fermi_T as F')
                        ->leftJoin('Acd_Produzione_T as P', 'F.AF_FK_AcdProduzione', '=', 'P.AP_ID')
                        ->leftJoin('Causali_Fermo as CF', 'F.AF_FK_CausaleFermo', '=', 'CF.IDCausaleFermo')
                        ->where('P.AP_FK_IDRisorsa', $macchinaId40)
                        ->where('F.AF_DataOraInizio', '<=', $fineStr)
                        ->where(function ($q) use ($inizioStr) {
                            $q->whereNull('F.AF_DataOraFine')
                              ->orWhere('F.AF_DataOraFine', '>=', $inizioStr);
                        })
                        ->select(
                            'F.AF_DataOraInizio',
                            'F.AF_DataOraFine',
                            'CF.DescrizioneFermo'
                        )
                        ->orderBy('F.AF_DataOraInizio', 'asc')
                        ->get();

                    $totaleFermiMinuti = 0;
                    $fermiList = [];
                    foreach ($fermiRows as $f) {
                        $fInizioTs = $f->AF_DataOraInizio ? strtotime((string) $f->AF_DataOraInizio) : null;
                        $fFineTs = $f->AF_DataOraFine ? strtotime((string) $f->AF_DataOraFine) : time();
                        // Durata clippata sui bordi della lavorazione
                        $startTs = max($fInizioTs ?? 0, $tsInizio->timestamp);
                        $endTs = min($fFineTs, $tsFine->timestamp);
                        $dur = max(0, (int) floor(($endTs - $startTs) / 60));
                        $totaleFermiMinuti += $dur;
                        $fermiList[] = [
                            'inizio' => $f->AF_DataOraInizio ? substr((string) $f->AF_DataOraInizio, 0, 19) : null,
                            'fine' => $f->AF_DataOraFine ? substr((string) $f->AF_DataOraFine, 0, 19) : null,
                            'causale' => trim((string) ($f->DescrizioneFermo ?? 'Non specificato')),
                            'durata_minuti' => $dur,
                        ];
                    }

                    $speedAvg = count($speedVals) > 0 ? array_sum($speedVals) / count($speedVals) : 0;
                    $speedMax = count($speedVals) > 0 ? max($speedVals) : 0;
                    $extrAvg = count($extruderVals) > 0 ? array_sum($extruderVals) / count($extruderVals) : 0;

                    $processo40 = [
                        'disponibile' => true,
                        'macchina_id' => $macchinaId40,
                        'operatori' => array_keys($operatori),
                        'operatore_principale' => !empty($operatori) ? array_keys($operatori)[0] : null,
                        'kpi' => [
                            'velocita_linea_media' => round($speedAvg, 2),
                            'velocita_linea_max' => round($speedMax, 2),
                            'velocita_minima_teorica' => $velocitaMinimaTeorica,
                            'velocita_estrusore_media' => round($extrAvg, 2),
                            'totale_fermo_minuti' => $totaleFermiMinuti,
                            'numero_fermi' => count($fermiList),
                        ],
                        'serie' => $sampled40,
                        'fermi' => $fermiList,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('ProductionHubController@telemetria 4.0 fetch error: ' . $e->getMessage());
            }
        }

        // Ordina cronologicamente
        usort($rawRows, fn ($a, $b) => $a['ts'] <=> $b['ts']);

        $totalCount = count($rawRows);
        if ($totalCount === 0) {
            return response()->json([
                'success' => true,
                'macchina' => $macchinaNome,
                'fonte' => $fonteDati,
                'data_inizio' => $tsInizio->format('Y-m-d H:i:s'),
                'data_fine' => $tsFine->format('Y-m-d H:i:s'),
                'campioni_totali' => 0,
                'punti' => [],
                'kpi' => null,
                'processo_40' => $processo40,
            ]);
        }

        // Statistiche complessive reali su tutti i campioni
        $diamValues = array_column($rawRows, 'd');
        $sum = array_sum($diamValues);
        $mean = $sum / $totalCount;
        $min = min($diamValues);
        $max = max($diamValues);
        $delta = $max - $min;

        $variance = 0.0;
        foreach ($diamValues as $v) {
            $variance += pow($v - $mean, 2);
        }
        $stdDev = sqrt($variance / $totalCount);

        // Calcolo massima ovalizzazione (|X - Y| quando entrambi > 0)
        $maxOval = 0.0;
        foreach ($rawRows as $r) {
            if ($r['x'] > 0.001 && $r['y'] > 0.001) {
                $oval = abs($r['x'] - $r['y']);
                if ($oval > $maxOval) {
                    $maxOval = $oval;
                }
            }
        }

        // Calcolo fuori tolleranza
        $fuoriTolleranzaCount = 0;
        if ($targetDiametro !== null && $tolleranza !== null && $tolleranza > 0) {
            $limMin = $targetDiametro - $tolleranza;
            $limMax = $targetDiametro + $tolleranza;
            foreach ($diamValues as $v) {
                if ($v < $limMin || $v > $limMax) {
                    $fuoriTolleranzaCount++;
                }
            }
        }

        // Downsampling per rendering reattivo: max 1.200 punti
        $maxChartPoints = 1200;
        $sampled = [];
        if ($totalCount <= $maxChartPoints) {
            $sampled = array_map(function ($r) {
                return [
                    't' => $r['dt_str'],
                    'd' => $r['d'],
                    'x' => $r['x'],
                    'y' => $r['y'],
                ];
            }, $rawRows);
        } else {
            $step = (float) $totalCount / $maxChartPoints;
            for ($i = 0; $i < $maxChartPoints; $i++) {
                $idx = min((int) floor($i * $step), $totalCount - 1);
                $r = $rawRows[$idx];
                $sampled[] = [
                    't' => $r['dt_str'],
                    'd' => $r['d'],
                    'x' => $r['x'],
                    'y' => $r['y'],
                ];
            }
        }

        return response()->json([
            'success' => true,
            'macchina' => $macchinaNome,
            'fonte' => $fonteDati,
            'data_inizio' => $tsInizio->format('Y-m-d H:i:s'),
            'data_fine' => $tsFine->format('Y-m-d H:i:s'),
            'kpi' => [
                'campioni_totali' => $totalCount,
                'campioni_grafico' => count($sampled),
                'medio' => round($mean, 3),
                'min' => round($min, 3),
                'max' => round($max, 3),
                'delta' => round($delta, 3),
                'std_dev' => round($stdDev, 3),
                'ovalizzazione_max' => round($maxOval, 3),
                'target' => $targetDiametro,
                'tolleranza' => $tolleranza,
                'fuori_tolleranza' => $fuoriTolleranzaCount,
                'fuori_tolleranza_pct' => round(($fuoriTolleranzaCount / $totalCount) * 100, 1),
            ],
            'punti' => $sampled,
            'processo_40' => $processo40,
        ]);
    }
}
