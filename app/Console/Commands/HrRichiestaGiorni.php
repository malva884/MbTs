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

class HrRichiestaGiorni extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:hr-richiesta-giorni';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recupero le richiestte effettuate ogni 5 minuti.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('max_execution_time', -1);
        stream_context_set_default(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
        $token = "exWm8aP5MjxLUj2b28$2Fd";
        $path = 'https://app.metallurgicabresciana.it/turni/mb/richieste/api/get.php?';
        $path .= 'tk=' . $token;
        $getMovieList = file_get_contents($path);
        $result = json_decode($getMovieList);
        if ($result->stato == 200) {
            foreach ($result->list as $dettaglio) {
                $dependente = $this->dipendente($dettaglio->richiesta->matricola);

                //$check = HrHoursRequested::where('bacheca_id',$dettaglio->richiesta->richiesta_id)->first();

                $giorni = [];
                if(/*empty($check->id) && */!empty($dependente->id)){
                    $richiesta = new HrHoursRequested();
                    $richiesta->bacheca_id = $dettaglio->richiesta->richiesta_id;
                    $richiesta->data_richiesta = $dettaglio->richiesta->data_richiesta;
                    $richiesta->bacheca_dipendente_id = $dettaglio->richiesta->dipendente;
                    $richiesta->dipendente_matricola = $dettaglio->richiesta->matricola;
                    $richiesta->dipendente_cognome = $dettaglio->richiesta->cognome;
                    $richiesta->dipendente_nome = $dettaglio->richiesta->nome;
                    $richiesta->tipologia = $dettaglio->richiesta->tipologia;
                    $richiesta->centro_di_costo = $dependente->centro;
                    $richiesta->save();

                    foreach ($dettaglio->giorni as $giorno){
                        $giorni[strtotime($giorno->data)]= $giorno->data;
                        $dettaglio = new HrHoursRequestedDetail();
                        $dettaglio->richiesta_id = $richiesta->id;
                        $dettaglio->bacheca_id = $richiesta->bacheca_id;
                        $dettaglio->bacheca_dipendente_id = $richiesta->bacheca_dipendente_id;
                        $dettaglio->dipendente_matricola = $richiesta->dipendente_matricola;
                        $dettaglio->data = $giorno->data;
                        if(!empty($giorno->ore_richieste)){
                            $hours = floor($giorno->ore_richieste / 60);
                            $min = $giorno->ore_richieste - ($hours * 60);
                            $dettaglio->ore_richieste = $hours.':'.$min;
                            $dettaglio->ora_inizio = $giorno->ora_inizio;
                            $dettaglio->ora_fine = date('H:i', strtotime($dettaglio->data.' '.$giorno->ora_inizio . " +".$giorno->ore_richieste." Minutes"));
                        }
                        $dettaglio->tipologia = $giorno->tipologia;
                        $dettaglio->save();
                    }

                    $approvatori = $this->approvatori($dependente->centro, true, $richiesta->id);

                    // notifica di approvazione
                    if(count($approvatori['email'])){
                        switch ($richiesta->tipologia) {
                            case 1:
                                $info['tipologia'] = 'Ferie';
                                break;
                            case 2:
                                $info['tipologia'] = '104';
                                break;
                            case 5:
                                $info['tipologia'] = 'Permesso';
                                break;
                            case 101:
                                $info['tipologia'] = 'Annulamento Ferie';
                                break;
                            case 102:
                                $info['tipologia'] = 'Annulamento 104';
                            case 105:
                                $info['tipologia'] = 'Annulamento Permesso';
                                break;
                        }
                        $info['dipendente'] = $richiesta->dipendente_cognome.' '.$richiesta->dipendente_nome;
                        $info['matricola'] = $richiesta->dipendente_matricola;
                        $id = $richiesta->id;
                        $subject = 'Nuova Richiesta Da Approvare '. strtotime(date('Y-m-d H:i:s'));
                        ksort($giorni);

                        $tokenEmail = Str::random(5).uniqid();
                        // Creo la riga per approvazione tramite email
                        $this->setApprovazioneEmail($richiesta->bacheca_id, $tokenEmail, implode('-',$approvatori['id']));

                        foreach($approvatori['email'] as $email){
                            if($email['notifica'] == true){
                                $tokenEmailTmp = $tokenEmail.'-'.$richiesta->bacheca_id.'-'.$email['user_id'];
                                $this->email($id,'emails/email_richiesta_giorni_dipendente', $subject, $info, $email['email'], $approvatori['approvatori'], $giorni, $tokenEmailTmp);
                            }

                        }
                    }
                    if(!empty($info['tipologia']))
                        $this->setRichiestaRicevuta($richiesta->bacheca_id, $token);
                    else
                        Log::info('Tipologia Non trovata: '.$richiesta->tipologia);
                }
                else{
                    Log::info('Dipendente Non Trovato: '.$dettaglio->richiesta->matricola);

                }
            }
        }
    }

    private function setApprovazioneEmail($id,$token,$approvatori)
    {
        stream_context_set_default(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);

        $path = 'https://app.metallurgicabresciana.it/turni/mb/richieste/api/set_approvazione.php?';
        $path .= 'tk=' . $token;
        $path .= '&id=' . $id;
        $path .= '&approvatori=' . $approvatori;
        $getMovieList = file_get_contents($path);
        $result = json_decode($getMovieList);
    }

    private function dipendente($matricola)
    {
        $dependente = DB::connection('mysql_old')->table('employees')
            ->select('id', 'centro')
            ->where('matricola', $matricola)
            ->first();

        return $dependente;
    }

    private function approvatori($centro, $set = false, $richiesta_id = null)
    {

        $userNotifiche = HrApproverRequest::select('users.email','users.full_name','users.id','hr_approver_requests.livello','hr_approver_requests.notifica')
            ->join('users','users.id','hr_approver_requests.user_id')
            ->where('centro_ci_costo',$centro)
            ->where('disattivo','=','false')
            ->orderBy('livello','asc')
            ->get();

        $livello = null;
        $result = [];
        foreach ($userNotifiche as $user){
            if(is_null($livello) || $livello == $user->livello){
                $livello = $user->livello;

                //if($set){
                $approval = new HrRequestPending();
                $approval->richiesta_id = $richiesta_id;
                $approval->user_id = $user->id;
                $approval->approvatore = $user->full_name;
                $approval->livello = $user->livello;
                $approval->save();
                //}

                $result['approvatori'][$user->full_name] = $user->full_name;
                $result['email'][] = [
                    'user_id' 	=> $user->id,
                    'email' => $user->email,
                    'notifica' => ($user->notifica == true ? true : false),
                ];
                $result['id'][] = $user->id;
                //$result['email']['email']= $user->email;
            }
        }

        return $result;
    }

    private function setRichiestaRicevuta($id, $token)
    {
        $path = 'https://app.metallurgicabresciana.it/turni/mb/richieste/api/inviato.php?';
        $path .= 'tk=' . $token;
        $path .= '&id='. $id;
        $getMovieList = file_get_contents($path);
    }

    private function annulla_richiesta($bacheca_id)
    {
        $richiesta = HrHoursRequested::where('bacheca_id',$bacheca_id)->first();

        $obj = HrRequestPending::where('richiesta_id',$richiesta->id)
            ->whereNotNull('stato')
            ->first();

        return empty($obj->id);
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
