<?php

namespace App\Console\Commands;

use App\Models\HrApproverRequest;
use App\Models\HrHoursRequested;
use App\Models\HrHoursRequestedDetail;
use App\Models\HrRequestPending;
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

        // Recupera le richieste dal database mysql_dipendenti (solo pending e processing)
        // Includendo anche gli annullamenti (cancellation_status = pending)
        $richieste = DB::connection('mysql_dipendenti')
            ->table('leaves')
            ->where(function ($query) {
                $query->whereIn('status', ['pending', 'processing'])
                    ->orWhere('cancellation_status', 'pending');
            })
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($richieste as $richiestaDipendenti) {
            // Verifica se è una richiesta di annullamento
            $isCancellation = !empty($richiestaDipendenti->cancellation_status) && $richiestaDipendenti->cancellation_status == 'pending';

            // Aggiorna lo stato appropriato per evitare duplicati
            if ($isCancellation) {
                DB::connection('mysql_dipendenti')
                    ->table('leaves')
                    ->where('id', $richiestaDipendenti->id)
                    ->update(['cancellation_status' => 'processing']);
            } else {
                DB::connection('mysql_dipendenti')
                    ->table('leaves')
                    ->where('id', $richiestaDipendenti->id)
                    ->update(['status' => 'processing']);
            }

            $dependente = $this->dipendente($richiestaDipendenti->employee_id);

            // Mappa la tipologia corretta (annullamento o normale)
            $tipologia = $isCancellation
                ? $this->mapTipologiaAnnullamento($richiestaDipendenti->leave_type_id)
                : $this->mapTipologia($richiestaDipendenti->leave_type_id);

            $check = HrHoursRequested::where('bacheca_id', $richiestaDipendenti->id)
                ->where('tipologia', $tipologia)
                ->first();

            $giorni = [];
            if (empty($check->id) && !empty($dependente->id)) {
                $richiesta = new HrHoursRequested();
                $richiesta->bacheca_id = $richiestaDipendenti->id;
                $richiesta->data_richiesta = $richiestaDipendenti->created_at;
                $richiesta->bacheca_dipendente_id = $richiestaDipendenti->employee_id;
                $richiesta->dipendente_matricola = $richiestaDipendenti->employee_id;
                $richiesta->dipendente_cognome = $dependente->cognome ?? $richiestaDipendenti->employee_name;
                $richiesta->dipendente_nome = $dependente->nome ?? '';
                $richiesta->tipologia = $tipologia;
                $richiesta->centro_di_costo = $dependente->centro;
                $richiesta->motivazione = $richiestaDipendenti->motivation;
                $richiesta->save();

                // Genera i giorni dal range from_date a to_date (escludendo sabato e domenica)
                $startDate = new \DateTime($richiestaDipendenti->from_date);
                $endDate = new \DateTime($richiestaDipendenti->to_date);
                $interval = new \DateInterval('P1D');
                $dateRange = new \DatePeriod($startDate, $interval, $endDate->modify('+1 day'));

                foreach ($dateRange as $date) {
                    // Salta sabato (6) e domenica (0)
                    if ($date->format('N') == 6 || $date->format('N') == 7) {
                        continue;
                    }
                    $giorni[strtotime($date->format('Y-m-d'))] = $date->format('Y-m-d');
                    $dettaglio = new HrHoursRequestedDetail();
                    $dettaglio->richiesta_id = $richiesta->id;
                    $dettaglio->bacheca_id = $richiesta->bacheca_id;
                    $dettaglio->bacheca_dipendente_id = $richiesta->bacheca_dipendente_id;
                    $dettaglio->dipendente_matricola = $richiesta->dipendente_matricola;
                    $dettaglio->data = $date->format('Y-m-d');

                    if (!empty($richiestaDipendenti->hours_required)) {
                        $hours = floor($richiestaDipendenti->hours_required / 60);
                        $min = $richiestaDipendenti->hours_required - ($hours * 60);
                        $dettaglio->ore_richieste = $hours . ':' . $min;

                        if (!empty($richiestaDipendenti->start_time)) {
                            $dettaglio->ora_inizio = date('H:i', mktime(0, $richiestaDipendenti->start_time));
                            $dettaglio->ora_fine = date('H:i', mktime(0, $richiestaDipendenti->start_time + $richiestaDipendenti->hours_required));
                        }
                    }

                    $dettaglio->tipologia = $richiesta->tipologia;
                    $dettaglio->save();
                }

                $approvatori = $this->approvatori($dependente->centro, true, $richiesta->id);

                // notifica di approvazione
                if (count($approvatori['email'])) {
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
                    $info['dipendente'] = $richiesta->dipendente_cognome . ' ' . $richiesta->dipendente_nome;
                    $info['matricola'] = $richiesta->dipendente_matricola;
                    $id = $richiesta->id;
                    $subject = 'Nuova Richiesta Da Approvare ' . strtotime(date('Y-m-d H:i:s'));
                    ksort($giorni);

                    $tokenEmail = Str::random(5) . uniqid();

                    foreach ($approvatori['email'] as $email) {
                        if ($email['notifica'] == true) {
                            $tokenEmailTmp = $tokenEmail . '-' . $richiesta->bacheca_id . '-' . $email['user_id'];
                            $this->email($id, 'emails/email_richiesta_giorni_dipendente', $subject, $info, $email['email'], $approvatori['approvatori'], $giorni, $tokenEmailTmp);
                        }
                    }
                }

                if (!empty($info['tipologia'])) {
                    // Aggiorna lo stato appropriato nel database dipendenti
                    if ($isCancellation) {
                        DB::connection('mysql_dipendenti')
                            ->table('leaves')
                            ->where('id', $richiesta->bacheca_id)
                            ->update(['cancellation_status' => 'processed']);
                    } else {
                        DB::connection('mysql_dipendenti')
                            ->table('leaves')
                            ->where('id', $richiesta->bacheca_id)
                            ->update(['status' => 'processed']);
                    }
                } else {
                    Log::info('Tipologia Non trovata: ' . $richiesta->tipologia);
                }
            } else {
                if (!empty($check->id)) {
                    // Aggiorna lo stato appropriato nel database dipendenti
                    if ($isCancellation) {
                        DB::connection('mysql_dipendenti')
                            ->table('leaves')
                            ->where('id', $richiestaDipendenti->id)
                            ->update(['cancellation_status' => 'processed']);
                    } else {
                        DB::connection('mysql_dipendenti')
                            ->table('leaves')
                            ->where('id', $richiestaDipendenti->id)
                            ->update(['status' => 'processed']);
                    }
                }

                Log::info('Dipendente Non Trovato: ' . $richiestaDipendenti->employee_id);
                Log::info('Tipologia: ' . $richiestaDipendenti->leave_type_id);
                Log::info('Id Bacheca: ' . $richiestaDipendenti->id);
            }
        }
    }

    private function mapTipologia($leaveTypeId)
    {
        // Mappa i leave_type_id del portale dipendenti alle tipologie esistenti
        $mapping = [
            'ferie' => 1,
            '104' => 2,
            'permesso' => 5,
        ];

        return $mapping[$leaveTypeId] ?? 1; // Default a ferie se non trovato
    }

    private function mapTipologiaAnnullamento($leaveTypeId)
    {
        // Mappa i leave_type_id del portale dipendenti alle tipologie di annullamento
        $mapping = [
            'ferie' => 101,  // Annullamento Ferie
            '104' => 102,    // Annullamento 104
            'permesso' => 105, // Annullamento Permesso
        ];

        return $mapping[$leaveTypeId] ?? 101; // Default a annullamento ferie se non trovato
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
                    'notifica' => $user->notifica,
                ];
                $result['id'][] = $user->id;
                //$result['email']['email']= $user->email;
            }
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
