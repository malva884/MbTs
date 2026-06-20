<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrWarehouseRows extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id', 'warehouse_id', 'materiale', 'descrizione', 'quantita', 'fibre', 'um', 'valore_unitario', 'valore_totale', 'crcy', 'ultimo_movimento','classe'
    ];


    public static function processing($row)
    {

        $tmp[1] = substr($row['material'], 0, 1);
        $tmp[2] = substr($row['material'], 1, 1);
        $tmp[3] = substr($row['material'], 2, 1);
        switch ($tmp[1]) {
            case 'P':
                if (is_numeric($tmp[2])) {
                    return self::calc('Packaging', $row);
                } elseif ($tmp[2] == 'E' && $tmp[3] == 'F') {
                    return self::calc('Raw Materials OFC', $row);
                }
                elseif ($tmp[2] == 'E') {
                    return self::calc('WIP OFC', $row);
                }else {
                    return self::calc('Raw Materials OFC', $row);
                }
                break;
            case 'T':
                if ($tmp[2] == 'B') {
                    return self::calc('WIP OFC', $row);
                }else {
                    return self::calc('Raw Materials OFC', $row);
                }
                break;
            case 'B':
                if ($tmp[2] == 'U') {
                    return self::calc('WIP OFC', $row);
                } elseif ($tmp[2] == 'E') {
                    return self::calc('Fiber Optics OFC', $row);
                } else {
                    return self::calc('Raw Materials OFC', $row);
                }
                break;
            case 'C':
                if (is_numeric($tmp[2])) {
                    return self::calc('Raw Materials CC', $row);
                } elseif ($tmp[2] == 'O') {
                    return self::calc('Fiber Optics OFC', $row);
                } else {
                    return self::calc('Raw Materials OFC', $row);
                }
                break;
            case 'D':
                if ($tmp[2] == 'O') {
                    return self::calc('Fiber Optics OFC', $row);
                } else {
                    return self::calc('Raw Materials OFC', $row);
                }
                break;
            case 'S':
                if ($tmp[1] == 'S' && is_numeric($tmp[2])) {
                    return self::calc('WIP CC', $row);
                } elseif ($tmp[2] == 'Z') {
                    return self::calc('WIP OFC', $row);
                } elseif ($tmp[2] == 'F') {
                    return self::calc('Finished Products OFC', $row);
                } elseif ($tmp[2] == 'M') {
                    return self::calc('Fiber Optics OFC', $row);
                } else {
                    return self::calc('Raw Materials OFC', $row);
                }
                break;
            case 'F':
                if (is_numeric($tmp[2]) && $tmp[2] != 8) {
                    return self::calc('Finished Products CC', $row);
                } elseif ($tmp[2] == 'C' || (is_numeric($tmp[2]) && $tmp[2] == 8)) {
                    return self::calc('Finished Products OFC', $row);
                } elseif ($tmp[2] == 'J') {
                    return self::calc('Finished Products CC', $row);
                 } elseif ($tmp[2] == 'I' && $tmp[2] == 'L') {
                    return self::calc('WIP OFC', $row);
                } else {
                    return self::calc('Raw Materials OFC', $row);
                }
                break;
            case 'R':
                if (is_numeric($tmp[2]) && ($tmp[2] != 8 && $tmp[2] != 9)) {
                    return self::calc('Raw Materials CC', $row);
                } elseif (is_numeric($tmp[2]) && ($tmp[2] == 8 || $tmp[2] == 9)) {
                    return self::calc('Raw Materials OFC', $row);
                } else {
                    return self::calc('Raw Materials OFC', $row);
                }
                break;
            case 'M':
                if ($tmp[2] == 'U') {
                    return self::calc('Fiber Optics OFC', $row);
                } else {
                    return self::calc('Raw Materials OFC', $row);
                }
                break;
            case 'N':
                if ($tmp[2] == 'O') {
                    return self::calc('Fiber Optics OFC', $row);
                } else {
                    return self::calc('Raw Materials OFC', $row);
                }
                break;
            default:
                return self::calc('Raw Materials OFC', $row);
        }
    }


    private static function calc($class, $row)
    {

        $fkm = null;
        $fiber_count = '';
        $values = [];
        if (in_array($class, ['Finished Products OFC', 'Raw Materials OFC', 'WIP OFC', 'Fiber Optics OFC'])) {
            if($class == 'Finished Products OFC' || $class == 'WIP OFC'){
                $result = DB::connection('sqlsrv_gp')->table('AGG_PRODOTTI_TMP')->select('id', 'Conversione')->where('cdProdotto', $row['material'])->first();
                $fiber_count = (!empty($result->Conversione) ? $result->Conversione : 1);
                $fkm = round($row['total_stock'] * $fiber_count, 2);
            }
            $values['fkm_ofc'] = $fkm / 1000;
            $values['ckm_ofc'] = $row['total_stock'];
            $values['valore_ofc'] = $row['total_value'];
            $values['ckm_cc'] = 0;
            $values['valore_cc'] = 0;
        } else {
            $values['ckm_cc'] = $row['total_stock'];
            $values['valore_cc'] = $row['total_value'];
            $values['fkm_ofc'] = 0;
            $values['ckm_ofc'] = 0;
            $values['valore_ofc'] = 0;
        }

        $material_class = [
            'ckm' => $row['total_stock'],
            'fkm' => $fkm,
            'valore' => $row['total_value'],
        ];

        $lastDate = date_create($row['last_gds_mvmt']);

        $sheet = [$row['material'], $row['description'], round($row['total_stock'],3), (!empty($row['bun']) ? $row['bun']:'EUR'), intval($fiber_count), (is_null($fkm) ? '' : $fkm), round($row['unitary_value'],2), round($row['total_value'],2), date_format($lastDate, "m/d/Y"), $class];
        return ['sheet' => $sheet, 'values' => $values, 'material_class' => $material_class, 'class' => $class];
    }
}
