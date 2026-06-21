<?php

namespace App\Jobs;

use App\Models\RegistroAccountWifi;
use App\Models\Utility;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use phpseclib3\Net\SSH2;

class CredenzialiWifi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $id;
	protected $registerLog;
    protected $host = "10.141.1.100";
    protected $username = "it-admin";
    protected $password = "F-X@G6.zTl@mif2T";

    /**
     * Create a new job instance.
     */
    public function __construct($id,$registerLog = null)
    {
        $this->id = $id;
        $this->registerLog = $registerLog;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
		ini_set('max_execution_time', -1);
		Log::info('Open Wifi ');
        if($this->id)
            $obj = RegistroAccountWifi::find($this->id);
        else
            $obj = RegistroAccountWifi::where('register_id',$this->registerLog)->orderBy('created_at', 'desc')->first();

		$accounts = $obj;
        try {
            $ssh = new SSH2($this->host);
            if (!$ssh->login($this->username, $this->password)) {
                throw new \Exception("Login failed.");
            }

            $checkUser = $this->checkUser($ssh, $obj->username);

            if(!$checkUser){
                $startDate = Carbon::now();
                //$endDate = Carbon::createFromFormat('Y-m-d H:i:s', $obj->data_fine.' 20:00:00');
				$endDate = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d').' 20:00:00');
                $days = $startDate->diff($endDate);

                //$anni = $days->format('%y');
				$anni = 0;
                //$mesi = $days->format('%m');
				$mesi = 0;
                //$giorni = $days->format('%d');
				$giorni = 0;
                $ore = $days->format('%h');
                $minuti = 0;
				

                //$azienda = "description {$minuti}\n";
                //$final = "type network-user description $obj->azienda guest-user max-login-limit 0 lifetime year ".$anni." month ".$mesi." day ".$giorni." hour ".$ore." minute ".$minuti." second 0\n";
                $ssh->read('itl-s-wlc-cl#');
                $ssh->write("configure terminal\n");
                $ssh->read('itl-s-wlc-cl(config)#');
                $ssh->write("user-name ".$obj->username."\n");
                $ssh->read('itl-s-wlc-cl(config)#');
                $ssh->write("description Esterno\n");
                $ssh->read('itl-s-wlc-cl(config)#');
                $ssh->write("password 0 ".$obj->password."\n");
                $ssh->read('itl-s-wlc-cl(config)#');
                $ssh->write("type network-user description Esterno guest-user max-login-limit 0 lifetime year ".$anni." month ".$mesi." day ".$giorni." hour ".$ore." minute ".$minuti." second 0\n");
                $ssh->read('itl-s-wlc-cl(config)#');
                $ssh->write("exit\n");
            }

			Log::info('Ok Wifi ');
        } catch (\Exception $e) {
			Log::info('Error Wifi ');
		/*	Log::info('OPS... Wifi Error ');
			//Log::info('Errore Creazione Wifi: '.$obj->username);
			Log::info($e->getMessage());
            $users = Utility::users_notify(['richiesta_wifi']);
			
			$content = 'errore wifi ';
            Mail::send('emails/email_white', compact('content'), function ($message) use ($users) {
                $message
                    ->to('gregorio.grande@stl.tech')
                    ->subject('Errore Creazione Credenziali Wifi');
            });*/
        }

        $ssh->disconnect();
    }

    private function checkUser($ssh, $username)
    {
        try {
            $ssh->read('itl-s-wlc-cl#');
            $ssh->write("show aaa local guest_user ".$username."\n");
            $result = $ssh->read('itl-s-wlc-cl#');
            $arr_user = explode(" : ",$result);
            // verifico che l'account dell'utente sia presente.
            if(!empty($arr_user[1])){
                $ssh->write("exit\n");
                return true;
            }
            else
                return false;

        } catch (\Exception $e) {
            return false;
        }
    }
}
