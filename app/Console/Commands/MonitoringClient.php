<?php

namespace App\Console\Commands;

use App\Models\PlAsset;
use App\Models\PlAssetMonitoring;
use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Revolution\Google\Sheets\Facades\Sheets;


class MonitoringClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monitoring_client';

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
		$giorno = date("D");
		if($giorno != 'Sat' && $giorno != 'Sun'){
			Log::info('Entro');
			$clients = DB::table('pl_assets')->select('id','utente','ip_address','hostname')->where('monitoraggio_attivo',true)->get();

			foreach ($clients as $client){
				$output=shell_exec('ping -n 1 '.$client->ip_address);
				if (strpos($output, 'out') !== false) {
					Log::info('Asset Error: '.$client->hostname);
					//Log::info('Morto: ');
					//Log::info('IP: '.$client->ip_address);
				}
				elseif(strpos($output, 'expired') !== false)
				{
					Log::info('Asset Error: '.$client->hostname);
					//Log::info('Network Error: 1');
					//Log::info('IP: '.$client->ip_address);
				}
				elseif(strpos($output, 'data') !== false)
				{
					$monitoring = new PlAssetMonitoring();
					$monitoring->asset_id = $client->id;
					$monitoring->id_client = strtotime(date('Y-m-d H:i:s'));
					$monitoring->data = date('Y-m-d H:i:s');
					$monitoring->tipo_log = 'Monitoring';
					$monitoring->hostname = $client->hostname;
					$monitoring->save();
				}
				else
				{
					Log::info('Asset Error: '.$client->hostname);
					//Log::info('Network Error: 2');
					//Log::info('IP: '.$client->ip_address);
				}
			}
		}
        
        //Log::info('Monitoring Clienti Esco');
    }
}
