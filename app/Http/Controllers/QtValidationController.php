<?php

namespace App\Http\Controllers;

use App\Models\WfDocument;
use App\Models\WfDocumentValidation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QtValidationController extends Controller
{
    /**
     * Elenco dei documenti da validare (Lettura per la tabella Vue)
     */
    public function getDocumentsToValidate(Request $request): JsonResponse
    {
        $perPage = $request->input('itemsPerPage', 10);
        $sortBy = $request->input('sortBy', 'created_at');
        $orderBy = $request->input('orderBy', 'asc');

        $modelId = $request->input('model_id');
        $tipologia = $request->input('tipologia');
        $riferimento = $request->input('riferimento');
        $statoFiltro = $request->input('stato');
        $dal = $request->input('dal');
        $al = $request->input('al');

        // QUERY BASE: Riga singola pulita puntando direttamente alla tabella di validazione di Qualità
        $query = DB::table('wf_documents')
            ->leftJoin('wf_document_validations', function ($join) {
                $join->on('wf_document_validations.wf_document_id', '=', 'wf_documents.id')
                    ->where('wf_document_validations.reparto', '=', 'Qualita');
            })
            ->leftJoin('wf_orders', function ($join) {
                $join->on('wf_orders.commessa', '=', 'wf_documents.riferimento')
                    ->where('wf_orders.tipologia', '=', 1);
            })
            ->leftJoin('wf_documents as wf_documents_ddc', function ($join) {
                $join->whereRaw('LEFT(wf_documents_ddc.nome_file, 10) = LEFT(wf_documents.nome_file, 10)')
                    ->whereRaw('wf_documents_ddc.riferimento = wf_documents.riferimento')
                    ->where('wf_documents_ddc.tipologia', '=', 25);
            })
            ->select([
                'wf_documents.id',
                'wf_documents.model',
                'wf_documents.model_id',
                'wf_documents.tipologia',
                'wf_documents.nome_file',
                'wf_documents.id_file_drive',
                'wf_orders.id_file_drive as id_file_drive_commessa',
                'wf_documents_ddc.id_file_drive as id_file_drive_ddc',
                'wf_documents.riferimento',
                'wf_documents.created_at',
                // Forziamo il COALESCE pulito sulla colonna "stato"
                DB::raw("COALESCE(wf_document_validations.stato, 'DA-FARE') as stato")
            ])
            ->where('wf_documents.model', 'WfOrder')
            ->whereNotNull('wf_documents.id_file_drive');

        // 1. Filtro Tipologia
        if ($tipologia) {
            $tipologiaId = ($tipologia === 'IdoneitaDatore') ? 1 : 2;
            $query->where('wf_documents.tipologia', $tipologiaId);
        } else {
            $query->whereIn('wf_documents.tipologia', [20]);
        }

        // 2. Filtro Model ID
        if ($modelId) {
            $query->where('wf_documents.model_id', 'LIKE', "%{$modelId}%");
        }

        // 3. Filtro Stato
        if ($statoFiltro) {
            if ($statoFiltro === 'DA-FARE') {
                $query->where(function($q) {
                    $q->whereNull('wf_document_validations.stato')
                        ->orWhere('wf_document_validations.stato', '=', 'DA-FARE');
                });
            } else {
                $query->where('wf_document_validations.stato', '=', $statoFiltro);
            }
        } else {
            // Di default mostriamo TUTTO nella tabella (Sia Da Fare, Sia Fase 1, Sia Fase 2)
            // per evitare che le righe spariscano sotto gli occhi dell'utente creando glitch visivi
            $query->where(function($q) {
                $q->whereNull('wf_document_validations.stato')
                    ->orWhereIn('wf_document_validations.stato', ['DA-FARE', 'DDC-OK', 'ORDINE-OK']);
            });
        }

        // 4. Filtro Data
        if ($dal) {
            $query->whereDate('wf_documents.created_at', '>=', $dal);
        }
        if ($al) {
            $query->whereDate('wf_documents.created_at', '<=', $al);
        }

        // 5. Filtro Riferimento
        if ($riferimento) {
            $query->where('wf_documents.nome_file', 'LIKE', "%{$riferimento}%");
        }

        // 6. Ordinamento: ORDINE-OK sempre in fondo, poi per data
        $documentsToValidate = $query
            ->orderByRaw("CASE WHEN COALESCE(wf_document_validations.stato, 'DA-FARE') = 'ORDINE-OK' THEN 1 ELSE 0 END ASC")
            ->orderBy('wf_documents.created_at', 'desc')
            ->paginate($perPage);

        return response()->json($documentsToValidate);
    }

    /**
     * Avanzamento di stato sulla riga unica di validazione
     */
    public function approveDocument(Request $request): JsonResponse
    {
        Log::channel('stderr')->info($request->all());
        $user = Auth::user();

        $request->validate([
            'wf_document_id' => 'required|uuid',
            'attuale_stato'  => 'required|string|in:DA-FARE,DDC-OK,ORDINE-OK'
        ]);

        $document = WfDocument::find($request->wf_document_id);
        if (!$document) {
            return response()->json(['error' => 'Documento non trovato.'], 404);
        }

        DB::beginTransaction();
        try {
            // Calcoliamo lo stato successivo in modo deterministico
            $nuovoStato = 'DDC-OK';
            if ($request->attuale_stato === 'DDC-OK') {
                $nuovoStato = 'ORDINE-OK';
            } elseif ($request->attuale_stato === 'ORDINE-OK') {
                $nuovoStato = 'ORDINE-OK';
            }

            // Cerchiamo la riga esistente
            $validation = WfDocumentValidation::where('wf_document_id', $document->id)
                ->where('reparto', 'Qualita')
                ->first();

            // Se non esiste la riga iniziale, la creiamo al volo
            if (!$validation) {
                $validation = new WfDocumentValidation();
                $validation->wf_document_id = $document->id;
                $validation->reparto = 'Qualita';
            }

            // Allineiamo ENTRAMBE le colonne per evitare conflitti visivi sul DB
            $validation->stato = $nuovoStato;
            $validation->tipologia_validazione = 'Qualita_Standard'; // Valore fisso descrittivo
            $validation->user_id = $user->id;

            // Forza l'aggiornamento del timestamp
            $validation->touch();
            $validation->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Validazione salvata. Stato avanzato a ' . $nuovoStato
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('stderr')->info($e->getMessage());

            return response()->json([
                'error' => 'Errore durante l\'approvazione del documento.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function getQualityStats(Request $request): JsonResponse
    {
        // 1. GESTIONE FILTRI TEMPORALI (Default: ultimi 30 giorni)
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateTimeString());
        $endDate = $request->input('end_date', Carbon::now()->toDateTimeString());

        // 2. DOCUMENTI MAI ENTRATI IN VALIDAZIONE
        // Conta i record orfani di validazione specifica per il reparto Qualità
        $documentiSenzaValidazione = DB::table('wf_documents')
            ->leftJoin('wf_document_validations', function($join) {
                $join->on('wf_documents.id', '=', 'wf_document_validations.wf_document_id')
                    ->where('wf_document_validations.reparto', '=', 'Qualita');
            })
            ->where('tipologia',1)
            ->whereNull('wf_document_validations.id')
            ->count();

        // 3. DOCUMENTI IN CODA MA GIÀ AVVIATI (In lavorazione - Indipendenti dalle date)
        $codaInValidazione = DB::table('wf_document_validations')
            ->where('reparto', 'Qualita')
            ->select([
                // Record agganciati ma ancora in Fase 1
                DB::raw("COUNT(CASE WHEN stato = 'DDC-OK' THEN 1 END) as in_corso"),
                // Nel caso in cui nascano record in validazione contrassegnati come da fare originari
                DB::raw("COUNT(CASE WHEN stato = 'DDC-OK' AND tipologia_validazione = 'DA-FARE' THEN 1 END) as da_fare_iniziali")
            ])
            ->first();

        // 4. METRICHE DI PERFORMANCE DEL PERIODO (Influenzate dal filtro data)
        // Conta quanti documenti sono stati portati a termine (ORDINE-OK) nel range selezionato
        $evasiNelPeriodo = DB::table('wf_document_validations')
            ->where('reparto', 'Qualita')
            ->where('stato', 'ORDINE-OK')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        // 5. CALCOLO DEI TEMPI MEDI (Lead Time del periodo sui completati)
        $tempiMedi = DB::table('wf_document_validations')
            ->where('reparto', 'Qualita')
            ->where('stato', 'ORDINE-OK')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->select([
                // Differenza in minuti tra la presa in carico (created_at) e la chiusura (updated_at)
                DB::raw("AVG(DATEDIFF(minute, created_at, updated_at)) / 60.0 as media_ore_controllo")
            ])
            ->first();

        // 6. PRODUTTIVITÀ DEL TEAM (Filtrata per data)
        $produttivitaOperatori = DB::table('wf_document_validations')
            ->join('users', 'users.id', '=', 'wf_document_validations.user_id')
            ->where('wf_document_validations.reparto', 'Qualita')
            ->whereBetween('wf_document_validations.updated_at', [$startDate, $endDate])
            ->select([
                'users.id as user_id',
                'users.full_name as operatore_nome',
                DB::raw("COUNT(CASE WHEN wf_document_validations.stato = 'DDC-OK' THEN 1 END) as avanzamenti_fase1"),
                DB::raw("COUNT(CASE WHEN wf_document_validations.stato = 'ORDINE-OK' THEN 1 END) as chiusure_fase2"),
                DB::raw("COUNT(wf_document_validations.id) as azioni_totali")
            ])
            ->groupBy('users.id', 'users.full_name')
            ->orderBy('azioni_totali', 'desc')
            ->get();

        // 7. TREND GIORNALIERO DELLE CHIUSURE (Filtrato per data)
        $trendGiornaliero = DB::table('wf_document_validations')
            ->where('reparto', 'Qualita')
            ->where('stato', 'ORDINE-OK')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->select([
                DB::raw("CONVERT(VARCHAR(10), updated_at, 120) as data_giorno"),
                DB::raw("COUNT(id) as documenti_completati")
            ])
            ->groupBy(DB::raw("CONVERT(VARCHAR(10), updated_at, 120)"))
            ->orderBy('data_giorno', 'asc')
            ->get();

        // 8. COSTRUZIONE STRUTTURA DATI PER LA DASHBOARD
        // La coda reale "Da Fare" è formata principalmente dai documenti che non hanno ancora una riga
        $daFareTotale  = (int) $documentiSenzaValidazione + (int) ($codaInValidazione->da_fare_iniziali ?? 0);
        $inCorsoTotale = (int) ($codaInValidazione->in_corso ?? 0) - (int) ($codaInValidazione->da_fare_iniziali ?? 0);
        $completati    = (int) $evasiNelPeriodo;

        // Totale delle pratiche che gravitano attorno all'intervallo operativo
        $totaleLavorabile = $daFareTotale + $inCorsoTotale + $completati;

        return response()->json([
            'periodo' => [
                'inizio' => $startDate,
                'fine'   => $endDate
            ],
            'volumi' => [
                'totale_ricevuti' => $totaleLavorabile,
                'da_fare'         => $daFareTotale,     // Mostrerà i 15.459+ documenti arretrati
                'in_corso'        => max(0, $inCorsoTotale),  // Pratiche attualmente in Fase 1
                'completati'      => $completati,      // Documenti chiusi nel periodo (es. 1)
                'tasso_completamento_percentuale' => $totaleLavorabile > 0 ? round(($completati / $totaleLavorabile) * 100, 1) : 0
            ],
            'efficienza_ore' => [
                'attesa_media'     => 0, // Eventualmente calcolabile integrando i tempi di smistamento
                'controllo_medio'  => $tempiMedi->media_ore_controllo ? round((float)$tempiMedi->media_ore_controllo, 2) : 0,
                'lead_time_totale' => $tempiMedi->media_ore_controllo ? round((float)$tempiMedi->media_ore_controllo, 2) : 0
            ],
            'operatori' => $produttivitaOperatori,
            'trend'     => $trendGiornaliero
        ]);
    }


    /**
     * Lettura degli stati per i semafori/righe verdi in Vue3
     */
    public function checkStatus(Request $request): JsonResponse
    {
        $model = $request->query('model');
        $modelId = $request->query('model_id');

        $documents = WfDocument::where('model', $model)
            ->where('model_id', $modelId)
            ->whereIn('tipologia', [1, 2])
            ->get();

        $idoneita = $documents->firstWhere('tipologia', 1);
        $giudizio = $documents->firstWhere('tipologia', 2);

        $idoneitaValidata = $idoneita
            ? WfDocumentValidation::where('wf_document_id', $idoneita->id)
                ->where('reparto', 'Qualita')
                ->where('stato', 'ORDINE-OK')
                ->first()
            : null;

        $giudizioValidato = $giudizio
            ? WfDocumentValidation::where('wf_document_id', $giudizio->id)
                ->where('reparto', 'Qualita')
                ->where('stato', 'ORDINE-OK')
                ->first()
            : null;

        return response()->json([
            'idoneita' => [
                'presente' => !is_null($idoneita),
                'valido' => !is_null($idoneitaValidata),
                'validato_da' => $idoneitaValidata?->user_id,
                'data_validazione' => $idoneitaValidata?->updated_at
            ],
            'giudizio' => [
                'presente' => !is_null($giudizio),
                'valido' => !is_null($giudizioValidato),
                'validato_da' => $giudizioValidato?->user_id,
                'data_validazione' => $giudizioValidato?->updated_at
            ],
            'riga_completa' => (!is_null($idoneitaValidata) && !is_null($giudizioValidato))
        ]);
    }
}
