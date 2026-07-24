<?php

namespace App\Jobs;

use App\Models\PrMovement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Revolution\Google\Sheets\Facades\Sheets;

class ImportPrMovements implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $spreadsheetId;
    protected $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct($spreadsheetId, $jobId)
    {
        $this->spreadsheetId = $spreadsheetId;
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        ini_set('memory_limit', '3048M');
        ini_set('max_execution_time', 3600);
        set_time_limit(3600);

        // Inizializza progress in cache
        Cache::put("import_progress_{$this->jobId}", [
            'status' => 'processing',
            'total_rows' => 0,
            'processed_rows' => 0,
            'imported_count' => 0,
            'skipped_count' => 0,
            'percentage' => 0,
            'message' => 'Caricamento dati da Google Sheets...'
        ]);

        try {
            // Carica gli uni_key esistenti del mese corrente e precedente
            $previousMonthStart = now()->subMonth()->startOfMonth();
            $currentMonthEnd = now()->endOfMonth();
            $existingUniKeys = PrMovement::whereNotNull('uni_key')
                ->whereBetween('data_pubblicazione', [$previousMonthStart, $currentMonthEnd])
                ->pluck('uni_key')
                ->toArray();
            $existingUniKeys = array_flip($existingUniKeys);

            $importedCount = 0;
            $skippedCount = 0;
            $chunkSize = 100;

            // Carica tutti i dati
            Cache::put("import_progress_{$this->jobId}", [
                'status' => 'processing',
                'total_rows' => 0,
                'processed_rows' => 0,
                'imported_count' => 0,
                'skipped_count' => 0,
                'percentage' => 0,
                'message' => 'Caricamento dati da Google Sheets...'
            ]);

            $rows = Sheets::spreadsheet($this->spreadsheetId)
                ->sheet('Sheet1')
                ->all();

            $totalRows = count($rows);
            $chunks = array_chunk($rows, $chunkSize);

            Cache::put("import_progress_{$this->jobId}", [
                'status' => 'processing',
                'total_rows' => $totalRows,
                'processed_rows' => 0,
                'imported_count' => 0,
                'skipped_count' => 0,
                'percentage' => 0,
                'message' => "Inizio importazione di {$totalRows} righe..."
            ]);

            $processedRows = 0;

            foreach ($chunks as $chunk) {
                $i = 1;

                foreach ($chunk as $row) {
                    if ($i == 1) {
                        $i++;
                        $processedRows++;
                        continue;
                    }

                    if (empty($row[0])) {
                        $processedRows++;
                        continue;
                    }

                    if (!empty($row[0])) {
                        $uni_key = $row[10] . '-' . $row[16];

                        // Controlla se uni_key esiste già
                        if (isset($existingUniKeys[$uni_key])) {
                            $skippedCount++;
                            $processedRows++;
                            continue;
                        }

                        $quantita = str_replace(',', '.', str_replace('.', '', $row[2]));
                        $quantita = (strpos($quantita, ".") === FALSE ? $quantita . '.000' : $quantita);
                        $import = str_replace(',', '.', str_replace('.', '', $row[3]));
                        $import = (strpos($import, ".") === FALSE ? $import . '.00' : $import);

                        $data_pubblicazione = explode("/", $row[11]);
                        $data_pubblicazione_formatted = $data_pubblicazione[2] . '-' . $data_pubblicazione[0] . '-' . $data_pubblicazione[1];

                        $data_documento = explode("/", $row[12]);
                        $data_documento_formatted = $data_documento[2] . '-' . $data_documento[0] . '-' . $data_documento[1];

                        $data_inserimento_formatted = explode("/", $row[13]);
                        $data_inserimento_formatted = $data_inserimento_formatted[2] . '-' . $data_inserimento_formatted[0] . '-' . $data_inserimento_formatted[1];

                        // Insert singolo con Eloquent per gestire UUID automaticamente
                        PrMovement::create([
                            'materiale' => $row[0],
                            'descrizione' => $row[1],
                            'quantita' => $quantita,
                            'importo' => $import,
                            'um' => $row[4],
                            'lotto' => $row[5],
                            'plant' => $row[6],
                            'posizione_archiviazione' => $row[7],
                            'tipo_movimento' => $row[8],
                            'special_stock' => $row[9],
                            'documento_materiale' => $row[10],
                            'data_pubblicazione' => $data_pubblicazione_formatted,
                            'data_documento' => $data_documento_formatted,
                            'data_inserimento' => $data_inserimento_formatted,
                            'testo_movimento' => $row[14],
                            'user' => $row[15],
                            'uni_key' => $uni_key,
                        ]);

                        $importedCount++;

                        // Aggiungi ai keys esistenti per evitare duplicati nello stesso batch
                        $existingUniKeys[$uni_key] = true;
                    }
                    $processedRows++;
                    $i++;
                }

                // Aggiorna progress dopo ogni chunk
                $percentage = round(($processedRows / $totalRows) * 100, 2);
                Cache::put("import_progress_{$this->jobId}", [
                    'status' => 'processing',
                    'total_rows' => $totalRows,
                    'processed_rows' => $processedRows,
                    'imported_count' => $importedCount,
                    'skipped_count' => $skippedCount,
                    'percentage' => $percentage,
                    'message' => "Elaborazione in corso: {$processedRows}/{$totalRows} ({$percentage}%)"
                ]);
            }

            // Importazione completata
            Cache::put("import_progress_{$this->jobId}", [
                'status' => 'completed',
                'total_rows' => $totalRows,
                'processed_rows' => $processedRows,
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
                'percentage' => 100,
                'message' => "Importazione completata. Importati: {$importedCount}, Saltati (duplicati): {$skippedCount}"
            ]);

        } catch (\Exception $e) {
            Cache::put("import_progress_{$this->jobId}", [
                'status' => 'failed',
                'total_rows' => 0,
                'processed_rows' => 0,
                'imported_count' => 0,
                'skipped_count' => 0,
                'percentage' => 0,
                'message' => 'Errore durante l\'importazione: ' . $e->getMessage()
            ]);
        }
    }
}
