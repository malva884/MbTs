<?php

namespace App\Console\Commands;

use App\Models\Utility;
use App\Models\PlAsset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
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
		try {
			//ini_set('memory_limit', '64M');
			$giorno = date("D");
			if($giorno != 'Sat' && $giorno != 'Sun'){
				ini_set('max_execution_time', -1);
				$rang = date('Y-m-d H:i:s',strtotime('-10 minutes'));
				$date = strtotime(date('Y-m-d H:i:s').' +1 hours');
				$g = date('d');
				if($g <= 5)
					$g = 1;
				else
					$g = date('d', strtotime(' -5 day'));
				$objs = DB::table('pl_asset_monitorings')
					->join('pl_assets','pl_assets.id','pl_asset_monitorings.asset_id')
					->select(DB::raw('ROW_NUMBER() OVER(PARTITION BY asset_id ORDER BY data DESC) AS RowNum, pl_asset_monitorings.*, pl_assets.ultima_notifica, pl_assets.hostName, pl_assets.utente'))
					->whereYear('data',date('Y'))
					->whereMonth('data',date('m'))
					->whereDay('data','>=',$g)
					->orderBy('data', 'desc')
					->get();

				$emails = Utility::users_notify(['it_monitoring_asset']);
				$dd = $objs->where('RowNum',1);
				$assetProblem = [];
				foreach ($dd as $key => $obj){
					$assetProblem[$obj->asset_id] = strtotime($obj->data);
					if($obj->tipo_log != 'Spegnimento' && strtotime($obj->data) <= strtotime($rang) && (is_null($obj->ultima_notifica) || strtotime($obj->ultima_notifica) >= $date)){
						Mail::send('emails/email_asset_problem', compact('obj'), function ($message) use ($emails, $obj) {
							$message
								->to($emails)
								->subject($obj->utente);
						});

						$j = PlAsset::find($obj->asset_id);
						$j->ultima_notifica = date('Y-m-d H:i:s');
						$j->save();
						
					}
				}

				$assets = PlAsset::select('id','ultima_notifica','hostName','utente')
					->whereNotNull('ultima_notifica')
					->get();

				foreach ($assets as $asset){
					if(!empty($assetProblem[$asset->id]) && $assetProblem[$asset->id] > strtotime($asset->ultima_notifica)){
						$asset->ultima_notifica = null;
						$asset->save();

						$info = [
							'richiesta' => 'Asset Problem Resolved',
							'device' => $asset->hostName,
							'utente' => $asset->utente,
						];

						$users = Utility::users_notify(['it_richiesta_assistenza']);

						Mail::send('emails/email_assistenza', compact('info'), function ($message) use ($users, $info) {
							$message
								->to($users)
								->subject($info['utente']);
						});
					}
				}
			}
			
		} catch (Exception $e) {
			
			Log::info('Monitoring Asset');

			Log::info($e->getMessage());
			
		}
    }
}
