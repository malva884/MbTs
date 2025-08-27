<?php

namespace App\Console\Commands;

use App\Models\PlAsset;
use App\Models\PlAssetMonitoring;
use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Revolution\Google\Sheets\Facades\Sheets;


class MonitoringAsset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monitoring_asset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitoraggio Connettivita Asset';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('max_execution_time', -1);
        $rang = date('Y-m-d h:i:s',strtotime('-10 minutes'));
        $date = strtotime(date('Y-m-d H:i:s').' +1 hours');

        $objs = DB::table('pl_asset_monitorings')
            ->join('pl_assets','pl_assets.id','pl_asset_monitorings.asset_id')
            ->select(DB::raw('ROW_NUMBER() OVER(PARTITION BY asset_id ORDER BY data DESC) AS RowNum, pl_asset_monitorings.*, pl_assets.ultima_notifica'))
            ->orderBy('data', 'desc')
            ->get();

        $emails = Utility::users_notify(['it_monitoring_asset']);
        $dd = $objs->where('RowNum',1);
        $assetProblem = [];
        foreach ($dd as $key => $obj){
            $assetProblem[$obj->asset_id] = strtotime($obj->data);
            if($obj->tipo_log != 'Spegnimento' && $obj->data <= $rang && (is_null($obj->ultima_notifica) || strtotime($obj->ultima_notifica) >= $date)){
                Mail::send('emails/email_asset_problem', compact('obj'), function ($message) use ($emails) {
                    $message
                        ->to($emails)
                        ->subject('Asset Problem');
                });

                $j = PlAsset::find($obj->asset_id);
                $j->ultima_notifica = date('Y-m-d H:i:s');
                $j->save();
            }
        }

        $assets = PlAsset::select('id','ultima_notifica')
            ->whereNotNull('ultima_notifica')
            ->get();
        foreach ($assets as $asset){
            if(!empty($assetProblem[$asset->id]) && $assetProblem[$asset->id] > strtotime($asset->ultima_notifica)){
                $asset->ultima_notifica = null;
                //$asset->save();

                $info = [
                    'richiesta' => 'Asset Problem Resolved',
                    'device' => $asset->hostName,
                ];

                $users = Utility::users_notify(['it_richiesta_assistenza']);

                Mail::send('emails/email_assistenza', compact('info'), function ($message) use ($users) {
                    $message
                        ->to($users)
                        ->subject('Asset Problem Resolved');
                });
            }
        }

    }
}
