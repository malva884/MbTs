<?php

namespace App\Http\Controllers;

use App\Imports\WsRowsImport;
use App\Jobs\WsImportJob;
use App\Models\WsHead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class WsHeadController extends Controller
{
    /**
     * Lista paginata delle testate (index del modulo).
     */
    public function list(Request $request)
    {
        $sortBy = $request->get('sortBy', 'created_at');
        $orderBy = $request->get('orderBy', 'desc');
        $perPage = (int)($request->get('itemsPerPage', 15));

        $allowed = ['created_at', 'status', 'total_final_mp', 'total_raw_material', 'total_final', 'raw_material_machine_manpower_cost'];
        if (!in_array($sortBy, $allowed))
            $sortBy = 'created_at';

        $heads = WsHead::orderBy($sortBy, $orderBy === 'asc' ? 'asc' : 'desc')
            ->paginate($perPage);

        return response()->json($heads);
    }

    /**
     * Avvia l'importazione dello Stato Lavori (job in coda).
     */
    public function load(Request $request)
    {
        $request->validate([
            'data_macchinari' => 'required|date',
            'data_matariali' => 'required|date',
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $path = $request->file('file')->store('ws_imports');

        $head = new WsHead;
        $head->status = 0;
        $head->save();

        WsImportJob::dispatch(
            $head->id,
            $path,
            $request->data_macchinari,
            $request->data_matariali
        );

        return response()->json([
            'success' => true,
            'head_id' => $head->id,
        ]);
    }

    /**
     * Progresso dell'importazione in corso.
     */
    public function importProgress(string $headId)
    {
        $progress = Cache::get("ws_import_{$headId}");
        $head = WsHead::find($headId);

        return response()->json([
            'progress' => $progress,
            'head_status' => $head?->status,
            'import_error' => $head?->import_error,
        ]);
    }

    /**
     * Elimina una testata (righe e riepiloghi in cascade).
     */
    public function destroy(string $id)
    {
        $head = WsHead::find($id);
        if (!$head) {
            return response()->json([
                'success' => false,
                'message' => 'Messaggi.Elemento-Non-Trovato',
                'color' => 'error',
            ], 404);
        }

        try {
            Cache::forget("ws_import_{$id}");
            $head->delete();

            return response()->json([
                'success' => true,
                'message' => 'Messaggi.Elemento-Eliminato',
                'color' => 'success',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore eliminazione: ' . $e->getMessage(),
                'color' => 'error',
            ], 500);
        }
    }

    /**
     * Serie per grafico andamento (ultime 6 testate completate).
     */
    public function charts()
    {
        $workstatus = WsHead::where('status', 2)
            ->latest()
            ->take(6)
            ->get()
            ->reverse()
            ->values();

        $arr = [
            'a' => [],
            'b' => [],
            'c' => [],
            'd' => [],
        ];

        foreach ($workstatus as $row) {
            $date = $row->created_at ? $row->created_at->format('d/m/y') : '';
            $arr['a'][] = ['x' => $date, 'y' => round((float)$row->total_final_mp, 2)];
            $arr['b'][] = ['x' => $date, 'y' => round((float)$row->total_raw_material, 2)];
            $arr['c'][] = ['x' => $date, 'y' => round((float)$row->total_final, 2)];
            $arr['d'][] = ['x' => $date, 'y' => round((float)$row->raw_material_machine_manpower_cost, 2)];
        }

        return response()->json($arr);
    }

    /**
     * Verifica file "Zestra": individua materiali duplicati per ordine.
     */
    public function verificaCheck(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $list = [];
        try {
            $file = Excel::toArray(new WsRowsImport, $request->file('file'));
            $order = '';
            $tmp = [];
            foreach ($file as $rows) {
                foreach ($rows as $row) {
                    if (!empty($row[0])) {
                        $order = (string)$row[0];
                        if (!isset($tmp[$order])) {
                            $tmp[$order] = [];
                        }
                    }
                    $material = !empty($row[1]) ? (string)$row[1] : null;
                    if ($material && $order !== '') {
                        if (!in_array($material, $tmp[$order])) {
                            $tmp[$order][] = $material;
                        } else {
                            if (!isset($list[$order])) {
                                $list[$order] = [];
                            }
                            if (!in_array($material, $list[$order])) {
                                $list[$order][] = $material;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore lettura file: ' . $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'duplicates' => $list,
        ]);
    }
}
