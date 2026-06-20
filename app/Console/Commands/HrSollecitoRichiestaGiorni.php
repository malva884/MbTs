<?php

namespace App\Console\Commands;

use App\Models\HrApproverRequest;
use App\Models\HrHoursRequested;
use App\Models\HrHoursRequestedDetail;
use App\Models\HrRequestPending;
use App\Models\Utility;
use App\Services\GoogleDrive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class HrSollecitoRichiestaGiorni extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:hr-sollecito-richiesta-giorni';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sollecito Approvazione Richieste In Approvazione';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('max_execution_time', -1);
        $richieste = HrHoursRequested::whereNull('stato')->get();
        foreach ($richieste as $richiesta){
            $tipologia = '';
            $giorni = HrHoursRequestedDetail::where('richiesta_id',$richiesta->id)->orderBy('data')->get();

            switch ($richiesta->tipologia) {
                case 1:
                    $tipologia = 'Ferie';
                    break;
                case 2:
                    $tipologia = '104';
                    break;
                case 5:
                    $tipologia = 'Permesso';
                    break;
                case 101:
                    $tipologia = 'Ferrie Revocate';
                    break;
                case 102:
                    $tipologia = '104 Revocate';
                    break;
            }
            $info['dipendente'] = $richiesta->dipendente_cognome.' '.$richiesta->dipendente_nome;
            $info['matricola'] = $richiesta->dipendente_matricola;
            $info['tipologia'] = $tipologia;

            $d = [];
            foreach ($giorni as $giorno)
                $d[] = $giorno->data;

            stream_context_set_default(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);

            $path = 'https://app.metallurgicabresciana.it/turni/mb/richieste/api/get_approvazione.php?';
            $path.= 'richiesta='. $richiesta->bacheca_id;
            $getMovieList = file_get_contents($path);
            $result = json_decode($getMovieList);

            $tokenEmail = '';
            if ($result->stato == 200) {
                $tokenEmail = $result->token;
            }

            $approvatori = $this->approvatori($richiesta->id);

            $subject = 'Notifica Richiesta In Approvazione '. strtotime(date('Y-m-d H:i:s'));
            // notifica di approvazione
            foreach($approvatori['users'] as $user){
                $tokenEmailTmp = $tokenEmail.'-'.$richiesta->bacheca_id.'-'.$user['user_id'];
                $this->email($richiesta->id,'emails/email_richiesta_giorni_dipendente', $subject, $info, $user['email'], $approvatori['approvatori'], $d, $tokenEmailTmp);
            }
        }

    }

    private function approvatori($richiestaId)
    {
        $approvatori = HrRequestPending::select('users.full_name','users.id','users.email')
            ->join('users','users.id','hr_request_pendings.user_id')
            ->where('richiesta_id',$richiestaId)
            ->whereNull('hr_request_pendings.stato')
            ->get();

        $result = [];
        foreach ($approvatori as $approvatore){
            $result['approvatori'][] = $approvatore->full_name;
            $result['$approvatori_id'][] = $approvatore->id;
            $result['users'][] = [
                'user_id' 	=> $approvatore->id,
                'email' => $approvatore->email,
            ];
        }

        return $result;
    }

    private function email($id, $template, $oggetto, $info, $email, $approvatori, $giorni, $token = null)
    {

        Mail::send($template, compact('id','approvatori','giorni','info', 'token'), function ($message) use ($email,$oggetto) {
            $message
                ->to($email)
                ->subject($oggetto);
        });
    }
}
