<?php

namespace App\Jobs;


use App\Models\PrWarehouseRows;
use App\Models\Utility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MagazzinoEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $id;

    /**
     * Create a new job instance.
     */
    public function __construct($id)
    {
        $this->id = $id;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $dataBy = date('Y-m-01') . ' to ' . date('Y-m-d');

        $values = ['valore_ofc' => 0, 'valore_cc' => 0, 'fkm_ofc' => 0, 'ckm_ofc' => 0, 'ckm_cc' => 0];
        $totale = 0;
        $material_class = [];

        $materials = DB::table('pr_warehouse_rows')->select('materiale as material', 'descrizione as description', 'valore_unitario as unitary_value', 'quantita as total_stock', 'ultimo_movimento as last_gds_mvmt', 'valore_totale as total_value', 'crcy as bun')
            ->where('warehouse_id', $this->id)
            ->get();

        $head = DB::table('pr_warehouse_heads')->where('id', $this->id)->first();

        foreach ($materials as $material) {
            $result = PrWarehouseRows::processing((array)$material);
            $values['valore_ofc'] += $result['values']['valore_ofc'];
            $values['valore_cc'] += $result['values']['valore_cc'];
            $values['fkm_ofc'] += $result['values']['fkm_ofc'];
            $values['ckm_ofc'] += $result['values']['ckm_ofc'];
            $values['ckm_cc'] += $result['values']['ckm_cc'];

            if (!empty($material_class[$result['class']])) {
                $material_class[$result['class']]['ckm'] = round($material_class[$result['class']]['ckm'] + $result['material_class']['ckm'], 3);
                $material_class[$result['class']]['fkm'] = round($material_class[$result['class']]['fkm'] + $result['material_class']['fkm'], 3);
                $material_class[$result['class']]['valore'] += round($result['material_class']['valore'], 2);
            } else {
                $material_class[$result['class']]['ckm'] = round($result['material_class']['ckm'], 3);
                $material_class[$result['class']]['fkm'] = round($result['material_class']['fkm'], 3);
                $material_class[$result['class']]['valore'] = round($result['material_class']['valore'], 2);
            }
            $totale = $totale + round($result['material_class']['valore'], 2);
        }

        ksort($material_class);
        $material_class['Total Warehouse'] = ['valore'=>$totale,'ckm'=>0,'fkm'=>0];
        $corsoLavori = ['valore'=>$head->corso_lavori,'ckm'=>0,'fkm'=>0];
        $totale = $totale + $head->corso_lavori;


        $subject = 'Report Warehouse';
        $users = Utility::users_notify(['test_system']);

        Mail::send('emails/email_magazzino', compact('material_class','corsoLavori','totale','head'), function ($message) use ($users, $subject) {
            $message
                ->to(array_values($users))
                ->subject($subject);
        });
    }
}
