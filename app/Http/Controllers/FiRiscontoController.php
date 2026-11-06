<?php

namespace App\Http\Controllers;

use App\Models\FiRisconto;
use App\Services\GoogleDrive;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FiRiscontoController extends Controller
{
    /**
     * Costante giorni mese medio contabile (365.25 / 12)
     */
    private const DAYS_PER_MONTH = 30.4375;

    /**
     * Elenco paginato dei risconti con statistiche aggregate
     */
    public function list(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $tipo = $request->get('tipo');
        $anno = $request->get('anno');
        $sortBy = $request->get('sortBy', 'id');
        $orderBy = $request->get('orderBy', 'desc');
        $itemsPerPage = (int) $request->get('itemsPerPage', 10);

        $sortableColumns = [
            'id', 'tipo', 'descrizione', 'importo_totale', 'data_inizio',
            'data_fine', 'data_chiusura_bilancio', 'giorni_totali',
            'giorni_competenza', 'giorni_futuri', 'quota_giornaliera',
            'quota_mensile', 'quota_competenza', 'importo_risconto', 'created_at',
        ];

        if (!in_array($sortBy, $sortableColumns, true)) {
            $sortBy = 'id';
        }
        if (!in_array(strtolower($orderBy), ['asc', 'desc'], true)) {
            $orderBy = 'desc';
        }

        $query = FiRisconto::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('descrizione', 'like', '%' . $search . '%')
                  ->orWhere('conto_dare', 'like', '%' . $search . '%')
                  ->orWhere('conto_avere', 'like', '%' . $search . '%')
                  ->orWhere('note', 'like', '%' . $search . '%');
            });
        }

        if (!empty($tipo) && in_array($tipo, ['ATTIVO', 'PASSIVO'], true)) {
            $query->where('tipo', $tipo);
        }

        if (!empty($anno)) {
            $query->where(function ($q) use ($anno) {
                $q->whereYear('data_chiusura_bilancio', $anno)
                  ->orWhereYear('data_inizio', $anno);
            });
        }

        // Calcolo delle statistiche aggregate sul set filtrato
        $statsQuery = clone $query;
        $stats = [
            'totale_attivi' => (float) (clone $statsQuery)->where('tipo', 'ATTIVO')->sum('importo_risconto'),
            'totale_passivi' => (float) (clone $statsQuery)->where('tipo', 'PASSIVO')->sum('importo_risconto'),
            'totale_importo' => (float) (clone $statsQuery)->sum('importo_totale'),
            'totale_competenza' => (float) (clone $statsQuery)->sum('quota_competenza'),
            'totale_mensile' => (float) (clone $statsQuery)->sum('quota_mensile'),
            'totale_risconto' => (float) (clone $statsQuery)->sum('importo_risconto'),
            'totale_operazioni' => (int) (clone $statsQuery)->count(),
        ];

        $paginated = $query->orderBy($sortBy, $orderBy)->paginate($itemsPerPage);

        return response()->json([
            'data' => $paginated->items(),
            'total' => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'per_page' => $paginated->perPage(),
            'last_page' => $paginated->lastPage(),
            'stats' => $stats,
        ]);
    }

    /**
     * Calcola in tempo reale la ripartizione pro-rata temporis e la partita doppia
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $this->validateRiscontoInput($request);
        $computed = $this->computeRisconto($validated);

        return response()->json([
            'success' => true,
            'data' => $computed,
        ]);
    }

    /**
     * Salva un nuovo risconto nel database eseguendo tutti i calcoli
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRiscontoInput($request);
        $computed = $this->computeRisconto($validated);

        $userId = auth()->id() ?? auth('sanctum')->id();

        $risconto = FiRisconto::create(array_merge($computed, [
            'note' => $request->input('note'),
            'user_id' => $userId,
        ]));

        $allegatoError = null;
        if ($request->hasFile('allegato')) {
            $allegatoError = $this->uploadAllegatoPdf($risconto, $request->file('allegato'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Risconto salvato con successo',
            'allegato_error' => $allegatoError,
            'data' => $risconto->fresh(),
        ], 201);
    }

    /**
     * Dettaglio singolo risconto con partita doppia
     */
    public function show($id): JsonResponse
    {
        $risconto = FiRisconto::findOrFail($id);

        $partitaDoppia = $this->buildPartitaDoppia(
            $risconto->tipo,
            $risconto->descrizione,
            $risconto->importo_risconto,
            $risconto->data_chiusura_bilancio->format('Y-m-d')
        );

        $data = $risconto->toArray();
        $data['partita_doppia'] = $partitaDoppia;

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Aggiorna un risconto esistente ricalcolando tutte le metriche
     */
    public function update(Request $request, $id): JsonResponse
    {
        $risconto = FiRisconto::findOrFail($id);
        $validated = $this->validateRiscontoInput($request);
        $computed = $this->computeRisconto($validated);

        $risconto->update(array_merge($computed, [
            'note' => $request->input('note'),
        ]));

        $allegatoError = null;

        // Rimozione esplicita dell'allegato (senza sostituzione)
        if ($request->boolean('allegato_delete') && !$request->hasFile('allegato') && $risconto->google_drive_file_id) {
            GoogleDrive::delated($risconto->google_drive_file_id);
            $risconto->update(['google_drive_file_id' => null, 'allegato_nome' => null]);
        }

        if ($request->hasFile('allegato')) {
            $allegatoError = $this->uploadAllegatoPdf($risconto, $request->file('allegato'));
        } elseif ($risconto->google_drive_file_id && $risconto->wasChanged('descrizione')) {
            // Mantiene la convenzione di naming risconto_{id}_{descrizione}.pdf
            try {
                $newName = $this->buildAllegatoFilename($risconto);
                GoogleDrive::rename_dir($risconto->google_drive_file_id, $newName);
                $risconto->update(['allegato_nome' => $newName]);
            } catch (\Throwable $e) {
                // Rinominazione non critica: il file resta accessibile col vecchio nome
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Risconto aggiornato con successo',
            'allegato_error' => $allegatoError,
            'data' => $risconto->fresh(),
        ]);
    }

    /**
     * Elimina un risconto
     */
    public function destroy($id): JsonResponse
    {
        $risconto = FiRisconto::findOrFail($id);

        // Elimina definitivamente il PDF da Google Drive
        if (!empty($risconto->google_drive_file_id)) {
            GoogleDrive::delated($risconto->google_drive_file_id);
        }

        $risconto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Risconto eliminato con successo',
        ]);
    }

    /**
     * Esportazione in formato CSV
     */
    public function export(Request $request): StreamedResponse
    {
        $search = $request->get('search');
        $tipo = $request->get('tipo');
        $anno = $request->get('anno');

        $query = FiRisconto::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('descrizione', 'like', '%' . $search . '%')
                  ->orWhere('conto_dare', 'like', '%' . $search . '%')
                  ->orWhere('conto_avere', 'like', '%' . $search . '%');
            });
        }

        if (!empty($tipo) && in_array($tipo, ['ATTIVO', 'PASSIVO'], true)) {
            $query->where('tipo', $tipo);
        }

        if (!empty($anno)) {
            $query->where(function ($q) use ($anno) {
                $q->whereYear('data_chiusura_bilancio', $anno)
                  ->orWhereYear('data_inizio', $anno);
            });
        }

        $records = $query->orderBy('data_inizio', 'asc')->get();

        $filename = 'risconti_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 per compatibilità Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID', 'Tipo', 'Descrizione', 'Importo Totale (€)',
                'Data Inizio', 'Data Fine', 'Data Chiusura Bilancio',
                'Giorni Totali', 'Giorni Competenza', 'Giorni Futuri',
                'Quota Giornaliera (€)', 'Quota Mensile (€)',
                'Quota Competenza (€)', 'Risconto (€)',
                '% Competenza', '% Risconto', 'Conto Dare', 'Conto Avere', 'Note',
            ], ';');

            foreach ($records as $item) {
                fputcsv($handle, [
                    $item->id,
                    $item->tipo,
                    $item->descrizione,
                    number_format($item->importo_totale, 2, ',', ''),
                    $item->data_inizio->format('d/m/Y'),
                    $item->data_fine->format('d/m/Y'),
                    $item->data_chiusura_bilancio->format('d/m/Y'),
                    $item->giorni_totali,
                    $item->giorni_competenza,
                    $item->giorni_futuri,
                    number_format($item->quota_giornaliera, 4, ',', ''),
                    number_format($item->quota_mensile, 2, ',', ''),
                    number_format($item->quota_competenza, 2, ',', ''),
                    number_format($item->importo_risconto, 2, ',', ''),
                    number_format($item->percentuale_competenza, 2, ',', ''),
                    number_format($item->percentuale_risconto, 2, ',', ''),
                    $item->conto_dare,
                    $item->conto_avere,
                    $item->note,
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Valida i parametri di input del form
     */
    private function validateRiscontoInput(Request $request): array
    {
        return $request->validate([
            'tipo' => 'required|in:ATTIVO,PASSIVO',
            'descrizione' => 'required|string|max:255',
            'importo_totale' => 'required|numeric|gt:0',
            'data_inizio' => 'required|date',
            'data_fine' => 'required|date|after:data_inizio',
            'data_chiusura_bilancio' => 'required|date|after_or_equal:data_inizio|before:data_fine',
            'allegato' => 'nullable|file|mimes:pdf|max:15360',
        ], [
            'tipo.required' => 'La tipologia di risconto è obbligatoria.',
            'tipo.in' => 'La tipologia deve essere ATTIVO o PASSIVO.',
            'descrizione.required' => 'La descrizione dell\'operazione è obbligatoria.',
            'importo_totale.required' => 'L\'importo totale è obbligatorio.',
            'importo_totale.gt' => 'L\'importo deve essere maggiore di zero.',
            'data_inizio.required' => 'La data di inizio è obbligatoria.',
            'data_fine.required' => 'La data di fine è obbligatoria.',
            'data_fine.after' => 'La data fine deve essere successiva alla data inizio.',
            'data_chiusura_bilancio.required' => 'La data di chiusura bilancio è obbligatoria.',
            'data_chiusura_bilancio.after_or_equal' => 'La data chiusura bilancio deve essere pari o successiva alla data inizio.',
            'allegato.mimes' => 'È possibile allegare solo file PDF.',
            'allegato.max' => 'Il PDF non può superare i 15 MB.',
            'data_chiusura_bilancio.before' => 'La data chiusura bilancio deve essere precedente alla data fine contratto affinché si generi un risconto.',
        ]);
    }

    /**
     * Esegue tutti i calcoli matematici e contabili pro-rata temporis
     */
    private function computeRisconto(array $data): array
    {
        $dStart = Carbon::parse($data['data_inizio'])->startOfDay();
        $dEnd = Carbon::parse($data['data_fine'])->startOfDay();
        $dClosing = Carbon::parse($data['data_chiusura_bilancio'])->startOfDay();
        $amount = (float) $data['importo_totale'];
        $tipo = $data['tipo'];
        $descrizione = trim($data['descrizione']);

        // Giorni totali: compreso il giorno di inizio e di fine (+1)
        $totalDays = $dStart->diffInDays($dEnd) + 1;

        // Giorni di competenza dell'anno corrente: da inizio a chiusura compresi (+1)
        $currentYearDays = $dStart->diffInDays($dClosing) + 1;

        // Giorni futuri di risconto: da chiusura a fine (il giorno di chiusura è già conteggiato nell'anno corrente)
        $futureDays = $dClosing->diffInDays($dEnd);

        // Quote
        $dailyQuota = $totalDays > 0 ? ($amount / $totalDays) : 0.0;
        $monthlyQuota = round($dailyQuota * self::DAYS_PER_MONTH, 2);
        $competenzaAmount = round($dailyQuota * $currentYearDays, 2);

        // Quadratura dei centesimi per garantire che competenza + risconto == totale esatto
        $riscontoAmount = round($amount - $competenzaAmount, 2);

        // Percentuali
        $pctCurrent = $totalDays > 0 ? round(($currentYearDays / $totalDays) * 100, 2) : 0.0;
        $pctFuture = $totalDays > 0 ? round(($futureDays / $totalDays) * 100, 2) : 0.0;

        // Partita doppia
        $partitaDoppia = $this->buildPartitaDoppia($tipo, $descrizione, $riscontoAmount, $dClosing->format('d/m/Y'));

        return [
            'tipo' => $tipo,
            'descrizione' => $descrizione,
            'importo_totale' => $amount,
            'data_inizio' => $dStart->format('Y-m-d'),
            'data_fine' => $dEnd->format('Y-m-d'),
            'data_chiusura_bilancio' => $dClosing->format('Y-m-d'),
            'giorni_totali' => $totalDays,
            'giorni_competenza' => $currentYearDays,
            'giorni_futuri' => $futureDays,
            'quota_giornaliera' => round($dailyQuota, 4),
            'quota_mensile' => $monthlyQuota,
            'quota_competenza' => $competenzaAmount,
            'importo_risconto' => $riscontoAmount,
            'percentuale_competenza' => $pctCurrent,
            'percentuale_risconto' => $pctFuture,
            'conto_dare' => $partitaDoppia['conto_dare'],
            'conto_avere' => $partitaDoppia['conto_avere'],
            'partita_doppia' => $partitaDoppia,
        ];
    }

    /**
     * Genera la scrittura contabile in Partita Doppia per il risconto
     */
    private function buildPartitaDoppia(string $tipo, string $descrizione, float $importo, string $dataChiusura): array
    {
        if ($tipo === 'ATTIVO') {
            return [
                'data_scrittura' => $dataChiusura,
                'conto_dare' => 'Risconti Attivi',
                'dare_importo' => $importo,
                'conto_avere' => 'Costi per ' . ($descrizione ?: 'Operazione') . ' (Storno)',
                'avere_importo' => $importo,
                'spiegazione' => 'Si rinvia all\'esercizio successivo la quota di costo non ancora maturata (Storno di Costo).',
                'descrizione_tipo' => 'Risconto Attivo (Costo Sospeso)',
            ];
        }

        return [
            'data_scrittura' => $dataChiusura,
            'conto_dare' => 'Ricavi per ' . ($descrizione ?: 'Operazione') . ' (Storno)',
            'dare_importo' => $importo,
            'conto_avere' => 'Risconti Passivi',
            'avere_importo' => $importo,
            'spiegazione' => 'Si rinvia all\'esercizio successivo la quota di ricavo non ancora maturata (Storno di Ricavo).',
            'descrizione_tipo' => 'Risconto Passivo (Ricavo Sospeso)',
        ];
    }

    /**
     * Risolve l'ID della cartella "Risconti" su Google Drive (la crea se assente)
     */
    private function resolveRiscontiFolderId(): ?string
    {
        $settingService = new SettingService();

        $configured = $settingService->get('google_drive_risconti_folder_id');
        if (is_string($configured) && strlen($configured) >= 10) {
            return $configured;
        }

        $parentId = $settingService->get('google_drive_documenti_folder_id');
        $parentId = (is_string($parentId) && strlen($parentId) >= 10) ? $parentId : 'root';

        $folderId = GoogleDrive::search($parentId, 'google', 'dir', 'Risconti', true);

        return (is_string($folderId) && strlen($folderId) >= 10) ? $folderId : null;
    }

    /**
     * Genera il nome file allegato: risconto_{id}_{descrizione}.pdf
     */
    private function buildAllegatoFilename(FiRisconto $risconto): string
    {
        $desc = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '-', $risconto->descrizione);
        $desc = trim(Str::limit($desc, 60, ''));

        return 'risconto_' . $risconto->id . '_' . ($desc !== '' ? $desc : 'allegato') . '.pdf';
    }

    /**
     * Carica il PDF su Google Drive e aggiorna il record.
     * Ritorna null in caso di successo, altrimenti il messaggio di errore.
     */
    private function uploadAllegatoPdf(FiRisconto $risconto, UploadedFile $file): ?string
    {
        try {
            $folderId = $this->resolveRiscontiFolderId();
            if (empty($folderId)) {
                return 'Cartella Google Drive "Risconti" non disponibile o non configurata.';
            }

            $filename = $this->buildAllegatoFilename($risconto);
            $fileId = GoogleDrive::add_file([$folderId], $filename, $file->getRealPath(), true);

            if (!is_string($fileId) || strlen($fileId) < 10) {
                return 'Upload del PDF su Google Drive fallito.';
            }

            // Sostituzione: elimina il vecchio file su Drive
            $oldFileId = $risconto->google_drive_file_id;
            if (!empty($oldFileId) && $oldFileId !== $fileId) {
                GoogleDrive::delated($oldFileId);
            }

            $risconto->update([
                'google_drive_file_id' => $fileId,
                'allegato_nome' => $filename,
            ]);

            return null;
        } catch (\Throwable $e) {
            return 'Errore durante l\'upload del PDF: ' . $e->getMessage();
        }
    }
}
