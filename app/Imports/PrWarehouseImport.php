<?php

namespace App\Imports;

use App\Models\FiGoodsTransitRow;
use App\Models\PrWarehouseRows;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

class PrWarehouseImport implements ToModel, WithHeadingRow
{
    // WithHeadingRow
    private $head = null;
    public $result = ['valore_ofc'=>0,'valore_cc'=>0,'fkm_ofc'=>0,'ckm_ofc'=>0,'ckm_cc'=>0];
    public $material_class = [];

    public $sheet = [];
    public function __construct($headId)
    {
        $this->head = $headId;
        $this->sheet[] = ['Material','Description','Total Stock','Unit','Fiber Count','Fkm','Unit cost','Total','Last movement','Class'];

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
        if(empty($row['last_gds_mvmt'])){
            Log::channel('stderr')->info($row);
            dd();
        }

        if(!empty($row['material'])){
            $unix_date = ($row['last_gds_mvmt'] - 25569) * 86400;
            $excel_date = 25569 + ($unix_date / 86400);
            $unix_date = ($excel_date - 25569) * 86400;
            $row['last_gds_mvmt'] =  gmdate("d-m-Y", $unix_date);

            $result = PrWarehouseRows::processing($row);

            $this->result['valore_ofc'] += $result['values']['valore_ofc'];
            $this->result['valore_cc'] += $result['values']['valore_cc'];
            $this->result['fkm_ofc'] += $result['values']['fkm_ofc'];
            $this->result['ckm_ofc'] += $result['values']['ckm_ofc'];
            $this->result['ckm_cc'] += $result['values']['ckm_cc'];

            $this->sheet[] = $result['sheet'];

           if(!empty($this->material_class[$result['class']])){
               $this->material_class[$result['class']]['ckm'] += $result['material_class']['ckm'];
               $this->material_class[$result['class']]['fkm'] += $result['material_class']['fkm'];
               $this->material_class[$result['class']]['valore'] += $result['material_class']['valore'];
           }else{
               $this->material_class[$result['class']]['ckm'] = $result['material_class']['ckm'];
               $this->material_class[$result['class']]['fkm'] = $result['material_class']['fkm'];
               $this->material_class[$result['class']]['valore'] = $result['material_class']['valore'];
           }


            return new PrWarehouseRows([
                'warehouse_id' => $this->head,
                'materiale' => $row['material'],
                'descrizione' => $row['description'],
                'quantita' => $row['total_stock'],
                'um' => $row['bun'],
                'valore_unitario' => $row['unitary_value'],
                'valore_totale' => $row['total_value'],
                'crcy' => $row['crcy'],
                'ultimo_movimento' => $row['last_gds_mvmt'],
                'classe' => $result['class'],
            ]);
        }
    }
}
