<?php

namespace App\Http\Controllers;

use App\Models\JobLog;
use Illuminate\Http\Request;

class JobLogController extends Controller
{
    /**
     * Ottieni gli ultimi job ProcessQualityPdf
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 20);
        
        $logs = JobLog::where('job_name', 'ProcessQualityPdf')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $logs,
            'total' => $logs->count(),
        ]);
    }

    /**
     * Ottieni i dettagli di un job specifico
     */
    public function show($id)
    {
        $log = JobLog::findOrFail($id);
        
        return response()->json($log);
    }

    /**
     * Ottieni statistiche sui job ProcessQualityPdf
     */
    public function stats()
    {
        $stats = [
            'total' => JobLog::where('job_name', 'ProcessQualityPdf')->count(),
            'running' => JobLog::where('job_name', 'ProcessQualityPdf')->where('status', 'running')->count(),
            'success' => JobLog::where('job_name', 'ProcessQualityPdf')->where('status', 'success')->count(),
            'failed' => JobLog::where('job_name', 'ProcessQualityPdf')->where('status', 'failed')->count(),
            'last_24h' => JobLog::where('job_name', 'ProcessQualityPdf')
                ->where('created_at', '>=', now()->subHours(24))
                ->count(),
        ];

        return response()->json($stats);
    }
}
