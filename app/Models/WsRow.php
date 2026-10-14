<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WsRow extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'work_statuses';

    protected $fillable = [
        'id', 'head', 'order', 'father', 'order_row', 'material_1', 'material_description_1',
        'division', 'total_order_quantity', 'um', 'quantity_1', 'operation_activity',
        'work_center', 'work_center_cost', 'confirmed_quantity', 'activity_machine_hours',
        'machine_cost', 'activity_manpower_hours', 'manpower_cost', 'machine_cost_manpower',
        'calculation_1', 'material_2', 'material_description_2', 'requirement_quantity',
        'base_unit_measure', 'quantity_2', 'material_cost', 'cost_material', 'calculation_2',
        'total_raw_material', 'total_machine_cost_manpower', 'final_total',
        'raw_material_machine_manpower_cost', 'result_1', 'result_2',
        'qty_perv_fase', 'um_fase', 'date_created', 'check_row', 'view',
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'check_row' => 'boolean',
        'view' => 'boolean',
        'date_created' => 'date',
    ];

    /**
     * Mappa indice colonna Excel -> attributo modello.
     * Ported dal progetto old (WorkStatus::$camps).
     *
     * @var array<int|string, string>
     */
    private array $camps = [
        0 => 'order',
        1 => 'material_1',
        2 => 'material_description_1',
        3 => 'division',
        4 => 'total_order_quantity',
        5 => 'um',
        6 => 'quantity_1',
        7 => 'operation_activity',
        8 => 'work_center',
        'machine_cost' => 'work_center_cost',
        10 => 'confirmed_quantity',
        11 => 'activity_machine_hours',
        'machine_cost_total' => 'machine_cost',
        13 => 'activity_manpower_hours',
        'manpower_cost_total' => 'manpower_cost',
        'machine_cost_manpower' => 'machine_cost_manpower',
        'calculation_1' => 'calculation_1',
        15 => 'material_2',
        16 => 'material_description_2',
        17 => 'requirement_quantity',
        18 => 'base_unit_measure',
        19 => 'quantity_2',
        21 => 'qty_perv_fase',
        20 => 'um_fase',
        22 => 'material_cost',
        'calculation_2' => 'calculation_2',
        23 => 'total_raw_material',
        24 => 'total_machine_cost_manpower',
        25 => 'final_total',
        'result_1' => 'result_1',
        'result_2' => 'result_2',
    ];

    private function num($value): ?float
    {
        if ($value === null || $value === '')
            return null;

        return (float)$value;
    }

    /**
     * Salva una riga del file stato lavori applicando costi e calcoli.
     * Ported dal metodo stored() del model WorkStatus del progetto old.
     *
     * @param  array  $materiali  mappa codice materiale => costo unitario
     * @param  array  $macchinari mappa macchina => ['costo_macchina','costo_manodopera']
     * @param  array  $row        riga grezza del file Excel
     * @param  WsHead $head       testata corrente
     * @param  bool   $saveCheck  true quando il blocco ordine corrente ha quantity_1 > 0
     * @param  string|null $father ordine padre della riga
     * @param  array|null  $infoOrder info ordine accumulate (quantity, coefficiente, ...)
     * @param  array|null  $fathersWithMissingCheck mappa per raccogliere i padri con prelievi mancanti (evita query durante il ciclo)
     * @param  bool        $persist se true esegue save(), altrimenti popola solo il modello per batch insert
     * @param  float|null  $phaseCalc coefficiente della fase corrente (ultima riga con lavorazione)
     */
    public function storeRow(
        array $materiali,
        array $macchinari,
        array $row,
        WsHead $head,
        bool $saveCheck,
        ?string $father = null,
        ?array $infoOrder = null,
        ?array &$fathersWithMissingCheck = null,
        bool $persist = true,
        ?float $phaseCalc = null
    ): ?array
    {
        try {
            $machine_cost = 0.0;
            $material_cost = 0.0;

            foreach ($this->camps as $key => $camp) {
                switch ($key) {
                    case 8:
                        if ($row[$key] ?? null)
                            $machine_cost = (float)($macchinari[$row[$key]]['costo_macchina'] ?? 0);
                        $this->$camp = $row[$key] ?? null;
                        break;
                    case 11:
                    case 13:
                        if ($row[$key] ?? null)
                            $this->$camp = round((float)$row[$key] / 60, 2);
                        break;
                    case 19:
                        if ($row[$key] ?? null)
                            $this->$camp = $this->num(str_replace('-', '', (string)$row[$key]));
                        break;
                    case 20:
                    case 21:
                        if (!empty($row[$key]))
                            $this->$camp = ($key === 21 ? $this->num($row[$key]) : $row[$key]);
                        break;
                    case 22:
                        if ($this->quantity_2)
                            $this->$camp = round($material_cost * (float)$this->quantity_2, 2);
                        break;
                    case 15:
                        if ($row[$key] ?? null) {
                            $material_cost = (float)($materiali[$row[$key]] ?? 0);
                            $this->cost_material = $material_cost;
                        }
                        $this->$camp = $row[$key] ?? null;
                        break;
                    case 23:
                    case 24:
                        break;
                    case 'machine_cost_manpower':
                        if ($this->machine_cost)
                            $this->$camp = (float)$this->machine_cost + (float)$this->manpower_cost;
                        break;
                    case 'machine_cost':
                        if ($machine_cost)
                            $this->$camp = $machine_cost;
                        break;
                    case 'machine_cost_total':
                        if ($machine_cost)
                            $this->$camp = $machine_cost * (float)$this->activity_machine_hours;
                        break;
                    case 'manpower_cost_total':
                        if ((float)$this->activity_manpower_hours > 0) {
                            if (!empty($macchinari[$row[8]]['costo_manodopera']))
                                $this->$camp = (float)$macchinari[$row[8]]['costo_manodopera'] * (float)$this->activity_manpower_hours;
                        }
                        break;
                    case 25:
                    case 'calculation_1':
                    case 'calculation_2':
                        break;
                    case 'result_1':
                        if (!empty($this->machine_cost_manpower) && !empty($infoOrder['quantity'])) {
                            $confirmed = (float)$this->confirmed_quantity;
                            $qtyFase = (float)$this->qty_perv_fase;
                            if ($confirmed == (float)$infoOrder['quantity']) {
                                $this->result_1 = $this->machine_cost_manpower;
                                $this->calculation_1 = 1;
                            } elseif (!empty($infoOrder['coefficiente']) && !empty($qtyFase) && $confirmed > (float)$infoOrder['quantity'] && $confirmed >= $qtyFase) {
                                $this->result_1 = $infoOrder['coefficiente'] * (float)$this->machine_cost_manpower;
                                $this->calculation_1 = $infoOrder['coefficiente'];
                            } elseif (!empty($infoOrder['coefficiente']) && !empty($qtyFase) && $confirmed >= $qtyFase) {
                                $this->result_1 = $infoOrder['coefficiente'] * (float)$this->machine_cost_manpower;
                                $this->calculation_1 = $infoOrder['coefficiente'];
                            } else {
                                if( !in_array($this->work_center, [85, 70, 72]))
                                    $this->calculation_1 =  round(abs(($confirmed - $infoOrder['quantity']) / $confirmed - 1), 3);
                                else
                                    $this->calculation_1 = $infoOrder['coefficiente'];
                                $this->result_1 = $this->calculation_1 * (float)$this->machine_cost_manpower;
                            }
                            $this->result_1 = round((float)$this->result_1, 3);
                        } else {
                            $this->result_1 = 0;
                        }
                        break;
                    case 'result_2':
                        if (!empty($this->material_cost)) {
                            // Regola fase: se il coefficiente della fase e' 1
                            // anche quello dei suoi prelievi deve essere 1
                            if ($phaseCalc !== null && $phaseCalc == 1.0) {
                                $this->calculation_2 = 1;
                                $this->result_2 = round((float)$this->material_cost, 2);
                            } elseif (!empty($infoOrder['coefficiente'])) {
                                $this->result_2 = round($infoOrder['coefficiente'] * (float)$this->material_cost, 2);
                                $this->calculation_2 = $infoOrder['coefficiente'];
                            } else {
                                $this->result_2 = 0;
                            }
                        } else {
                            $this->result_2 = 0;
                        }
                        break;
                    default:
                        if (isset($row[$key]) && $row[$key] !== '')
                            $this->$camp = $row[$key];
                }
            }

            if (empty($row[0])) {
                $this->father = $father;
            } else {
                $this->order = $row[0];
                $infoOrder = [];
                $infoOrder['order'] = $row[0];
                $infoOrder['tot_order_qty'] = (float)$this->total_order_quantity;
                $infoOrder['quantity'] = (float)$this->quantity_1;
                if ((float)$this->quantity_1 > 0) {
                    $cal = ((float)$this->total_order_quantity - (float)$this->quantity_1) / (float)$this->total_order_quantity - 1;
                    $infoOrder['coefficiente'] = round(abs($cal), 3);
                }
            }
            $this->head = $head->id;
            $this->date_created = now()->toDateString();

            if ($saveCheck && (float)$this->requirement_quantity > 0 && (float)$this->quantity_2 == 0) {
                if ($fathersWithMissingCheck !== null) {
                    if (!empty($father)) {
                        $fathersWithMissingCheck[$father] = true;
                    }
                } else {
                    WsRow::where('order', $father)
                        ->where('head', $head->id)
                        ->update(['check_row' => true]);
                }
                $this->check_row = true;
            }

            if ($this->machine_cost_manpower) {
                $infoOrder['tot_row_machine_manpower'] = ($infoOrder['tot_row_machine_manpower'] ?? 0) + (float)$this->machine_cost_manpower;
                if (!$saveCheck)
                    $infoOrder['tot_row_machine_manpower2'] = ($infoOrder['tot_row_machine_manpower2'] ?? 0) + (float)$this->machine_cost_manpower;
            }

            if ($this->material_cost) {
                $infoOrder['tot_row_matirial'] = ($infoOrder['tot_row_matirial'] ?? 0) + (float)$this->material_cost;
                if (!$saveCheck)
                    $infoOrder['tot_row_matirial2'] = ($infoOrder['tot_row_matirial2'] ?? 0) + (float)$this->material_cost;
            }

            $this->view = $saveCheck;

            if ($persist) {
                $this->save();
            }

            return $infoOrder;
        } catch (\Throwable $e) {
            \Log::error('WsRow::storeRow error: ' . $e->getMessage(), ['row' => $row]);

            return $infoOrder;
        }
    }

    public function headModel()
    {
        return $this->belongsTo(WsHead::class, 'head');
    }
}
