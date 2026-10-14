<?php

namespace App\Http\Controllers;

use App\Imports\WsMachineCostsImport;
use App\Imports\WsMaterialCostsImport;
use App\Models\PrMaterial;
use App\Models\WsMachineCost;
use App\Models\WsMaterialCost;
use App\Models\WsSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class WsCostController extends Controller
{
    /**
     * Date import disponibili + costo manodopera corrente.
     * Le date vengono normalizzate a Y-m-d per evitare problemi
     * di serializzazione timezone (es. "2026-10-12T22:00:00.000000Z").
     */
    public function dates()
    {
        $toDate = fn ($d) => \Carbon\Carbon::parse($d)->format('Y-m-d');

        $machines = WsMachineCost::select('date_import')
            ->distinct()
            ->orderByDesc('date_import')
            ->pluck('date_import')
            ->map($toDate)
            ->values();

        $materials = WsMaterialCost::select('date_import')
            ->distinct()
            ->orderByDesc('date_import')
            ->pluck('date_import')
            ->map($toDate)
            ->values();

        return response()->json([
            'machines' => $machines,
            'materials' => $materials,
            'manpower' => WsSetting::getValue('ws_manpower', ''),
        ]);
    }

    /**
     * Import costi macchinari (sostituisce quelli del giorno).
     * Se il file non viene caricato, viene copiato l'ultimo listino
     * disponibile dal vecchio portale (mysql_old.machine_labor_costs).
     */
    public function importMachines(Request $request)
    {
        $request->validate([
            'file' => 'nullable|file|mimes:xlsx,xls,csv',
        ]);

        ini_set('memory_limit', '1024M');
        set_time_limit(900);

        try {
            DB::beginTransaction();

            WsMachineCost::where('date_import', date('Y-m-d'))->delete();

            if ($request->hasFile('file')) {
                Excel::import(new WsMachineCostsImport, $request->file('file'));
            } else {
                $latestDate = DB::connection('mysql_old')
                    ->table('machine_labor_costs')
                    ->max('date_import');

                if (empty($latestDate)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Nessun listino macchinari disponibile nel vecchio portale',
                        'color' => 'error',
                    ], 422);
                }

                $now = now();
                $today = date('Y-m-d');
                $batch = [];

                DB::connection('mysql_old')->table('machine_labor_costs')
                    ->select('machine', 'cost', 'manpawer_cost')
                    ->where('date_import', $latestDate)
                    ->orderBy('machine')
                    ->chunk(2000, function ($machines) use (&$batch, $today, $now) {
                        foreach ($machines as $m) {
                            $batch[] = [
                                'machine' => (string)$m->machine,
                                'cost' => (float)$m->cost,
                                'manpower_cost' => (float)$m->manpawer_cost,
                                'date_import' => $today,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    });

                if (empty($batch)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Nessun listino macchinari disponibile nel vecchio portale',
                        'color' => 'error',
                    ], 422);
                }

                // SQL Server supporta max 2100 parametri per query:
                // 6 colonne per riga -> chunk conservativo da 300 righe
                foreach (array_chunk($batch, 300) as $chunk) {
                    WsMachineCost::insert($chunk);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Errore importazione: ' . $e->getMessage(),
                'color' => 'error',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Import-Completato',
            'color' => 'success',
        ]);
    }

    /**
     * Import costi materiali (sostituisce quelli del giorno).
     * Se il file non viene caricato, i costi vengono presi dall'anagrafica pr_materials.
     */
    public function importMaterials(Request $request)
    {
        $request->validate([
            'file' => 'nullable|file|mimes:xlsx,xls,csv',
        ]);

        ini_set('memory_limit', '1024M');
        set_time_limit(900);

        try {
            DB::beginTransaction();

            WsMaterialCost::where('date_import', date('Y-m-d'))->delete();

            if ($request->hasFile('file')) {
                Excel::import(new WsMaterialCostsImport, $request->file('file'));
            } else {
                $now = now();
                $today = date('Y-m-d');
                $seen = [];
                $batch = [];

                PrMaterial::select('materiale', 'valore')
                    ->orderBy('materiale')
                    ->chunk(2000, function ($materials) use (&$batch, &$seen, $today, $now) {
                        foreach ($materials as $m) {
                            if (!$m->materiale || isset($seen[$m->materiale]))
                                continue;
                            $seen[$m->materiale] = true;
                            $batch[] = [
                                'material' => $m->materiale,
                                'cost' => (float)$m->valore,
                                'date_import' => $today,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    });

                if (empty($batch)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Nessun materiale disponibile in anagrafica',
                        'color' => 'error',
                    ], 422);
                }

                // SQL Server supporta max 2100 parametri per query:
                // 5 colonne per riga -> chunk conservativo da 300 righe
                foreach (array_chunk($batch, 300) as $chunk) {
                    WsMaterialCost::insert($chunk);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Errore importazione: ' . $e->getMessage(),
                'color' => 'error',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Import-Completato',
            'color' => 'success',
        ]);
    }

    /**
     * Salva il costo manodopera generico.
     */
    public function saveManpower(Request $request)
    {
        $request->validate([
            'manpower' => 'required|numeric',
        ]);

        WsSetting::setValue('ws_manpower', $request->manpower);

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Elemento-Salvato',
            'color' => 'success',
        ]);
    }
}
