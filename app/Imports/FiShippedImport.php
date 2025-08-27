<?php

namespace App\Imports;

use App\Models\FiShippedRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

class FiShippedImport implements ToModel, WithStartRow
{
    // WithHeadingRow
    private $head = null;
    public $result = ['target_cc'=>0,'target_ofc'=>0,'target_fkm'=>0,'target_ckm'=>0,'target_ofc_ckm'=>0];
    public function __construct($headId)
    {
        $this->head = $headId;

    }

    public function startRow(): int
    {
        return 2;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {

        if($row[2] && $row[4]){
            $unix_date = ($row[3] - 25569) * 86400;
            $excel_date = 25569 + ($unix_date / 86400);
            $unix_date = ($excel_date - 25569) * 86400;
            $row[3] = gmdate("Y-m-d", $unix_date);

            //$cliente = $row[5];
            if($row[5] == 'TELECOM ITALIA SPA'){
                if (strpos($row[8], 'FC') !== false)
                    $row[5] = 'TELECOM ITALIA SPA - FIBERCOP';
            }

            $row[22] = str_replace(",", "", $row[22]);
            $row[23] = str_replace(",", "", $row[23]);
            $row[25] = str_replace(",", "", $row[25]);
            $row[26] = str_replace(",", "", $row[26]);
            $row[27] = str_replace(",", "", $row[27]);
            $row[28] = str_replace(",", "", $row[28]);
            $row[29] = str_replace(",", "", $row[29]);
            $row[31] = str_replace(",", "", $row[31]);
            $rate = explode(',', $row[32]);



            $value = number_format((float)$row[21], 2, '.', '');
            $qty = number_format($row[24], 3, '.', '');
            $qty_fkm = number_format($row[25], 3, '.', '');
            $fiber_counter = 0;
            if($row[11] == '5441'){
                $this->result['target_cc']+= $value;
                $this->result['target_ckm']+= $qty;
                $fiber_counter = $row[23];
            }
            elseif ($row[11] == '5420'){
                $this->result['target_ofc']+= $value;

                $this->result['target_ofc_ckm']+= $qty;
                if($row[23]){
                    $rmaterial = DB::connection('sqlsrv_gp')->table('AGG_PRODOTTI_TMP')->where('cdProdotto',$row[7] )->first();
                    if(!empty($rmaterial->Conversione)){
                        $this->result['target_fkm']+= $qty_fkm;
                    }

                }else{
                    $this->result['target_fkm']+= $qty_fkm;
                    $fiber_counter = $row[23];
                }

            }

            return new FiShippedRow([
                'head' => $this->head,
                'date_row' => $row[3],
                'code_client' => $row[4],
                'client' => $row[5],
                'item' => $row[6],
                'material' => $row[7],
                'description' => $row[8],
                'type' => $row[11],
                'commessa' => $row[16],
                'code_recipient' => $row[18],
                'recipient' => $row[19],
                'unit' => $row[20],
                'qty_value' => $value,
                'cost_value' => number_format($row[22], 2, '.', ''),
                'fiber_counter' => $fiber_counter,
                'delivered_qty' => $qty,
                'qty_fkm' => $qty_fkm,
                'price_km' => number_format($row[26], 2, '.', ''),
                'cost_km' => number_format($row[27], 2, '.', ''),
                'std_price' => number_format($row[28], 2, '.', ''),
                'order' => $row[29],
                'net_profit' => number_format($row[30], 2, '.', ''),
                'profit_perc' => $row[31],
                'exchange_rate' => $rate[0],
                'postal_code' => str_replace(" ","",$row[33]),
                'city' => $row[34],
                'document' => $row[35],
            ]);

        }
    }

}
