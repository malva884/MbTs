<?php

namespace App\Http\Controllers;

use App\Models\QtFai;
use App\Services\GoogleDrive;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class QtFaiController extends Controller
{
    /**
     * Elenco completo dei FAI.
     */
    public function index(Request $request)
    {
        // Recupera i filtri inviati dalla tabella Vue
        $query = QtFai::latest();

        if ($request->has('ol')) {
            $query->where('ol', 'like', '%' . $request->ol . '%');
        }

        if ($request->has('articolo')) {
            $query->where('articolo', 'like', '%' . $request->articolo . '%');
        }

        // Pagina i risultati (es. 10 per volta, o dinamico in base a itemsPerPage)
        $perPage = $request->input('itemsPerPage', 10);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Inserimento di un nuovo FAI con gestione file specifica su Google Drive.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'data_inizio'        => 'required|date',
            'descrizione'        => 'required|string',
            'esito_fattibilita'  => 'nullable|string',
            'soggetto'           => 'required|string',
            'articolo'           => 'required|string',
            'file_specifica'     => 'nullable|file|max:10240',
            'ol'                 => 'required|string',
            'prove'              => 'required|array',
        ]);

        $data = $request->except('file_specifica');
        $data['specifica'] = null;
        $data['specifica_id'] = null;

        // 1. L'Observer imposta solo codice, esito e crea la cartella FAI recuperando il drive_id
        $qtFai = QtFai::create($data);

        // 2. Forza Laravel a ricaricare il record per avere il drive_id generato dall'Observer
        $qtFai->refresh();

        // 3. Gestione del file di specifica (se presente)
        if ($request->hasFile('file_specifica') && $qtFai->drive_id) {
            $file = $request->file('file_specifica');
            $originalName = $file->getClientOriginalName();

            $driveResponse = GoogleDrive::add_file(
                $qtFai->drive_id,
                $originalName,
                $file->getRealPath(),
                true,
                'google'
            );

            Log::info("=== RISPOSTA DRIVE ===", ['risposta' => $driveResponse]);

            $driveFileId = null;
            if (is_string($driveResponse)) {
                $driveFileId = $driveResponse;
            } elseif (is_array($driveResponse) && isset($driveResponse['id'])) {
                $driveFileId = $driveResponse['id'];
            } elseif (is_object($driveResponse) && isset($driveResponse->id)) {
                $driveFileId = $driveResponse->id;
            }

            // AGGIORNA l'istanza con il nome della specifica
            $qtFai->update([
                'specifica' => $originalName,
                'specifica_id' => $driveFileId
            ]);

            // Sincronizza l'oggetto in memoria con i nuovi dati appena salvati
            $qtFai->refresh();
        }

        // 4. GENERAZIONE DEL PDF DI RIEPILOGO (Ora ha la "specifica" aggiornata!)
        if ($qtFai->drive_id) {
            try {
                $nomiProve = [];
                if (!empty($qtFai->prove) && is_array($qtFai->prove)) {
                    $nomiProve = \App\Models\QtCategorie::whereIn('id', $qtFai->prove)
                        ->pluck('categoria')
                        ->toArray();
                }

                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.fai_riepilogo', [
                    'fai' => $qtFai,
                    'nomiProve' => $nomiProve
                ]);

                $tempPath = storage_path('app/public/temp_' . $qtFai->codice . '.pdf');
                file_put_contents($tempPath, $pdf->output());

                $nomeFilePdf = 'Riepilogo_' . $qtFai->codice . '.pdf';

                // Carica il PDF definitivo su Google Drive
                GoogleDrive::add_file($qtFai->drive_id, $nomeFilePdf, $tempPath, false, 'google');

                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }
            } catch (\Exception $e) {
                Log::error("Errore durante la generazione del PDF nel Controller: " . $e->getMessage());
            }
        }

        return response()->json($qtFai, 201);
    }

    /**
     * Visualizzazione del dettaglio di un singolo FAI.
     */
    public function show($id)
    {
        Log::info("=== INIZIO DEBUG SHOW FAI PER ID: {$id} ===");

        // 1. Recupera il FAI master (include i nuovi campi descrizione, specifica e specifica_id)
        $fai = QtFai::findOrFail($id);

        // LOG STEP 1: Verifica del contenuto del piano originale
        Log::info("STEP 1 - Campo 'prove' grezzo sul DB FAI:", ['prove_raw' => $fai->prove]);

        // Forza il recupero delle prove in piano come array pulito di stringhe
        $proveUfficialiIds = is_string($fai->prove) ? json_decode($fai->prove, true) : ($fai->prove ?? []);
        $proveUfficialiIds = is_array($proveUfficialiIds) ? array_map('strval', $proveUfficialiIds) : [];

        Log::info("STEP 1.1 - Array prove convertito e pulito (Stringhe):", $proveUfficialiIds);

        // 2. Recupera i test reali presenti su DB
        $testEseguitiRaw = DB::table('qt_type_tests as t')
            ->where('t.fai', $id)
            ->leftJoin('qt_categories as c', 't.tipo', '=', 'c.id')
            ->leftJoin('users as u', 't.user', '=', 'u.id')
            ->select([
                't.id',
                't.tipo as tipo_id',
                DB::raw("COALESCE(c.categoria, t.standard, 'Prova Extra / Non in Lista') as nome_prova"),
                't.esito',
                't.data_prova',
                't.path_drive',
                't.note',
                'u.full_name as operatore'
            ])
            ->orderBy('t.data_prova', 'desc')
            ->get();

        Log::info("STEP 2 - Numero di test reali trovati su qt_type_tests: " . $testEseguitiRaw->count());

        // 3. Mappa i test reali determinando se sono "in piano"
        $idTestEseguiti = [];
        $testEseguiti = $testEseguitiRaw->map(function ($test) use ($proveUfficialiIds, &$idTestEseguiti) {
            $tipoIdStr = strval($test->tipo_id);

            if (!empty($tipoIdStr)) {
                $idTestEseguiti[] = $tipoIdStr;
            }

            return [
                'id'          => $test->id,
                'tipo_id'     => $test->tipo_id,
                'nome_prova'  => $test->nome_prova,
                'esito'       => $test->esito,
                'data_prova'  => $test->data_prova,
                'path_drive'  => $test->path_drive,
                'note'        => $test->note,
                'operatore'   => $test->operatore,
                'in_piano'    => in_array($tipoIdStr, $proveUfficialiIds),
                'mancante'    => false
            ];
        });

        $idTestEseguiti = array_unique($idTestEseguiti);
        Log::info("STEP 2.1 - Elenco dei 'tipo_id' reali presenti (Stringhe):", $idTestEseguiti);

        // 4. Trova i codici mancanti (Confronto matematico sicuro)
        $idsMancanti = array_diff($proveUfficialiIds, $idTestEseguiti);
        Log::info("STEP 3 - Risultato di array_diff (Mancanti calcolati):", $idsMancanti);

        $testMancanti = collect();

        if (!empty($idsMancanti)) {
            // Interroghiamo la tabella anagrafica indicizzandola per ID
            $categorieEsistenti = DB::table('qt_categories')
                ->whereIn('id', $idsMancanti)
                ->get()
                ->keyBy('id');

            Log::info("STEP 3.1 - Corrispondenze reali trovate su qt_categories: " . $categorieEsistenti->count());

            // Ciclo fail-safe pilotato direttamente dagli ID mancanti
            foreach ($idsMancanti as $idMancante) {

                // Se l'ID esiste usiamo la sua descrizione, altrimenti usiamo il codice numerico come fallback
                $nomeProvaMancante = isset($categorieEsistenti[$idMancante])
                    ? $categorieEsistenti[$idMancante]->categoria
                    : "Prova Prevista (ID #" . $idMancante . ")";

                $testMancanti->push([
                    'id'          => 'missing-' . $idMancante,
                    'tipo_id'     => $idMancante,
                    'nome_prova'  => $nomeProvaMancante,
                    'esito'       => 'MANCANTE',
                    'data_prova'  => null,
                    'path_drive'  => null,
                    'note'        => 'Prova richiesta nel piano iniziale FAI ma non ancora registrata a sistema',
                    'operatore'   => null,
                    'in_piano'    => true,
                    'mancante'    => true
                ]);
            }
        }

        Log::info("STEP 3.2 - Totale record fittizi 'mancanti' pronti: " . $testMancanti->count());

        // 5. Unione finale e ordinamento delle collezioni
        $collezioneFinale = $testMancanti->concat($testEseguiti)->sort(function ($a, $b) {
            // Regola 1: le prove mancanti (rosse) vanno tassativamente in cima a tutto
            if ($a['mancante'] && !$b['mancante']) return -1;
            if (!$a['mancante'] && $b['mancante']) return 1;

            // Regola 2: se presenti entrambi, diamo priorità a quelli "In Piano" rispetto alle prove "Extra"
            if ($a['in_piano'] && !$b['in_piano']) return -1;
            if (!$a['in_piano'] && $b['in_piano']) return 1;

            // Regola 3: ordina in ordine cronologico decrescente (dalla più recente alla più vecchia)
            return strcmp($b['data_prova'] ?? '', $a['data_prova'] ?? '');
        })->values();

        Log::info("STEP 4 - Primi 3 elementi del payload finale:", array_slice($collezioneFinale->toArray(), 0, 3));
        Log::info("=== FINE DEBUG SHOW FAI ===");

        return response()->json([
            'fai'   => $fai,
            'prove' => $collezioneFinale
        ]);
    }

    /**
     * Modifica e Avanzamento Stato del FAI con eventuale aggiornamento file Specifica.
     */
    public function update(Request $request, $id)
    {
        // 1. Validazione dell'esito inviato da Vue
        $validated = $request->validate([
            'esito' => 'required|string|in:POSITIVO,NEGATIVO'
        ]);

        // Recuperiamo il record FAI Master
        $qtFai = QtFai::findOrFail($id);

        // 2. Aggiorna l'esito sul database (questo aggiorna automaticamente il campo updated_at)
        $qtFai->update([
            'esito' => $validated['esito']
        ]);

        // 3. Rigenerazione del PDF su Google Drive
        if ($qtFai->drive_id) {
            try {
                $disk = 'google';
                $nomeFilePdf = 'Riepilogo_' . $qtFai->codice . '.pdf';

                // --- ELIMINAZIONE VECCHIO PDF ---
                $oldFileId = GoogleDrive::search($qtFai->drive_id, $disk, 'file', $nomeFilePdf, false);
                if ($oldFileId) {
                    Log::info("Eliminazione vecchio PDF di riepilogo FAI su Drive. ID: " . $oldFileId);
                    GoogleDrive::delated($oldFileId, $disk);
                }

                // --- 1. RECUPERO IL PIANO PROVE ORIGINALE (NOMI DELLE CATEGORIE) ---
                $provePianificate = [];
                if (!empty($qtFai->prove) && is_array($qtFai->prove)) {
                    $provePianificate = DB::table('qt_categories')
                        ->whereIn('id', $qtFai->prove)
                        ->pluck('categoria', 'id')
                        ->toArray();
                }

                // --- 2. QUERY DELLA SHOW PER I TEST ESEGUITI ---
                $testEseguitiRaw = DB::table('qt_type_tests as t')
                    ->where('t.fai', $qtFai->id)
                    ->leftJoin('qt_categories as c', 't.tipo', '=', 'c.id')
                    ->leftJoin('users as u', 't.user', '=', 'u.id')
                    ->select([
                        't.id',
                        't.tipo as tipo_id',
                        DB::raw("COALESCE(c.categoria, t.standard, 'Prova Extra / Non in Lista') as nome_prova"),
                        't.esito',
                        't.data_prova',
                        't.path_drive',
                        't.note',
                        'u.full_name as operatore'
                    ])
                    ->orderBy('t.data_prova', 'desc')
                    ->get();

                // Indicizziamo per tipo_id per fare il match con il piano FAI
                $testEseguitiMappati = $testEseguitiRaw->keyBy('tipo_id');

                $proveCompleto = [];
                $visteOttenuteIds = [];

                // --- 3. COSTRUZIONE LISTA PROVE (LOGICA SHOW) ---

                // Fase A: Inseriamo tutte le prove pianificate
                foreach ($provePianificate as $tipoId => $nomeCategoria) {
                    if ($testEseguitiMappati->has($tipoId)) {
                        $testReale = $testEseguitiMappati->get($tipoId);

                        $proveCompleto[] = (object)[
                            'nome_prova' => $testReale->nome_prova,
                            'esito'      => $testReale->esito ?: 'NON VALUTATO',
                            'data_prova' => $testReale->data_prova,
                            'operatore'  => $testReale->operatore ?: 'Non assegnato'
                        ];
                        $visteOttenuteIds[] = $tipoId;
                    } else {
                        $proveCompleto[] = (object)[
                            'nome_prova' => $nomeCategoria,
                            'esito'      => 'MANCANTE',
                            'data_prova' => null,
                            'operatore'  => 'Non assegnato'
                        ];
                    }
                }

                // Fase B: Includiamo anche i test eseguiti Extra/Fuori piano
                foreach ($testEseguitiRaw as $test) {
                    if (!in_array($test->tipo_id, $visteOttenuteIds)) {
                        $proveCompleto[] = (object)[
                            'nome_prova' => $test->nome_prova,
                            'esito'      => $test->esito ?: 'NON VALUTATO',
                            'data_prova' => $test->data_prova,
                            'operatore'  => $test->operatore ?: 'Non assegnato'
                        ];
                    }
                }

                // --- 4. CARICAMENTO DEL TEMPLATE BLADE ---
                $pdf = Pdf::loadView('pdf.fai_riepilogo', [
                    'fai'           => $qtFai,
                    'nomiProve'     => array_values($provePianificate),
                    'proveCompleto' => $proveCompleto
                ]);

                // Percorso locale temporaneo
                $tempPath = storage_path('app/public/temp_update_' . $qtFai->codice . '.pdf');
                file_put_contents($tempPath, $pdf->output());

                // Spediamo il file rigenerato su Google Drive
                GoogleDrive::add_file($qtFai->drive_id, $nomeFilePdf, $tempPath, false, $disk);

                // Pulizia locale
                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }

                Log::info("Nuovo PDF FAI Rigenerato con successo (Logica Show + Data Chiusura).");

            } catch (\Exception $e) {
                Log::error("Errore durante la rigenerazione del PDF FAI: " . $e->getMessage());
            }
        }

        return response()->json($qtFai, 200);
    }

    /**
     * Caricamento manuale di documenti/allegati nella cartella Drive del FAI.
     */
    public function uploadDocument(Request $request, $id)
    {
        $qtFai = QtFai::findOrFail($id);

        $request->validate([
            'documento' => 'required|file|max:10240', // Limite impostato a 10MB
        ]);

        if (!$qtFai->drive_id) {
            return response()->json([
                'error' => 'Impossibile caricare i file. Cartella Google Drive non associata a questo FAI.'
            ], 422);
        }

        // Recuperiamo l'estensione del file originale per mantenere la consistenza del nome
        $originalExtension = $request->file('documento')->getClientOriginalExtension();
        $cleanFileName = pathinfo($request->file('documento')->getClientOriginalName(), PATHINFO_FILENAME);

        // Generiamo un nome univoco (es. 1717400000_Certificato_Qualita.pdf)
        $finalName = time() . '_' . $cleanFileName . '.' . $originalExtension;

        // Sfruttiamo il tuo metodo add_file passando direttamente l'oggetto $request completo
        GoogleDrive::add_file(
            $qtFai->drive_id,
            $finalName,
            $request,
            false,
            'google'
        );

        return response()->json([
            'success' => true,
            'message' => 'Documento caricato correttamente nella cartella Google Drive.'
        ]);
    }
}
