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
use Illuminate\Support\Facades\Http;
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
        // 1. RECUPERO L'ULTIMA APPROVAZIONE EFFETTUATA
        $pending = HrRequestPending::where('richiesta_id', $this->id)
            ->where('user_id', $this->user_id)
            ->orderBy('livello', 'desc')
            ->orderBy('updated_at', 'desc')
            ->first();

        if (!$pending) {
            Log::error("Job HR: Record pending non trovato per richiesta {$this->id} e utente {$this->user_id}. Interrompo.");
            return;
        }

        // 2. RECUPERO LA RICHIESTA PRINCIPALE DEL DIPENDENTE
        $richiesta = HrHoursRequested::find($pending->richiesta_id);
        if (!$richiesta) {
            Log::error("Job HR: Richiesta principale {$pending->richiesta_id} non trovata nel DB. Interrompo.");
            return;
        }

        $id = $richiesta->id;

        // 3. RECUPERO E PREPARAZIONE DATI (Posizionato in alto per log e email)
        $giorni = HrHoursRequestedDetail::where('richiesta_id', $richiesta->id)->orderBy('data')->get();
        $tipologia = $this->getTipologiaTesto($richiesta->tipologia);

        // Prepariamo le date sia come oggetti per il Blade sia come stringhe piatte per i log
        $giorniStringa = [];
        $soloDateTesto = []; 
        foreach ($giorni as $g) {
            $giorniStringa[] = (object) ['data' => (string) $g->data, 'tipologia' => (int) $g->tipologia, 'ora_inizio' => (string) $g->ora_inizio, 'ora_fine' => (string) $g->ora_fine ];
            $soloDateTesto[] = (string) $g->data;
        }
        
        // 🛑 CONTROLLO ANTI-DUPLICATO GLOBALE 
        if (($richiesta->stato === 0 || $richiesta->stato === 1) && $richiesta->updated_at->diffInMinutes(now()) > 5) {
            $nomeDipendente = $richiesta->dipendente_cognome . ' ' . $richiesta->dipendente_nome;
            $matricola = $richiesta->dipendente_matricola;
            $statoTesto = $richiesta->stato === 1 ? 'APPROVATA' : 'RIFIUTATA';
            $dateRichieste = implode(', ', $soloDateTesto);

            Log::warning(
                "Job HR - Richiesta già processata definitivamente: " .
                "[ID Richiesta: {$id}] " .
                "[Dipendente: {$nomeDipendente} (Matricola: {$matricola})] " .
                "[Stato Attuale: {$statoTesto}] " .
                "[Date: {$dateRichieste}] " .
                "[Ultimo Aggiornamento: {$richiesta->updated_at}]. " .
                "Esecuzione interrotta per evitare doppie email."
            );
            return;
        }

        // ---------------------------------------------------------------------
        // 🛡️ INIZIO DELLA TRANSAZIONE DI DATABASE
        // ---------------------------------------------------------------------
        DB::beginTransaction();

        try {
            // =========================================================================
            // --- RAMO A: LA RICHIESTA È STATA BOCCIATA ---
            // =========================================================================
            if ($pending->stato == 0) {
                Log::info("Job HR: Processo Rifiuto per richiesta {$id}.");
                
                $richiesta->stato = $pending->stato;
                $richiesta->note = $pending->note ?? $pending->nota;
                $richiesta->save();
                
                DB::table('hr_hours_requested_details')
                    ->where('richiesta_id', $richiesta->id)
                    ->update(['confermato' => false]);

                $info['dipendente'] = $richiesta->dipendente_cognome . ' ' . $richiesta->dipendente_nome;
                $info['matricola']  = $richiesta->dipendente_matricola;
                $info['tipologia']  = $tipologia;

                $subject = 'Richiesta Negata ' . strtotime($richiesta->updated_at);
                
                $users = Utility::users_notify(['hr_richieste_negate']);
                if ($users instanceof \Illuminate\Support\Collection) {
                    $users = $users->pluck('email')->toArray();
                } elseif (is_array($users) && isset($users[0]) && is_object($users[0])) {
                    $users = array_column($users, 'email');
                }
                
                $this->email($id, 'emails/email_richiesta_giorni_nagata', $subject, $info, $users, [], $giorniStringa);
                $this->notificaStatoEsterno($richiesta->bacheca_id, $pending->stato, $pending->nota);
            } 
            
            // =========================================================================
            // --- RAMO B: LA RICHIESTA È STATA APPROVATA (Controllo Multi-livello) ---
            // =========================================================================
            else {
                $usr = $this->user_id;
                
                // Cerca se ci sono altri approvatori di livello superiore
                $usersNotifica = HrApproverRequest::select(
                    'users.email', 
                    'users.full_name', 
                    'users.id', 
                    'hr_approver_requests.livello', 
                    'hr_approver_requests.notifica'
                )
                ->join('users', 'users.id', '=', 'hr_approver_requests.user_id')
                ->where('centro_ci_costo', $richiesta->centro_di_costo)
                ->where(function ($query) use ($richiesta, $pending, $usr) {
                    $livello = DB::table('hr_approver_requests')
                        ->select('livello')
                        ->where('centro_ci_costo', $richiesta->centro_di_costo)
                        ->where('notifica', 1)
                        ->where('livello', '>', (int)$pending->livello)
                        ->where('user_id', '<>', $usr)
                        ->orderBy('livello', 'asc')
                        ->first();
                        
                    $query->where('livello', (!empty($livello->livello) ? $livello->livello : 100));
                })
                ->where('disattivo', '=', 'false')
                ->get();

                Log::info("Job HR: Richiesta: {$this->id} | Approvatore: {$this->user_id} | Livello Corrente: {$pending->livello}");  
                
                // ---------------------------------------------------------------------
                // SOTTO-RAMO B1: C'è un altro approvatore successivo nella catena
                // ---------------------------------------------------------------------
                if ($usersNotifica->count()) {
                    
                    // 🛑 CONTROLLO ANTI-DUPLICATO LIVELLO SUCCESSIVO
                    $prossimoLivello = $usersNotifica->first()->livello;
                    $giaEsistente = HrRequestPending::where('richiesta_id', $richiesta->id)
                        ->where('livello', $prossimoLivello)
                        ->exists();

                    if ($giaEsistente) {
                        Log::warning("Job HR: Approvazione livello {$prossimoLivello} già presente. Salto inserimento e reinvio mail.");
                    } else {
                        $approvatori = [];
                        $users = [];
                        $approvatori_id = [];
                        
                        foreach ($usersNotifica as $user) {
                            $approval = new HrRequestPending();
                            $approval->richiesta_id = $richiesta->id;
                            $approval->user_id      = $user->id;
                            $approval->approvatore  = $user->full_name;
                            $approval->livello      = $user->livello;
                            $approval->save();

                            $approvatori[]    = $user->full_name;
                            $approvatori_id[] = $user->id;
                            $users[] = [
                                'user_id'  => $user->id,
                                'email'    => $user->email,
                                'notifica' => $user->notifica,
                            ];
                        }

                        $info['tipologia']  = $tipologia;
                        $info['dipendente'] = $richiesta->dipendente_cognome . ' ' . $richiesta->dipendente_nome;
                        $info['matricola']  = $richiesta->dipendente_matricola;

                        $tokenEmail = Str::random(5) . uniqid();
                        $this->setApprovazioneEmail($richiesta->bacheca_id, $tokenEmail, implode('-', $approvatori_id));

                        $subject = 'Nuova Richiesta Da Approvare ' . strtotime(date('Y-m-d H:i:s'));
                        foreach ($users as $user) {
                            if ($user['notifica'] == true) {
                                $tokenEmailTmp = $tokenEmail . '-' . $richiesta->bacheca_id . '-' . $user['user_id'];
                                $this->email($id, 'emails/email_richiesta_giorni_dipendente', $subject, $info, (string)$user['email'], $approvatori, $giorniStringa, $tokenEmailTmp);
                            }
                        }
                    }
                } 
                
                // ---------------------------------------------------------------------
                // SOTTO-RAMO B2: Era l'ultimo approvatore (Approvazione Definitiva)
                // ---------------------------------------------------------------------
                else {
                    $richiesta->stato = $pending->stato;
                    $richiesta->note  = $pending->nota;
                    $richiesta->save();
                    
                    DB::table('hr_hours_requested_details')
                        ->where('richiesta_id', $richiesta->id)
                        ->update(['confermato' => true]);
                    
                    $this->notificaStatoEsterno($richiesta->bacheca_id, $pending->stato, $pending->nota);

                    $matricola = $richiesta->dipendente_matricola;
                    
                    switch ($richiesta->tipologia) {
                        case 1: $this->setPresenze($matricola, $giorni, 1, 8); break;
                        case 2: $this->setPresenze($matricola, $giorni, 4, 8); break;
                        case 5: $this->setPresenze($matricola, $giorni, 5, 1); break;
                        case 101:
                        case 102:
                        case 105:
                            $this->setPresenze($matricola, $giorni, 0, 0);
                            break;
                    }

                    $subject = 'Richiesta Approvata ' . strtotime($richiesta->updated_at);
                    
                    $users = Utility::users_notify(['hr_richieste_approvate']);
                    if ($users instanceof \Illuminate\Support\Collection) {
                        $users = $users->pluck('email')->toArray();
                    } elseif (is_array($users) && isset($users[0]) && is_object($users[0])) {
                        $users = array_column($users, 'email');
                    }
                    
                    $info['dipendente'] = $richiesta->dipendente_cognome . ' ' . $richiesta->dipendente_nome;
                    $info['matricola']  = $matricola;
                    $info['tipologia']  = $tipologia;
					
                    
                    $this->email($id, 'emails/email_richiesta_giorni_approvata', $subject, $info, $users, [], $giorniStringa);
                }
            }

            // Se tutto è andato a buon fine, applica i cambiamenti definitivamente al DB
            DB::commit();

        } catch (\Throwable $e) {
            // 🛑 SE QUALCOSA FALLISCE, RESETTA TUTTE LE OPERAZIONI DI QUESTO TENTATIVO
            DB::rollBack();
            Log::error("Job HR interrotto e resettato sul DB. Errore riscontrato: " . $e->getMessage());
            
            // Rilanciamo l'eccezione in modo che Laravel mantenga il Job nei "failed" per il retry manuale
            throw $e; 
        }
    }

    /**
     * Converte l'ID tipologia nel testo descrittivo.
     */
    private function getTipologiaTesto($tipologiaId): string 
    { 
        switch ($tipologiaId) { 
            case 1: return 'Ferie'; 
            case 2: return '104'; 
            case 5: return 'Permesso'; 
            case 101: return 'Ferie Revocate'; 
            case 102: return '104 Revocate'; 
            case 105: return 'Permesso Revocato'; 
            default: return 'Richiesta'; 
        } 
    }

    /**
     * Sincronizza lo stato finale con l'API esterna di bacheca.
     */
    private function notificaStatoEsterno($bachecaId, $stato, $nota) 
    { 
        $token = "exWm8aP5MjxLUj2b28$2Fd"; 
        $url = 'https://app.metallurgicabresciana.it/turni/mb/richieste/api/set.php'; 
        
        try { 
            Http::timeout(15)->get($url, [
                'tk'    => $token, 
                'id'    => $bachecaId, 
                'stato' => $stato, 
                'nota'  => $nota
            ]); 
        } catch (\Exception $e) { 
            Log::error("Job HR: Errore API esterni set.php: " . $e->getMessage()); 
        } 
    }

    /**
     * Registra i token per le risposte veloci via email.
     */
    private function setApprovazioneEmail($id, $token, $approvatori) 
    { 
        $url = 'https://app.metallurgicabresciana.it/turni/mb/richieste/api/set_approvazione.php'; 
        
        try { 
            Http::timeout(15)->get($url, [
                'tk'          => $token, 
                'id'          => $id, 
                'approvatori' => $approvatori
            ]); 
        } catch (\Exception $e) { 
            Log::error("Job HR: Errore API esterni set_approvazione.php: " . $e->getMessage()); 
        } 
    }

    /**
     * Gestisce l'invio fisico della mail tramite il Mail Facade.
     */
    private function email($id, $template, $oggetto, $info, $email, $approvatori, $giorni, $token = null) 
    { 
		Log::info("Job HR: Email Info"); 
		 
        if (empty($email)) {
            return; 
        }
         Log::info("Job HR: Email Procedo"); 
		Log::info($info);  
		Log::info($giorni);  
		Log::info($approvatori);  
		Log::info($template); 


        // Rimosso try-catch interno per delegarlo alla transazione del metodo principale handle()
        Mail::send($template, compact('id', 'approvatori', 'giorni', 'info', 'token'), function ($message) use ($email, $oggetto) { 
            $message->to($email)->subject($oggetto); 
        }); 
    }

    /**
     * Prepara e distribuisce le presenze sulle date richieste nel DB legacy.
     */
    private function setPresenze($matricola, $date, $tipologia, $ore) 
    { 
        foreach ($date as $data) { 
            $obj = DB::connection('mysql_old')
                ->table('employees_attendances')
                ->where('matricola', $matricola)
                ->where('start_date', $data->data)
                ->first(); 

            if (!empty($obj->id)) { 
                $this->updateDb($obj->id, $tipologia, $ore); 
            } else { 
                $dipendente = DB::connection('mysql_old')
                    ->table('employees')
                    ->select('id')
                    ->where('matricola', $matricola)
                    ->first(); 

                if ($dipendente) { 
                    $this->insertDb($dipendente->id, $matricola, $data->data, $tipologia, $ore); 
                } 
            } 
        } 
    }

    /**
     * Inserisce un nuovo record presenze nel vecchio DB.
     */
    private function insertDb($dipendente, $matricola, $data, $tipologia, $ore) 
    { 
        DB::connection('mysql_old')->table('employees_attendances')->insert([
            'matricola'  => $matricola, 
            'employee'   => $dipendente, 
            'user'       => 100, 
            'start_date' => $data, 
            'type'       => $tipologia, 
            'hours'      => $ore, 
            'created_at' => now(), 
            'updated_at' => now()
        ]); 
    }

    /**
     * Aggiorna un record presenze esistente nel vecchio DB.
     */
    private function updateDb($id, $tipologia, $ore) 
    { 
        DB::connection('mysql_old')->table('employees_attendances')
            ->where('id', $id)
            ->update([
                'user'       => 100, 
                'type'       => $tipologia, 
                'hours'      => $ore, 
                'updated_at' => now()
            ]); 
    }
}