<?php

namespace App\Http\Controllers;

use App\Models\WfDocument;
use App\Models\WfDocumentValidation;
use App\Models\WfOrder;
use App\Services\GoogleDrive;
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

        // QUERY BASE: Tutti i DDT (tipologia=20) con validazione e DDC associato
        // Usiamo whereRaw con valori hardcoded per evitare conflitti di mergeBindings con SQL Server
        $subquery55Sql = "select d.id, d.id_file_drive, d.riferimento, ROW_NUMBER() OVER (PARTITION BY d.riferimento ORDER BY d.id) as rn from wf_documents as d where d.tipologia = 55";
        $subquery1Sql = "select d.id, d.id_file_drive, d.riferimento, ROW_NUMBER() OVER (PARTITION BY d.riferimento ORDER BY d.id) as rn from wf_documents as d where d.tipologia = 1";
        // Subquery per wf_orders: una sola riga per commessa (evita duplicati)
        $subqueryOrdersSql = "select o.id, o.commessa, o.id_file_drive, ROW_NUMBER() OVER (PARTITION BY o.commessa ORDER BY o.id) as rn from wf_orders as o";

        // Determina il filtro tipologia
        $tipologiaId = 20;
        if ($tipologia) {
            $tipologiaId = ($tipologia === 'IdoneitaDatore') ? 1 : 2;
        }

        $query = DB::table('wf_documents')
            ->whereRaw("wf_documents.model = 'WfOrder'")
            ->whereNotNull('wf_documents.id_file_drive')
            ->whereRaw("wf_documents.tipologia = {$tipologiaId}")
            ->leftJoin('wf_document_validations', function ($join) {
                $join->on('wf_document_validations.wf_document_id', '=', 'wf_documents.id')
                    ->whereRaw("wf_document_validations.reparto = 'Qualita'");
            })
            ->leftJoin('users', 'users.id', '=', 'wf_document_validations.user_id')
            ->leftJoin(DB::raw("({$subqueryOrdersSql}) as wf_orders"), function ($join) {
                $join->on('wf_orders.commessa', '=', 'wf_documents.riferimento')
                    ->whereRaw("wf_orders.rn = 1");
            })
            ->leftJoin(DB::raw("({$subquery55Sql}) as document_order_55"), function ($join) {
                $join->on('wf_orders.commessa', '=', 'document_order_55.riferimento')
                    ->whereRaw("document_order_55.rn = 1");
            })
            ->leftJoin(DB::raw("({$subquery1Sql}) as document_order_1"), function ($join) {
                $join->on('wf_orders.commessa', '=', 'document_order_1.riferimento')
                    ->whereRaw("document_order_1.rn = 1");
            })
            ->leftJoin('wf_documents as wf_documents_ddc', function ($join) {
                $join->on('wf_documents_ddc.id', '=', 'wf_document_validations.wf_document_id_ddc');
            })
            ->select([
                'wf_documents.id',
                'wf_documents.model',
                'wf_documents.model_id',
                'wf_documents.tipologia',
                'wf_documents.nome_file',
                'wf_documents.id_file_drive',
                DB::raw("COALESCE(document_order_55.id_file_drive, document_order_1.id_file_drive, wf_orders.id_file_drive) as id_file_drive_commessa"),
                DB::raw("COALESCE(document_order_55.id, document_order_1.id) as id_document_order"),
                'wf_documents_ddc.id_file_drive as id_file_drive_ddc',
                'wf_documents.riferimento',
                'wf_documents.created_at',
                DB::raw("COALESCE(wf_document_validations.stato, 'DA-FARE') as stato"),
                'users.full_name as eseguito_da'
            ]);

        // 1. Filtro Tipologia (già applicato sopra)

        // 2. Filtro Model ID
        if ($modelId) {
            $query->where('wf_documents.model_id', 'LIKE', "%{$modelId}%");
        }

        // 3. Filtro Stato
        if ($statoFiltro) {
            if ($statoFiltro === 'DA-FARE') {
                $query->whereRaw("(wf_document_validations.stato IS NULL OR wf_document_validations.stato = 'DA-FARE')");
            } else {
                $query->whereRaw("wf_document_validations.stato = '{$statoFiltro}'");
            }
        } else {
            $query->whereRaw("(wf_document_validations.stato IS NULL OR wf_document_validations.stato IN ('DA-FARE', 'DDC-OK', 'ORDINE-OK'))");
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

        // 2. DOCUMENTI DDT (tipologia=20) SENZA VALIDAZIONE O IN STATO DA-FARE
        // Allineato con la logica di getDocumentsToValidate
        $documentiSenzaValidazione = DB::table('wf_documents')
            ->leftJoin('wf_document_validations', function($join) {
                $join->on('wf_documents.id', '=', 'wf_document_validations.wf_document_id')
                    ->where('wf_document_validations.reparto', '=', 'Qualita');
            })
            ->where('wf_documents.tipologia', 20)
            ->where('wf_documents.model', 'WfOrder')
            ->whereNotNull('wf_documents.id_file_drive')
            ->where(function($q) {
                $q->whereNull('wf_document_validations.id')
                  ->orWhere('wf_document_validations.stato', '=', 'DA-FARE');
            })
            ->count();

        // 3. DOCUMENTI IN FASE 1 (DDC-OK) - Indipendenti dalle date
        $inCorso = DB::table('wf_document_validations')
            ->where('reparto', 'Qualita')
            ->where('stato', 'DDC-OK')
            ->count();

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
        $daFareTotale  = (int) $documentiSenzaValidazione;
        $inCorsoTotale = (int) $inCorso;
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
                'da_fare'         => $daFareTotale,
                'in_corso'        => max(0, $inCorsoTotale),
                'completati'      => $completati,
                'tasso_completamento_percentuale' => $totaleLavorabile > 0 ? round(($completati / $totaleLavorabile) * 100, 1) : 0
            ],
            'efficienza_ore' => [
                'attesa_media'     => 0,
                'controllo_medio'  => $tempiMedi->media_ore_controllo ? round((float)$tempiMedi->media_ore_controllo, 2) : 0,
                'lead_time_totale' => $tempiMedi->media_ore_controllo ? round((float)$tempiMedi->media_ore_controllo, 2) : 0
            ],
            'operatori' => $produttivitaOperatori,
            'trend'     => $trendGiornaliero
        ]);
    }


    /**
     * Scarica i DDC selezionati da Google Drive e li restituisce come array base64 per il merge lato client.
     */
    public function printDdcBulk(Request $request): JsonResponse
    {
        $request->validate([
            'drive_ids'   => 'required|array|min:1|max:20',
            'drive_ids.*' => 'string',
        ]);

        $driveIds = $request->input('drive_ids');
        $pdfs = [];

        try {
            foreach ($driveIds as $fileId) {
                if (empty($fileId)) {
                    continue;
                }

                $content = GoogleDrive::download($fileId, 'quality_ddc_drive');

                if (empty($content)) {
                    Log::warning("[printDdcBulk] Impossibile scaricare il file DDC: {$fileId}");
                    continue;
                }

                $pdfs[] = [
                    'id'   => $fileId,
                    'data' => base64_encode($content),
                ];
            }

            if (empty($pdfs)) {
                return response()->json(['error' => 'Nessun DDC valido trovato per la stampa.'], 422);
            }

            return response()->json([
                'success' => true,
                'count'   => count($pdfs),
                'pdfs'    => $pdfs,
            ]);

        } catch (\Exception $e) {
            Log::error("[printDdcBulk] Errore: " . $e->getMessage());

            return response()->json(['error' => 'Errore durante il download dei DDC.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Restituisce i dettagli di una commessa: file commessa, ultima revisione e tutti i documenti QT (tipologia 1, 3, 55).
     */
    public function getCommessaDetails(Request $request): JsonResponse
    {
        $request->validate([
            'riferimento' => 'required|string',
        ]);

        $riferimento = $request->query('riferimento');

        $documents = WfDocument::where('riferimento', $riferimento)
            ->whereIn('tipologia', [1, 3, 55])
            ->whereNotNull('id_file_drive')
            ->orderBy('tipologia', 'asc')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'nome_file', 'tipologia', 'id_file_drive', 'created_at']);

        $order = WfOrder::where('commessa', $riferimento)
            ->orderBy('tipologia', 'desc')
            ->first(['id', 'commessa', 'revisione', 'tipologia', 'stato', 'data_approvazione']);

        $tipologiaLabels = [
            1  => 'Commessa',
            3  => 'Revisione',
            55 => 'QT 55',
        ];

        $documentsFormatted = $documents->map(function ($doc) use ($tipologiaLabels) {
            return [
                'id'              => $doc->id,
                'nome_file'       => $doc->nome_file,
                'tipologia'       => $doc->tipologia,
                'tipologia_label' => $tipologiaLabels[$doc->tipologia] ?? "Tipo {$doc->tipologia}",
                'id_file_drive'   => $doc->id_file_drive,
                'created_at'      => $doc->created_at?->format('d/m/Y H:i'),
            ];
        });

        $lastRevision = $documents->firstWhere('tipologia', 3);
        $commessaFile = $documents->firstWhere('tipologia', 1);

        return response()->json([
            'commessa'      => $riferimento,
            'order'         => $order ? [
                'id'               => $order->id,
                'revisione'        => $order->revisione,
                'stato'            => $order->stato,
                'tipologia'        => $order->tipologia,
                'data_approvazione'=> $order->data_approvazione?->format('d/m/Y'),
            ] : null,
            'commessa_file' => $commessaFile ? [
                'nome_file'     => $commessaFile->nome_file,
                'id_file_drive' => $commessaFile->id_file_drive,
            ] : null,
            'last_revision' => $lastRevision ? [
                'nome_file'     => $lastRevision->nome_file,
                'id_file_drive' => $lastRevision->id_file_drive,
                'created_at'    => $lastRevision->created_at?->format('d/m/Y H:i'),
            ] : null,
            'documents'     => $documentsFormatted,
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
