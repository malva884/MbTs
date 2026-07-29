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
use Illuminate\Support\Facades\Storage;

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

        // 6. Ordinamento: per stato (DA-FARE → DDC-OK → ORDINE-OK), poi per data
        $documentsToValidate = $query
            ->orderByRaw("CASE COALESCE(wf_document_validations.stato, 'DA-FARE')
                WHEN 'DA-FARE' THEN 0
                WHEN 'DDC-OK' THEN 1
                WHEN 'ORDINE-OK' THEN 2
                ELSE 3 END ASC")
            ->orderBy('wf_documents.created_at', 'desc')
            ->paginate($perPage);

        // Conta il totale delle pratiche da processare (escluse ORDINE-OK) senza paginazione
        $totaleDaProcessare = DB::table('wf_documents')
            ->whereRaw("wf_documents.model = 'WfOrder'")
            ->whereNotNull('wf_documents.id_file_drive')
            ->whereRaw("wf_documents.tipologia = {$tipologiaId}")
            ->leftJoin('wf_document_validations', function ($join) {
                $join->on('wf_document_validations.wf_document_id', '=', 'wf_documents.id')
                    ->whereRaw("wf_document_validations.reparto = 'Qualita'");
            })
            ->whereRaw("(wf_document_validations.stato IS NULL OR wf_document_validations.stato IN ('DA-FARE', 'DDC-OK'))")
            ->count();

        $data = $documentsToValidate->toArray();
        $data['pratiche_da_processare'] = $totaleDaProcessare;

        return response()->json($data);
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

    /**
     * Lista i file PDF presenti sul disco quality_pdf_drive (cartella root o DDT/processing).
     */
    public function listQualityPdfFiles(Request $request): JsonResponse
    {
        $folder = $request->query('folder', '');
        $disk = Storage::disk('quality_pdf_drive');

        $files = $disk->files($folder);

        $result = [];
        foreach ($files as $file) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf') {
                $result[] = [
                    'path' => $file,
                    'name' => basename($file),
                ];
            }
        }

        // Se siamo nella root, aggiungi anche i file in DDT/processing
        if ($folder === '') {
            if ($disk->exists('DDT/processing')) {
                $processingFiles = $disk->files('DDT/processing');
                foreach ($processingFiles as $file) {
                    if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf') {
                        $result[] = [
                            'path' => $file,
                            'name' => basename($file),
                            'folder' => 'DDT/processing',
                        ];
                    }
                }
            }
        }

        return response()->json(['files' => $result]);
    }

    /**
     * Test dry-run: analizza un file PDF con lo stesso flusso del job ProcessQualityPdf
     * ma senza modificare/eliminare nulla. Restituisce tutti gli step.
     */
    public function testProcessPdf(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $filePath = $request->input('path');
        $disk = Storage::disk('quality_pdf_drive');
        $steps = [];

        // Step 1: verifica esistenza file
        $steps[] = ['step' => 'Verifica file su Drive', 'status' => $disk->exists($filePath) ? 'ok' : 'error', 'detail' => $disk->exists($filePath) ? "File trovato: {$filePath}" : "File NON trovato: {$filePath}"];

        if (!$disk->exists($filePath)) {
            return response()->json(['steps' => $steps, 'error' => 'File non trovato su Drive'], 404);
        }

        // Step 2: download temporaneo
        $contenuto = $disk->get($filePath);
        $nomeFile = basename($filePath);
        $tempName = 'temp_test_' . time() . '_' . $nomeFile;
        $percorsoTempLocale = storage_path('app/' . $tempName);
        Storage::disk('local')->put($tempName, $contenuto);

        $steps[] = ['step' => 'Download da Drive', 'status' => 'ok', 'detail' => "Scaricato " . strlen($contenuto) . " bytes"];

        try {
            // Step 3a: chiamata Gemini per debug (descrizione contenuto)
            $steps[] = ['step' => 'Debug Gemini - descrizione contenuto', 'status' => 'pending', 'detail' => 'In corso...'];

            $promptDebug = 'Analizza questo PDF pagina per pagina. Per OGNI pagina, rispondi:
1. Numero pagina
2. Trovi la dicitura "DOCUMENTO DI TRASPORTO"? (si/no)
3. Trovi un numero di commessa di 10 cifre che inizia con 46? Se si, quale?
4. Trovi un numero di DDT di 10 cifre che inizia con 516? Se si, quale?
5. Trovi indicazioni di paginazione tipo "1/2", "1/3"? Se si, quale?
6. Descrivi brevemente cosa vedi nella pagina (testo principale, tabelle, immagini).

Rispondi in testo libero, una pagina per riga.';

            $geminiService = new \App\Services\GeminiAiService();
            $rispostaDebug = $geminiService->analizzaFile(
                filePath: $percorsoTempLocale,
                prompt: $promptDebug,
                mimeType: 'application/pdf'
            );

            $steps[] = [
                'step' => 'Debug Gemini - descrizione contenuto',
                'status' => $rispostaDebug ? 'ok' : 'error',
                'detail' => $rispostaDebug ? substr($rispostaDebug, 0, 3000) : 'Nessuna risposta da Gemini',
            ];

            // Step 3b: chiamata Gemini per estrazione strutturata
            $steps[] = ['step' => 'Chiamata Gemini (estrazione)', 'status' => 'pending', 'detail' => 'In corso...'];

            $promptCommessa = 'Sei un assistente di estrazione dati strutturati. Analizza i documenti forniti seguendo queste istruzioni tassative:

1. **Filtro Pagine e Riconoscimento**:
   * Una pagina e considerata la PRIMA pagina di un documento valido se contiene la dicitura "DOCUMENTO DI TRASPORTO" (puo apparire anche come "DOCUMENTO DI TRASPORTO DPR" o varianti simili contenenti "DOCUMENTO DI TRASPORTO") insieme a un Numero di Commessa (10 cifre che inizia con 46) e un Numero di DDT (10 cifre che inizia con 516).
   * La paginazione puo apparire in vari formati: "1/2", "1/3", "Pag. 1 / 1", "Pag. 1/2", ecc. Estrai numeratore e denominatore da qualsiasi formato. Se non e presente alcuna indicazione di paginazione, usa 1 per entrambi i campi.
   * Se una pagina riporta paginazione con totale maggiore di 1 (es. "1/3" o "Pag. 1/3"), le pagine fisiche immediatamente successive nel file fanno parte dello STESSO documento DDT e devono essere incluse in "documenti_validi" con gli stessi ddt e commessa, anche se non ripetono la dicitura "DOCUMENTO DI TRASPORTO". Inseriscile con "pagina_corrente" progressivo (2, 3, ecc.).
   * Se una pagina ha paginazione "1/1" o "Pag. 1 / 1", il documento e composto da una sola pagina. Le pagine successive sono documenti separati o pagine scartate.
   * Una pagina e da scartare SOLO se non appartiene ad alcun documento DDT valido (ne come prima pagina, ne come pagina successiva di un documento multi-pagina).

2. **Formato della Risposta**:
   * Restituisci esclusivamente un oggetto JSON con due liste distinte strutturato esattamente cosi:
     {
       "documenti_validi": [
         {"pagina": 1, "commessa": "4612345678", "ddt": "5161234567", "pagina_corrente": 1, "pagine_totali": 3},
         {"pagina": 2, "commessa": "4612345678", "ddt": "5161234567", "pagina_corrente": 2, "pagine_totali": 3},
         {"pagina": 3, "commessa": "4612345678", "ddt": "5161234567", "pagina_corrente": 3, "pagine_totali": 3},
         {"pagina": 4, "commessa": "4699999999", "ddt": "5169999999", "pagina_corrente": 1, "pagine_totali": 1}
       ],
       "pagine_scartate": [5]
     }
   * IMPORTANTE: una pagina non puo apparire sia in "documenti_validi" che in "pagine_scartate".
   * Se l\'intero file non contiene assolutamente nulla (nessun documento valido e nessuna pagina diversa), rispondi unicamente con la stringa: NON TROVATO.
   * Non includere i markdown del codice (no ```json o ```text), non aggiungere introduzioni, spiegazioni o testo di contorno. Sii totalmente sintetico.';

            $rispostaRaw = $geminiService->analizzaFile(
                filePath: $percorsoTempLocale,
                prompt: $promptCommessa,
                mimeType: 'application/pdf'
            );

            $steps[] = [
                'step' => 'Risposta Gemini (raw)',
                'status' => $rispostaRaw ? 'ok' : 'error',
                'detail' => $rispostaRaw ? substr($rispostaRaw, 0, 2000) : 'Nessuna risposta da Gemini',
            ];

            if (!$rispostaRaw) {
                $steps[] = ['step' => 'Decodifica JSON', 'status' => 'error', 'detail' => 'Gemini non ha restituito nulla'];
                Storage::disk('local')->delete($tempName);
                return response()->json(['steps' => $steps, 'error' => 'Gemini non ha risposto']);
            }

            $rispostaPulita = trim($rispostaRaw);

            if ($rispostaPulita === 'NON TROVATO') {
                $steps[] = ['step' => 'Risultato', 'status' => 'warning', 'detail' => 'Gemini ha risposto NON TROVATO — il file verrebbe archiviato come non_riconosciuto'];
                Storage::disk('local')->delete($tempName);
                return response()->json(['steps' => $steps]);
            }

            // Step 4: decodifica JSON
            $datiDecodificati = json_decode($rispostaPulita, true);
            $jsonError = json_last_error_msg();

            $steps[] = [
                'step' => 'Decodifica JSON',
                'status' => $jsonError === 'No error' ? 'ok' : 'error',
                'detail' => $jsonError !== 'No error' ? "Errore JSON: {$jsonError}" : 'JSON valido',
            ];

            if ($jsonError !== 'No error') {
                Storage::disk('local')->delete($tempName);
                return response()->json(['steps' => $steps, 'error' => 'JSON non valido']);
            }

            // Step 5: analisi documenti validi
            $documentiValidi = $datiDecodificati['documenti_validi'] ?? [];
            $pagineScartate = $datiDecodificati['pagine_scartate'] ?? [];

            $steps[] = [
                'step' => 'Documenti validi estratti',
                'status' => count($documentiValidi) > 0 ? 'ok' : 'warning',
                'detail' => count($documentiValidi) . ' documenti trovati',
                'data' => $documentiValidi,
            ];

            $steps[] = [
                'step' => 'Pagine scartate',
                'status' => count($pagineScartate) > 0 ? 'warning' : 'ok',
                'detail' => count($pagineScartate) . ' pagine scartate',
                'data' => $pagineScartate,
            ];

            // Step 6: raggruppamento per DDT
            $gruppiDdt = [];
            foreach ($documentiValidi as $paginaDoc) {
                $chiave = ($paginaDoc['commessa'] ?? 'N/A') . '_' . ($paginaDoc['ddt'] ?? 'N/A');
                if (!isset($gruppiDdt[$chiave])) {
                    $gruppiDdt[$chiave] = [
                        'commessa' => $paginaDoc['commessa'] ?? 'N/A',
                        'ddt' => $paginaDoc['ddt'] ?? 'N/A',
                        'pagine' => [],
                    ];
                }
                $gruppiDdt[$chiave]['pagine'][] = $paginaDoc['pagina'] ?? 0;
            }

            $steps[] = [
                'step' => 'Raggruppamento DDT',
                'status' => 'ok',
                'detail' => count($gruppiDdt) . ' gruppi DDT',
                'data' => array_values($gruppiDdt),
            ];

            // Step 7: verifica workflow per ogni gruppo
            $workflowResults = [];
            foreach ($gruppiDdt as $gruppo) {
                $workflow = WfOrder::where('commessa', $gruppo['commessa'])->where('tipologia', 1)->first();
                $workflowResults[] = [
                    'commessa' => $gruppo['commessa'],
                    'ddt' => $gruppo['ddt'],
                    'pagine' => $gruppo['pagine'],
                    'workflow_trovato' => $workflow ? true : false,
                    'workflow_id' => $workflow?->id,
                    'folder_drive' => $workflow?->folder_drive,
                    'azione_prevista' => $workflow ? 'Caricamento su Drive' : 'Spostamento in DDT/processing',
                ];
            }

            $steps[] = [
                'step' => 'Verifica Workflow',
                'status' => 'ok',
                'detail' => count($workflowResults) . ' verifiche effettuate',
                'data' => $workflowResults,
            ];

            // Step 8: generazione PDF finali (come farebbe il job) ma restituiti come base64
            $pdfGenerati = [];
            try {
                foreach ($gruppiDdt as $gruppo) {
                    sort($gruppo['pagine']);

                    $pdfSingolo = new \setasign\Fpdi\Fpdi();
                    $pdfSingolo->setSourceFile($percorsoTempLocale);

                    foreach ($gruppo['pagine'] as $numPagina) {
                        $templateId = $pdfSingolo->importPage($numPagina);
                        $dimensioni = $pdfSingolo->getTemplateSize($templateId);
                        $pdfSingolo->AddPage($dimensioni['orientation'], [$dimensioni['width'], $dimensioni['height']]);
                        $pdfSingolo->useTemplate($templateId);
                    }

                    // Accoda le pagine scartate in fondo
                    if (!empty($pagineScartate)) {
                        foreach ($pagineScartate as $numPaginaScartata) {
                            $templateIdScartata = $pdfSingolo->importPage($numPaginaScartata);
                            $dimScartata = $pdfSingolo->getTemplateSize($templateIdScartata);
                            $pdfSingolo->AddPage($dimScartata['orientation'], [$dimScartata['width'], $dimScartata['height']]);
                            $pdfSingolo->useTemplate($templateIdScartata);
                        }
                    }

                    $nomeFileValido = $gruppo['ddt'] . ".pdf";
                    $output = $pdfSingolo->Output('S');

                    $pdfGenerati[] = [
                        'nome_file' => $nomeFileValido,
                        'commessa' => $gruppo['commessa'],
                        'ddt' => $gruppo['ddt'],
                        'pagine_valide' => $gruppo['pagine'],
                        'pagine_scartate_accodate' => $pagineScartate,
                        'base64' => base64_encode($output),
                    ];
                }

                $steps[] = [
                    'step' => 'Generazione PDF finali',
                    'status' => count($pdfGenerati) > 0 ? 'ok' : 'warning',
                    'detail' => count($pdfGenerati) . ' PDF generati (con pagine scartate accodate)',
                    'data' => array_map(fn($p) => ['nome_file' => $p['nome_file'], 'commessa' => $p['commessa'], 'ddt' => $p['ddt'], 'pagine_valide' => $p['pagine_valide'], 'pagine_scartate_accodate' => $p['pagine_scartate_accodate']], $pdfGenerati),
                    'pdfs' => $pdfGenerati,
                ];
            } catch (\Exception $fpdiEx) {
                $steps[] = ['step' => 'Generazione PDF finali', 'status' => 'error', 'detail' => 'Errore FPDI: ' . $fpdiEx->getMessage()];
            }

            $steps[] = ['step' => 'Risultato finale', 'status' => 'ok', 'detail' => 'Analisi dry-run completata. Nessun file è stato modificato o eliminato.'];

        } catch (\Exception $e) {
            $steps[] = ['step' => 'Errore', 'status' => 'error', 'detail' => $e->getMessage()];
        } finally {
            Storage::disk('local')->delete($tempName);
        }

        // Estrai i PDF base64 dalla risposta per il frontend
        $pdfsOutput = [];
        foreach ($steps as $step) {
            if (isset($step['pdfs'])) {
                $pdfsOutput = $step['pdfs'];
                break;
            }
        }

        return response()->json(['steps' => $steps, 'pdfs' => $pdfsOutput]);
    }
}
