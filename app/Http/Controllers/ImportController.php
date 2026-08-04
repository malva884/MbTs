<?php

namespace App\Http\Controllers;

use App\Jobs\ImportPrMovements;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ImportController extends Controller
{
    /**
     * Avvia l'importazione da Google Sheets con tracking del progresso
     */
    public function startImport(Request $request)
    {
        $request->validate([
            'spreadsheet_id' => 'required|string'
        ]);

        $spreadsheetId = $request->input('spreadsheet_id');
        $jobId = uniqid('import_');

        // Inizializza cache PRIMA di dispatchare il job
        Cache::put("import_progress_{$jobId}", [
            'status' => 'processing',
            'total_rows' => 0,
            'processed_rows' => 0,
            'imported_count' => 0,
            'skipped_count' => 0,
            'percentage' => 0,
            'message' => 'Caricamento dati da Google Sheets...'
        ], 300);

        // Dispatch job
        ImportPrMovements::dispatch($spreadsheetId, $jobId);

        return response()->json([
            'success' => true,
            'message' => 'Importazione avviata',
            'job_id' => $jobId
        ]);
    }

    /**
     * Ottiene il progresso dell'importazione
     */
    public function getImportProgress(Request $request)
    {
        $request->validate([
            'job_id' => 'required|string'
        ]);

        $jobId = $request->input('job_id');
        $cacheKey = "import_progress_{$jobId}";
        
        $progress = Cache::get($cacheKey);

        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Job non trovato o scaduto'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'progress' => $progress
        ]);
    }
}
