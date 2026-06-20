<?php

namespace App\Jobs;

use App\Models\HrApproverRequest;
use App\Models\HrHoursRequested;
use App\Models\HrHoursRequestedDetail;
use App\Models\HrRequestPending;
use App\Models\Utility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RichiesteGiorniDipendenti implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id;
    protected $user_id;



    /**
     * Create a new job instance.
     */
    public function __construct($id, $user_id)
    {
        $this->id = $id;
        $this->user_id = $user_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        ini_set('max_execution_time', -1);
        // Recupero l'ultima approvazione effettuata
        $pending = HrRequestPending::where('richiesta_id',$this->id)
            ->where('user_id',$this->user_id)
            ->orderby('livello','desc')
            ->orderby('updated_at','desc')
            ->first();

        // Recupero la richiesta del dipendente
        $richiesta = HrHoursRequested::where('id',$pending->richiesta_id)->first();
        $id = $richiesta->id;
        $giorni = HrHoursRequestedDetail::where('richiesta_id',$richiesta->id)->orderBy('data')->get();

        // se l'ultimo approvatore a bocciato la richiesta
        if($pending->stato == 0){
            Log::info('Bocciato');
            $richiesta->stato = $pending->stato;
            $richiesta->note = $pending->nota;
            $richiesta->save();
            DB::table('hr_hours_requested_details')->where('richiesta_id',$richiesta->id)->update(array('confermato' => false));

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
                case 105:
                    $tipologia = 'Permesso Revocato';
                    break;
            }

            $info['dipendente'] = $richiesta->dipendente_cognome.' '.$richiesta->dipendente_nome;
            $info['matricola'] = $richiesta->dipendente_matricola;
            $info['tipologia'] = $tipologia;

            $subject = 'Richiesta Negata '. strtotime(date('Y-m-d H:i:s'));
            // Recupero gli utenti da notificare
            $users = Utility::users_notify(['hr_richieste_negate']);
            // notifica di approvazione
            $this->email($id,'emails/email_richiesta_giorni_nagata', $subject, $info, $users, [],$giorni);

            stream_context_set_default(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
            $token = "exWm8aP5MjxLUj2b28$2Fd";
            $path = 'https://app.metallurgicabresciana.it//turni/mb/richieste/api/set.php?';
            $path .= 'tk=' . $token;
            $path .= '&id=' . $richiesta->bacheca_id;
            $path .= '&stato=' . $pending->stato;
            $path .= '&nota=' . urlencode($pending->nota);
            $getMovieList = file_get_contents($path);
        }
        else{
            $usr = $this->user_id;
            // Cerco se ci sono altri approvatori
            $usersNotifica = HrApproverRequest::select('users.email','users.full_name','users.id','hr_approver_requests.livello','hr_approver_requests.notifica')
                ->join('users','users.id','hr_approver_requests.user_id')
                ->where('centro_ci_costo',$richiesta->centro_di_costo)
                ->where(function ($query) use ($richiesta,$pending,$usr) {
                    $livello = DB::table('hr_approver_requests')
                        ->select('livello')
                        ->where('centro_ci_costo',$richiesta->centro_di_costo)
                        ->where('notifica',1)
                        ->where('livello','>',(integer)$pending->livello)
                        ->where('user_id','<>',$usr)
                        ->orderBy('livello','asc')
                        ->first();

                    $query->Where('livello', (!empty($livello->livello) ? $livello->livello : 100));
                })
                //->where('livello','=',(integer)$pending->livello + 1)
                //->where('user_id','<>',$this->user_id)
                ->where('disattivo','=','false')
                //->orderBy('livello','asc')
                ->get();
            Log::info('Richiesta: '.$this->id.' Utente AP.:'.$this->user_id.' Livello: '.$pending->livello);
            Log::info($usersNotifica);
            // se c'è una'altro approvatore creo un nuova riga
            if($usersNotifica->count()){
                //$livello = $pending->livello + 1;
                $approvatori = [];
                $users = [];
                $approvatori_id = [];
                foreach ($usersNotifica as $user){
                    $approval = new HrRequestPending();
                    $approval->richiesta_id = $richiesta->id;
                    $approval->user_id = $user->id;
                    $approval->approvatore = $user->full_name;
                    $approval->livello = $user->livello;
                    $approval->save();

                    $dipendente = $richiesta->dipendente_cognome.' '.$richiesta->dipendente_nome;

                    $subject = 'Nuova Richiesta Da Approvare '. strtotime(date('Y-m-d H:i:s'));
                    $approvatori[] = $user->full_name;
                    $approvatori_id[] = $user->id;
                    //$users[]= $user->email;
                    $users[] = [
                        'user_id' 	=> $user->id,
                        'email' => $user->email,
                        'notifica' => $user->notifica,
                    ];
                }
                $info = [];
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
                        break;
                    case 105:
                        $info['tipologia'] = 'Annulamento Permesso';
                        break;
                }

                $info['dipendente'] = $richiesta->dipendente_cognome.' '.$richiesta->dipendente_nome;
                $info['matricola'] = $richiesta->dipendente_matricola;

                $d = [];
                foreach ($giorni  as $giorno)
                    $d[] = $giorno->data;

                $tokenEmail = Str::random(5).uniqid();
                // Creo la riga per approvazione tramite email
                $this->setApprovazioneEmail($richiesta->bacheca_id, $tokenEmail, implode('-',$approvatori_id));

                // notifica di approvazione
                foreach($users as $user){
                    if($user['notifica'] == true){
                        $tokenEmailTmp = $tokenEmail.'-'.$richiesta->bacheca_id.'-'.$user['user_id'];
                        $this->email($id,'emails/email_richiesta_giorni_dipendente', $subject, $info, $user['email'], $approvatori, $d, $tokenEmailTmp);

                    }
                }

                // notifica di approvazione
                //$this->email($id,'emails/email_richiesta_giorni_dipendente', $subject, $info, $users, $approvatori,$d);
            }else{
                // chiudo la richiesta di apporvazione
                $richiesta->stato = $pending->stato;
                $richiesta->note = $pending->nota;
                $richiesta->save();
                DB::table('hr_hours_requested_details')->where('richiesta_id',$richiesta->id)->update(array('confermato' => true));


                stream_context_set_default(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
                $token = "exWm8aP5MjxLUj2b28$2Fd";
                $path = 'https://app.metallurgicabresciana.it//turni/mb/richieste/api/set.php?';
                $path .= 'tk=' . $token;
                $path .= '&id=' . $richiesta->bacheca_id;
                $path .= '&stato=' . $pending->stato;
                $path .= '&nota=' . urlencode($pending->nota);
                $getMovieList = file_get_contents($path);

                $dipendente = $richiesta->dipendente_cognome.' '.$richiesta->dipendente_nome;
                $matricola = $richiesta->dipendente_matricola;
                switch ($richiesta->tipologia) {
                    case 1:
                        $tipologia = 'Ferie';
                        $this->setPresenze($matricola, $giorni, 1, 8);
                        break;
                    case 2:
                        $tipologia = '104';
                        $this->setPresenze($matricola, $giorni, 4, 8);
                        break;
                    case 5:
                        $tipologia = 'Permesso';
                        $this->setPresenze($matricola, $giorni, 5, 1);
                        break;
                    case 101:
                        $tipologia = 'Ferrie Revocate';
                        $this->setPresenze($matricola, $giorni, 0, 0);
                        break;
                    case 102:
                        $tipologia = '104 Revocate';
                        $this->setPresenze($matricola, $giorni, 0, 0);
                        break;
                    case 105:
                        $tipologia = 'Permesso Revocato';
                        $this->setPresenze($matricola, $giorni, 0, 0);
                        break;
                }
                $subject = 'Richiesta Approvata '. strtotime(date('Y-m-d H:i:s'));
                // Recupero gli utenti da notificare
                $users = Utility::users_notify(['hr_richieste_approvate']);
                // notifica di approvazione
                $info['dipendente'] = $dipendente;
                $info['matricola'] = $matricola;
                $info['tipologia'] = $tipologia;
                $this->email($id,'emails/email_richiesta_giorni_approvata', $subject, $info, $users, [],$giorni);
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

    private function email($id, $template, $oggetto, $info, $email, $approvatori, $giorni, $token = null)
    {

        Mail::send($template, compact('id','approvatori','giorni','info', 'token'), function ($message) use ($email,$oggetto) {
            $message
                ->to($email)
                ->subject($oggetto);
        });
    }

    // verifica se il giorno richiesto è gia presente nella tabella presenze personale del vecchio portale
    private function setPresenze($matricola, $date, $tipologia, $ore)
    {
        try{
            foreach ($date as $data){
                $obj = DB::connection('mysql_old')->table('employees_attendances')
                    ->where('matricola',$matricola)
                    ->where('start_date',$data->data)
                    ->first();

                if(!empty($obj->id)){
                    $this->updateDb($obj->id, $tipologia, $ore); #TODO Attivre
                }else{
                    $dependente = DB::connection('mysql_old')->table('employees')
                        ->select('id', )
                        ->where('matricola', $matricola)
                        ->first();

                    $this->insertDb($dependente->id, $matricola, $data->data, $tipologia, $ore); #TODO Attivre
                }
            }
        } catch (\Exception $e) {


        }

    }

    private function insertDb($dependente, $matricola, $data, $tipologia, $ore)
    {
        DB::connection('mysql_old')->table('employees_attendances')->insert(
            [
                'matricola' => $matricola,
                'employee' => $dependente,
                'user' => 100, #TOdo
                'start_date' =>$data,
                'type' => $tipologia,
                'hours' => $ore,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    private function updateDb($id, $tipologia, $ore)
    {
        DB::connection('mysql_old')->table('employees_attendances')
            ->where('id',$id)
            ->update(array(
                'user' => 100, #TOdo
                'type' => $tipologia,
                'hours' => $ore,
                'updated_at' => date('Y-m-d H:i:s'),
            ));
    }
}
