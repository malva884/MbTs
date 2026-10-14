<?php

namespace App\Http\Controllers;

use App\Models\WsHead;
use App\Models\WsRow;
use App\Models\WsSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WsRowController extends Controller
{
    /**
     * Colonne aggiornabili dalla griglia.
     */
    private const EDITABLE_COLUMNS = [
        'quantity_1', 'qty_perv_fase', 'confirmed_quantity',
        'calculation_1', 'calculation_2', 'quantity_2',
        'result_1', 'result_2',
    ];

    /**
     * Dati testata per le pagine dettaglio/riepilogo.
     */
    public function head(string $id)
    {
        $head = WsHead::find($id);
        if (!$head)
            return response()->json(['success' => false, 'message' => 'Messaggi.Elemento-Non-Trovato'], 404);

        return response()->json($head);
    }

    /**
     * Righe da calcolare (view = true).
     */
    public function list(string $id)
    {
        $rows = WsRow::where('head', $id)
            ->where('view', true)
            ->orderBy('order_row')
            ->get();

        return response()->json($rows);
    }

    /**
     * Tutte le righe della testata.
     */
    public function listAll(string $id)
    {
        $rows = WsRow::where('head', $id)
            ->orderBy('order_row')
            ->get();

        return response()->json($rows);
    }

    /**
     * Righe con prelievi mancanti (check_row = true).
     */
    public function checkList(string $id)
    {
        $rows = WsRow::where('head', $id)
            ->where('check_row', true)
            ->orderBy('order_row')
            ->get();

        return response()->json($rows);
    }

    /**
     * Update cella dalla griglia dettaglio.
     * Ricalcola material_cost su quantity_2 e result su coefficienti.
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|string',
            'column' => 'required|string',
            'value' => 'nullable',
        ]);

        $column = $request->get('column');
        if (!in_array($column, self::EDITABLE_COLUMNS)) {
            return response()->json([
                'success' => false,
                'message' => 'Colonna non modificabile',
            ], 422);
        }

        $workStatus = WsRow::find($request->get('id'));
        if (!$workStatus)
            return response()->json(['success' => false, 'message' => 'Riga non trovata'], 404);

        $value = $request->get('value');
        $workStatus->$column = ($value === '' || $value === null) ? null : $value;

        if ($column === 'quantity_2') {
            $workStatus->material_cost = round((float)$workStatus->cost_material * (float)$workStatus->quantity_2, 2);
            if (is_numeric($workStatus->calculation_2)) {
                $workStatus->result_2 = round((float)$workStatus->material_cost * (float)$workStatus->calculation_2, 2);
            }
            $this->checkOrder($workStatus->father, $workStatus->head);
        }

        if ($column === 'calculation_1') {
            $workStatus->result_1 = is_numeric($value)
                ? round((float)$workStatus->machine_cost_manpower * (float)$value, 2)
                : 0;

            // Regola fase: coefficiente fase = 1 => coefficiente = 1 anche
            // sui prelievi della fase (righe con material_2 tra questa fase
            // e la successiva riga con lavorazione dello stesso ordine)
            if (is_numeric($value) && (float)$value === 1.0) {
                $orderNumber = $workStatus->father ?? $workStatus->order;

                if ($orderNumber) {
                    $nextPhase = WsRow::where('head', $workStatus->head)
                        ->where('father', $orderNumber)
                        ->whereNotNull('operation_activity')
                        ->where('order_row', '>', $workStatus->order_row)
                        ->min('order_row');

                    WsRow::where('head', $workStatus->head)
                        ->where('father', $orderNumber)
                        ->whereNotNull('material_2')
                        ->where('order_row', '>', $workStatus->order_row)
                        ->when($nextPhase, function ($q) use ($nextPhase) {
                            $q->where('order_row', '<', $nextPhase);
                        })
                        ->update([
                            'calculation_2' => 1,
                            'result_2' => DB::raw('material_cost'),
                        ]);
                }
            }
        }

        if ($column === 'calculation_2')
            $workStatus->result_2 = is_numeric($value)
                ? round((float)$workStatus->material_cost * (float)$value, 2)
                : 0;

        $workStatus->save();

        return response()->json([
            'success' => true,
            'material_cost' => $workStatus->material_cost,
            'result_1' => $workStatus->result_1,
            'result_2' => $workStatus->result_2,
        ]);
    }

    /**
     * Update cella dalla pagina Prelievi Mancanti.
     * Compilando quantity_2 la riga esce dai prelievi mancanti.
     */
    public function updateCheck(Request $request)
    {
        $request->validate([
            'id' => 'required|string',
            'column' => 'required|string',
            'value' => 'nullable',
        ]);

        $column = $request->get('column');
        if (!in_array($column, self::EDITABLE_COLUMNS)) {
            return response()->json([
                'success' => false,
                'message' => 'Colonna non modificabile',
            ], 422);
        }

        $workStatus = WsRow::find($request->get('id'));
        if (!$workStatus)
            return response()->json(['success' => false, 'message' => 'Riga non trovata'], 404);

        $value = $request->get('value');
        $workStatus->$column = ($value === '' || $value === null) ? null : $value;

        if ($column === 'quantity_2') {
            $workStatus->check_row = !is_numeric($workStatus->quantity_2) || (float)$workStatus->quantity_2 <= 0;
            $workStatus->material_cost = round((float)$workStatus->cost_material * (float)$workStatus->quantity_2, 2);
            if (is_numeric($workStatus->calculation_2)) {
                $workStatus->result_2 = round((float)$workStatus->material_cost * (float)$workStatus->calculation_2, 2);
            }
            $workStatus->save();
            $this->checkOrder($workStatus->father, $workStatus->head);

            return response()->json([
                'success' => true,
                'check_row' => $workStatus->check_row,
                'material_cost' => $workStatus->material_cost,
                'result_2' => $workStatus->result_2,
            ]);
        }

        $workStatus->save();

        return response()->json(['success' => true]);
    }

    /**
     * Aggiorna la nota del riepilogo per ordine.
     */
    public function updateSummary(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'value' => 'nullable|string',
        ]);

        $summary = WsSummary::find($request->get('id'));
        if (!$summary)
            return response()->json(['success' => false, 'message' => 'Riga non trovata'], 404);

        $summary->note = $request->get('value');
        $summary->save();

        return response()->json(['success' => true]);
    }

    /**
     * Se nessun figlio dell'ordine ha ancora check_row attivo,
     * spegne il flag anche sulla riga ordine padre.
     */
    private function checkOrder(?string $order, string $head): void
    {
        if (empty($order))
            return;

        $remaining = WsRow::where('father', $order)
            ->where('head', $head)
            ->where('check_row', true)
            ->count();

        if (!$remaining) {
            WsRow::where('order', $order)
                ->where('head', $head)
                ->update(['check_row' => false]);
        }
    }

    /**
     * Calcolo consuntivo: rigenera i riepiloghi per ordine
     * e aggiorna i totali della testata (status = 2).
     */
    public function finalWork(string $id)
    {
        ini_set('memory_limit', '3048M');
        set_time_limit(1200);

        $head = WsHead::find($id);
        if (!$head)
            return response()->json(['success' => false, 'message' => 'Messaggi.Elemento-Non-Trovato'], 404);

        try {
            DB::beginTransaction();

            WsSummary::where('head', $head->id)->delete();

            $totals = WsRow::where('head', $head->id)
                ->selectRaw('
                    COALESCE(SUM(material_cost), 0) as total_material,
                    COALESCE(SUM(manpower_cost), 0) as total_manpower,
                    COALESCE(SUM(machine_cost), 0) as total_machine
                ')
                ->first();

            $material = (float)($totals->total_material ?? 0);
            $totalManodopera = (float)($totals->total_manpower ?? 0);
            $totalMacchina = (float)($totals->total_machine ?? 0);

            $head->total_raw_material = $material;
            $head->total_machine_manpower_cost = $totalManodopera + $totalMacchina;
            $head->raw_material_machine_manpower_cost = $material + $totalManodopera + $totalMacchina;
            $head->save();

            $orders = WsRow::where('head', $head->id)
                ->where('view', true)
                ->whereNull('father')
                ->pluck('order');

            // Subquery per evitare il limite di 2100 parametri di SQL Server
            $ordersSubQuery = WsRow::where('head', $head->id)
                ->where('view', true)
                ->whereNull('father')
                ->select('order');

            $summariesData = WsRow::where('head', $head->id)
                ->whereIn('father', $ordersSubQuery)
                ->selectRaw('father as [order], COALESCE(SUM(result_2), 0) as total_material, COALESCE(SUM(result_1), 0) as total_res1')
                ->groupBy('father')
                ->get()
                ->keyBy('order');

            $now = now();
            $insertBatch = [];
            $totalSummaryMaterial = 0.0;
            $totalSummaryConsultivo = 0.0;

            foreach ($orders as $orderCode) {
                $stat = $summariesData->get($orderCode);
                $mat = round((float)($stat?->total_material ?? 0), 2);
                $res1 = (float)($stat?->total_res1 ?? 0);
                $cons = round($mat + $res1, 2);

                $totalSummaryMaterial += $mat;
                $totalSummaryConsultivo += $cons;

                $insertBatch[] = [
                    'order' => $orderCode,
                    'head' => $head->id,
                    'total_material' => $mat,
                    'total_consuntivo' => $cons,
                    'note' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Chunk conservativo: 7 colonne x 250 righe = 1750 parametri
            // (SQL Server supporta max 2100 parametri per query)
            if (!empty($insertBatch)) {
                foreach (array_chunk($insertBatch, 250) as $chunk) {
                    WsSummary::insert($chunk);
                }
            }

            $head->total_final_mp = (float)$head->total_raw_material - $totalSummaryMaterial;
            $head->total_final = (float)$head->raw_material_machine_manpower_cost - $totalSummaryConsultivo;
            $head->status = 2;
            $head->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('finalWork error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore calcolo: ' . $e->getMessage(),
                'color' => 'error',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Calcolo-Completato',
            'color' => 'success',
        ]);
    }

    /**
     * Riepilogo per ordine della testata.
     */
    public function summary(string $id)
    {
        $summaries = WsSummary::where('head', $id)->get();

        return response()->json($summaries);
    }
}
