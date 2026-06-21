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
use Illuminate\Support\Facades\Http;
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
    protected $description = 'Recupero le richieste effettuate ogni 5 minuti.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('HR Richieste: Inizio download nuove richieste.');
		ini_set('max_execution_time', -1);
        stream_context_set_default(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
        
        $token = "exWm8aP5MjxLUj2b28$2Fd";
        $url = 'https://app.metallurgicabresciana.it/turni/mb/richieste/api/get.php';

        try {
            $response = Http::timeout(30)->get($url, ['tk' => $token]);

            if ($response->failed()) {
                Log::error('HR Richieste: Errore connessione API. Status: ' . $response->status());
                return Command::FAILURE;
            }

            $result = $response->object();

            if (!empty($result->stato) && $result->stato == 200 && !empty($result->list)) {
                foreach ($result->list as $item) {
                    
                    // Sicurezza: Verifico che i dati minimi della richiesta esistano nell'oggetto dell'API
                    if (empty($item->richiesta->matricola) || empty($item->richiesta->richiesta_id)) {
                        Log::warning('HR Richieste: Trovato record API con dati richiesta incompleti. Salto.');
                        continue;
                    }

                    $dependente = $this->dipendente($item->richiesta->matricola);
                    $check = HrHoursRequested::where('bacheca_id', $item->richiesta->richiesta_id)
                        ->where('tipologia', $item->richiesta->tipologia)
                        ->first();

                    $giorni = [];

                    // Se la richiesta NON esiste localmente ed il dipendente è valido, la creo
                    if (empty($check->id) && !empty($dependente->id)) {
                        $richiesta = new HrHoursRequested();
                        $richiesta->bacheca_id = $item->richiesta->richiesta_id;
                        $richiesta->data_richiesta = $item->richiesta->data_richiesta;
                        $richiesta->bacheca_dipendente_id = $item->richiesta->dipendente;
                        $richiesta->dipendente_matricola = $item->richiesta->matricola;
                        $richiesta->dipendente_cognome = $item->richiesta->cognome;
                        $richiesta->dipendente_nome = $item->richiesta->nome;
                        $richiesta->tipologia = $item->richiesta->tipologia;
                        $richiesta->centro_di_costo = $dependente->centro;
                        $richiesta->motivazione = $item->richiesta->motivazione;
                        $richiesta->save();

						$giorniStringa = [];
                        // Ciclo sui giorni (Risolto bug di sovrascrittura di $dettaglio)
                        if (!empty($item->giorni)) {
                            foreach ($item->giorni as $giorno) {
                                $giorni[strtotime($giorno->data)] = $giorno->data;
                                
                                $dettaglioModel = new HrHoursRequestedDetail();
                                $dettaglioModel->richiesta_id = $richiesta->id;
                                $dettaglioModel->bacheca_id = $richiesta->bacheca_id;
                                $dettaglioModel->bacheca_dipendente_id = $richiesta->bacheca_dipendente_id;
                                $dettaglioModel->dipendente_matricola = $richiesta->dipendente_matricola;
                                $dettaglioModel->data = $giorno->data;
                                
                                if (!empty($giorno->ore_richieste)) {
                                    $hours = floor($giorno->ore_richieste / 60);
                                    $min = $giorno->ore_richieste - ($hours * 60);
                                    $dettaglioModel->ore_richieste = $hours . ':' . $min;
                                    $dettaglioModel->ora_inizio = $giorno->ora_inizio;
                                    $dettaglioModel->ora_fine = date('H:i', strtotime($giorno->data . ' ' . $giorno->ora_inizio . " +" . $giorno->ore_richieste . " Minutes"));
                                }
                                $dettaglioModel->tipologia = $giorno->tipologia;
                                $dettaglioModel->save();
								$giorniStringa[] = (object) ['data' => (string) $dettaglioModel->data, 'tipologia' => (int) $dettaglioModel->tipologia, 'ora_inizio' => (string) $dettaglioModel->ora_inizio, 'ora_fine' => (string) $dettaglioModel->ora_fine ];
                            }
                        }

                        // Recupero e inizializzazione approvatori primo livello
                        $approvatori = $this->approvatori($dependente->centro, true, $richiesta->id);

                        if (!empty($approvatori['email']) && count($approvatori['email'])) {
                            $info['tipologia'] = $this->getTipologiaTesto($richiesta->tipologia);
                            $info['dipendente'] = $richiesta->dipendente_cognome . ' ' . $richiesta->dipendente_nome;
                            $info['matricola'] = $richiesta->dipendente_matricola;
                            
                            $id = $richiesta->id;
                            $subject = 'Nuova Richiesta Da Approvare ' . strtotime(date('Y-m-d H:i:s'));
                            ksort($giorni);

                            $tokenEmail = Str::random(5) . uniqid();
                            $this->setApprovazioneEmail($richiesta->bacheca_id, $tokenEmail, implode('-', $approvatori['id']));
							
							$giorniObjs = HrHoursRequestedDetail::where('richiesta_id', $richiesta->id)->orderBy('data')->get();
					
                            
                            foreach ($approvatori['email'] as $emailData) {
                                if ($emailData['notifica'] == true) {
                                    $tokenEmailTmp = $tokenEmail . '-' . $richiesta->bacheca_id . '-' . $emailData['user_id'];
                                    $this->email($id, 'emails/email_richiesta_giorni_dipendente', $subject, $info, $emailData['email'], $approvatori['approvatori'], $giorniStringa, $tokenEmailTmp);
                                }
                            }
                        }

                        // Se la tipologia è mappata, diamo il feedback positivo all'API esterna
                        $this->setRichiestaRicevuta($richiesta->bacheca_id, $token);
                    } 
                    else {
                        // Se la richiesta esiste già localmente, diciamo all'API di non rimandarcela
                        if (!empty($check->id)) {
                            $this->setRichiestaRicevuta($item->richiesta->richiesta_id, $token);
                        }

                        if (empty($dependente->id)) {
                            Log::warning("HR Richieste: Dipendente con matricola {$item->richiesta->matricola} non trovato nel DB legacy.");
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('HR Richieste: Eccezione nel comando: ' . $e->getMessage());
            return Command::FAILURE;
        }

        Log::info('HR Richieste: Fine elaborazione.');
        return Command::SUCCESS;
    }

    private function getTipologiaTesto($tipologiaId): string
    {
        switch ($tipologiaId) {
            case 1: return 'Ferie';
            case 2: return '104';
            case 5: return 'Permesso';
            case 101: return 'Annulamento Ferie';
            case 102: return 'Annulamento 104';
            case 105: return 'Annulamento Permesso';
            default: return 'Richiesta';
        }
    }

    private function setApprovazioneEmail($id, $token, $approvatori)
    {
        $url = 'https://app.metallurgicabresciana.it/turni/mb/richieste/api/set_approvazione.php';
        try {
            Http::timeout(10)->get($url, [
                'tk' => $token,
                'id' => $id,
                'approvatori' => $approvatori
            ]);
        } catch (\Exception $e) {
            Log::error("HR Richieste: Errore invio approvazione email per bacheca_id {$id}: " . $e->getMessage());
        }
    }

    private function dipendente($matricola)
    {
        return DB::connection('mysql_old')->table('employees')
            ->select('id', 'centro')
            ->where('matricola', $matricola)
            ->first();
    }

    private function approvatori($centro, $set = false, $richiesta_id = null)
    {
        $userNotifiche = HrApproverRequest::select('users.email', 'users.full_name', 'users.id', 'hr_approver_requests.livello', 'hr_approver_requests.notifica')
            ->join('users', 'users.id', '=', 'hr_approver_requests.user_id')
            ->where('centro_ci_costo', $centro)
            ->where('disattivo', '=', 'false')
            ->orderBy('livello', 'asc')
            ->get();

        $livello = null;
        $result = ['approvatori' => [], 'email' => [], 'id' => []];

        foreach ($userNotifiche as $user) {
            if (is_null($livello) || $livello == $user->livello) {
                $livello = $user->livello;

                $approval = new HrRequestPending();
                $approval->richiesta_id = $richiesta_id;
                $approval->user_id = $user->id;
                $approval->approvatore = $user->full_name;
                $approval->livello = $user->livello;
                $approval->save();

                $result['approvatori'][$user->full_name] = $user->full_name;
                $result['email'][] = [
                    'user_id'  => $user->id,
                    'email'    => $user->email,
                    'notifica' => $user->notifica,
                ];
                $result['id'][] = $user->id;
            }
        }

        return $result;
    }

    private function setRichiestaRicevuta($id, $token)
    {
        $url = 'https://app.metallurgicabresciana.it/turni/mb/richieste/api/inviato.php';
        try {
            Http::timeout(10)->get($url, [
                'tk' => $token,
                'id' => $id
            ]);
        } catch (\Exception $e) {
            Log::error("HR Richieste: Errore in setRichiestaRicevuta per bacheca_id {$id}: " . $e->getMessage());
        }
    }

    private function email($id, $template, $oggetto, $info, $email, $approvatori, $giorni, $token = null)
    {
        try {
            Mail::send($template, compact('id', 'approvatori', 'giorni', 'info', 'token'), function ($message) use ($email, $oggetto) {
                $message->to($email)->subject($oggetto);
            });
        } catch (\Exception $e) {
            Log::error("HR Richieste: Impossibile inviare email a {$email}: " . $e->getMessage());
        }
    }
}