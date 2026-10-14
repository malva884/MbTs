<?php

namespace App\Jobs;

use App\Imports\WsRowsImport;
use App\Models\Utility;
use App\Models\WsHead;
use App\Models\WsMachineCost;
use App\Models\WsMaterialCost;
use App\Models\WsRow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Job asincrono che importa il file Excel dello Stato Lavori.
 * Legge tutte le righe, le trasforma in WsRow (testate ordine + righe
 * componenti/prelievi), applica i costi di macchine e materiali e
 * aggiorna i totali sulla testata WsHead.
 */
class WsImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800;

    protected string $headId;         // id della testata WsHead da popolare
    protected string $filePath;       // path del file Excel caricato (storage)
    protected string $dateMachines;   // data_import per il lookup dei costi macchina
    protected string $dateMaterials;  // data_import per il lookup dei costi materiale

    public function __construct(string $headId, string $filePath, string $dateMachines, string $dateMaterials)
    {
        $this->headId = $headId;
        $this->filePath = $filePath;
        $this->dateMachines = $dateMachines;
        $this->dateMaterials = $dateMaterials;
    }

    // Scrive lo stato di avanzamento in cache (TTL 10 minuti):
    // il frontend fa polling su questa chiave per la barra di progresso.
    private function progress(array $data): void
    {
        Cache::put("ws_import_{$this->headId}", $data, 600);
    }

    /**
     * Persiste il batch di righe con insert multi-riga.
     * Normalizza le chiavi degli attributi (le righe possono avere
     * insiemi di attributi diversi) e dimensiona i chunk sul limite
     * di 2100 parametri di SQL Server.
     */
    private function flushRows(array &$rowsBatch): void
    {
        if (empty($rowsBatch))
            return;

        $columns = [];
        foreach ($rowsBatch as $attrs) {
            foreach ($attrs as $key => $value) {
                $columns[$key] = true;
            }
        }
        $columns = array_keys($columns);

        // Tipi delle colonne: in un insert multi-riga SQL Server ricava
        // il tipo di ogni colonna per precedenza dei parametri
        // (int batte nvarchar). Valori eterogenei sulla stessa colonna
        // (es. int 0 in una riga e stringa '46.31' in un'altra)
        // scatenano "Conversion failed ... to data type int".
        // Forzo quindi tipi PHP coerenti colonna per colonna.
        $decimalCols = array_flip([
            'total_order_quantity', 'quantity_1', 'work_center_cost', 'confirmed_quantity',
            'activity_machine_hours', 'machine_cost', 'activity_manpower_hours', 'manpower_cost',
            'machine_cost_manpower', 'calculation_1', 'requirement_quantity', 'quantity_2',
            'material_cost', 'cost_material', 'calculation_2', 'total_raw_material',
            'total_machine_cost_manpower', 'final_total', 'raw_material_machine_manpower_cost',
            'result_1', 'result_2', 'qty_perv_fase',
        ]);
        $intCols = array_flip(['order_row']);
        $boolCols = array_flip(['check_row', 'view']);

        $normalized = [];
        foreach ($rowsBatch as $attrs) {
            $row = [];
            foreach ($columns as $col) {
                $value = $attrs[$col] ?? null;
                if (isset($boolCols[$col])) {
                    // bit NOT NULL: un null esplicito violerebbe il vincolo
                    $value = (bool) $value;
                } elseif (isset($decimalCols[$col])) {
                    $value = ($value === '' || $value === null) ? null : (float) $value;
                } elseif (isset($intCols[$col])) {
                    $value = ($value === '' || $value === null) ? null : (int) $value;
                } elseif (is_int($value) || is_float($value) || is_bool($value)) {
                    // Colonne stringa/uuid/data: mantengo il binding nvarchar
                    // uniforme per evitare l'inferenza a int di SQL Server
                    $value = (string) $value;
                }
                $row[$col] = $value;
            }
            $normalized[] = $row;
        }

        $perInsert = max(1, (int) floor(2000 / count($columns)));
        foreach (array_chunk($normalized, $perInsert) as $chunk) {
            WsRow::insert($chunk);
        }

        $rowsBatch = [];
    }

    public function handle(): void
    {
        // File potenzialmente molto grandi: alzo memoria e tempo di esecuzione
        ini_set('memory_limit', '3048M');
        set_time_limit(1800);

        $this->progress([
            'status' => 'processing',
            'processed_rows' => 0,
            'percentage' => 0,
            'message' => 'Lettura file in corso...',
        ]);

        $head = WsHead::find($this->headId);
        if (!$head)
            return;

        try {
            // Carico le tabelle dei costi per la data di importazione scelta
            // e le indicizzo in mappe per lookup O(1) durante il parsing.
            $machineCosts = WsMachineCost::where('date_import', $this->dateMachines)->get();
            $matirialCosts = WsMaterialCost::where('date_import', $this->dateMaterials)->get();

            // Mappa: codice macchina => costo macchina + costo manodopera
            $machines = [];
            foreach ($machineCosts as $machineCost)
                $machines[$machineCost->machine] = [
                    'costo_macchina' => $machineCost->cost,
                    'costo_manodopera' => $machineCost->manpower_cost,
                ];

            // Mappa: codice materiale => costo unitario
            $materials = [];
            foreach ($matirialCosts as $matirialCost)
                $materials[$matirialCost->material] = $matirialCost->cost;

            // Parsing completo del file Excel: array di fogli, ognuno con le sue righe
            $file = Excel::toArray(new WsRowsImport, Storage::path($this->filePath));

            DB::beginTransaction();

            $father = null;                  // codice ordine "padre" corrente (colonna 0)
            $saveCheck = false;              // true se l'ordine corrente ha q.tà > 0
            $infoOrder = [];                 // info dell'ordine propagate alle righe figlie
            $phaseCalc = null;               // coefficiente dell'ultima fase vista (riga con lavorazione)
            $order = 0;                      // indice progressivo di riga (order_row)
            $fathersWithMissingCheck = [];   // ordini con almeno un prelievo senza q.tà
            $rowsBatch = [];                 // buffer per l'insert multi-riga
            $now = now();                    // timestamp unico per tutte le righe

            // Il file è una distinta: le righe con colonna 0 valorizzata sono
            // testate ordine, quelle con colonna 0 vuota sono righe figlie
            // (fasi/prelievi) che ereditano il contesto dell'ultimo padre.
            foreach ($file as $rows) {
                $totalRows = count($rows);
                foreach ($rows as $index => $row) {
                    // Colonna 6 = quantità del prelievo
                    $qty1 = (float)($row[6] ?? 0);

                    // saveCheck vale solo per la riga testata e viene poi
                    // propagato alle figlie: se l'ordine ha q.tà <= 0 non
                    // vengono segnalati prelievi mancanti sulle sue righe.
                    if (!empty($row[0]) && $qty1 > 0)
                        $saveCheck = true;
                    elseif (!empty($row[0]) && $qty1 <= 0)
                        $saveCheck = false;

                    // Nuova testata ordine: diventa il padre delle righe
                    // successive e si resettano le info ordine propagate.
                    if (!empty($row[0])) {
                        $father = $row[0];
                        $infoOrder = [];
                        $phaseCalc = null;
                    }

                    $work = new WsRow;
                    $work->id = (string) Str::uuid();
                    $work->order_row = $order;
                    $work->created_at = $now;
                    $work->updated_at = $now;

                    // storeRow valorizza la riga: per le testate passa padre/infoOrder
                    // a null e restituisce le info ordine; per le figlie usa
                    // $father e $infoOrder dell'ordine corrente.
                    if (!empty($row[0]))
                        $infoOrder = $work->storeRow($materials, $machines, $row, $head, $saveCheck, null, null, $fathersWithMissingCheck, false, $phaseCalc);
                    else
                        $infoOrder = $work->storeRow($materials, $machines, $row, $head, $saveCheck, $father, $infoOrder, $fathersWithMissingCheck, false, $phaseCalc);

                    // I prelievi appartengono all'ultima fase: aggiorno il
                    // coefficiente di fase quando la riga ha una lavorazione
                    if (!empty($work->operation_activity))
                        $phaseCalc = is_numeric($work->calculation_1) ? (float)$work->calculation_1 : null;

                    // Accumulo nel buffer e scarico su DB ogni 200 righe
                    $rowsBatch[] = $work->getAttributes();
                    if (count($rowsBatch) >= 200)
                        $this->flushRows($rowsBatch);

                    $order++;

                    // Aggiorno il progresso ogni 50 righe (non ogni riga
                    // per non sovraccaricare la cache)
                    if ($index % 50 === 0) {
                        $this->progress([
                            'status' => 'processing',
                            'processed_rows' => $order,
                            'total_rows' => $totalRows,
                            'percentage' => $totalRows ? min(99, round($order / $totalRows * 100)) : 0,
                            'message' => "Elaborazione riga {$order} di {$totalRows}...",
                        ]);
                    }
                }
            }

            // Scarico l'eventuale resto del buffer
            $this->flushRows($rowsBatch);

            // Aggiorna padri con prelievi mancanti in un'unica query o chunk
            if (!empty($fathersWithMissingCheck)) {
                foreach (array_chunk(array_keys($fathersWithMissingCheck), 1000) as $chunk) {
                    WsRow::where('head', $head->id)
                        ->whereIn('order', $chunk)
                        ->update(['check_row' => true]);
                }
            }

            // Calcolo totali con singola query aggregata (niente somme in PHP)
            $totals = WsRow::where('head', $head->id)
                ->selectRaw('
                    COALESCE(SUM(material_cost), 0) as total_material,
                    COALESCE(SUM(manpower_cost), 0) as total_manpower,
                    COALESCE(SUM(machine_cost), 0) as total_machine
                ')
                ->first();

            $totalMaterial = (float)($totals->total_material ?? 0);
            $totalManodopera = (float)($totals->total_manpower ?? 0);
            $totalMacchina = (float)($totals->total_machine ?? 0);

            // Totali testata: manodopera + macchina, materiale, e somma complessiva
            $manodoperaMacchina = $totalManodopera + $totalMacchina;
            $head->total_machine_manpower_cost = round($manodoperaMacchina, 2);
            $head->total_raw_material = round($totalMaterial, 2);
            $head->raw_material_machine_manpower_cost = round($manodoperaMacchina + $totalMaterial, 2);
            $head->status = 1;
            $head->save();

            DB::commit();

            // Notifica prelievi mancanti: se ci sono righe con check_row
            // invio una mail agli utenti abilitati con link alla pagina check
            $checkCount = WsRow::where('head', $head->id)->where('check_row', true)->count();
            if ($checkCount) {
                $emails = Utility::users_notify(['it_workprogress_prelievi']);
                if (count($emails)) {
                    $headId = $head->id;
                    $content = "L'importazione dello Stato Lavori ({$headId}) ha rilevato {$checkCount} prelievi con quantità mancanti.";
                    $link = config('app.url') . '/it/workstatus/check/' . $headId;

                    Mail::send('emails.email_ws_prelievi', compact('content', 'link'), function ($message) use ($emails) {
                        $message->to($emails)->subject('Corso Lavori - Quantità Mancanti');
                    });
                }
            }

            // Il file Excel temporaneo non serve più
            Storage::delete($this->filePath);

            $this->progress([
                'status' => 'done',
                'processed_rows' => $order,
                'percentage' => 100,
                'check_rows' => $checkCount,
                'message' => 'Importazione completata',
            ]);
        } catch (\Throwable $e) {
            // In caso di errore: rollback totale (nessuna riga parziale),
            // log, testata marcata come fallita (status 3) e progresso a 'error'
            DB::rollBack();
            Log::error('WsImportJob error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            $head->status = 3;
            $head->import_error = mb_substr($e->getMessage(), 0, 2000);
            $head->save();

            $this->progress([
                'status' => 'error',
                'message' => 'Errore importazione: ' . $e->getMessage(),
            ]);
        }
    }
}
