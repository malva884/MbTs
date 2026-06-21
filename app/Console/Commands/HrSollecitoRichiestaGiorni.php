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
        
        Log::info('=== INIZIO COMANDO: app:hr-sollecito-richiesta-giorni ===');

        $richieste = HrHoursRequested::whereNull('stato')->get();
        
        Log::info("Trovate " . $richieste->count() . " richieste da elaborare.");

        foreach ($richieste as $richiesta) {
            Log::info("Elaborazione Richiesta ID: {$richiesta->id} (Bacheca ID: {$richiesta->bacheca_id})");

            $tipologia = '';
            $giorni = HrHoursRequestedDetail::where('richiesta_id', $richiesta->id)->orderBy('data')->get();

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
                    $tipologia = 'Ferie Revocate'; 
                    break;
                case 102:
                    $tipologia = '104 Revocate';
                    break;
                default:
                    $tipologia = 'Sconosciuta (' . $richiesta->tipologia . ')';
                    Log::warning("Tipologia richiesta non riconosciuta per richiesta ID: {$richiesta->id}");
            }

            $info['dipendente'] = $richiesta->dipendente_cognome . ' ' . $richiesta->dipendente_nome;
            $info['matricola'] = $richiesta->dipendente_matricola;
            $info['tipologia'] = $tipologia;
            
            $d = [];
			$giorniStringa = [];
            foreach ($giorni as $giorno) {
                $d[] = $giorno->data;
				$giorniStringa[] = (object) ['data' => (string) $giorno->data, 'tipologia' => (int) $giorno->tipologia, 'ora_inizio' => (string) $giorno->ora_inizio, 'ora_fine' => (string) $giorno->ora_fine ];

            }

            // Chiamata API Esterna con gestione errore
            stream_context_set_default(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
            $path = 'https://app.metallurgicabresciana.it/turni/mb/richieste/api/get_approvazione.php?richiesta=' . $richiesta->bacheca_id;
            
            $tokenEmail = '';
            try {
                // Usiamo @ per evitare che PHP spari un Warning a schermo se l'host è irraggiungibile
                $getMovieList = @file_get_contents($path);
                
                if ($getMovieList === false) {
                    throw new \Exception("Impossibile contattare l'API esterna o endpoint non trovato.");
                }

                $result = json_decode($getMovieList);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Errore nella decodifica JSON dell'API.");
                }

                if (isset($result->stato) && $result->stato == 200) {
                    $tokenEmail = $result->token;
                } else {
                    Log::warning("L'API ha risposto ma lo stato non è 200 per richiesta ID: {$richiesta->id}. Risposta: " . json_encode($result));
                }

            } catch (\Exception $e) {
                Log::error("Errore durante la chiamata API per richiesta ID: {$richiesta->id}. Errore: " . $e->getMessage());
                // Saltiamo questa richiesta ed evitiamo il crash del comando completo
                continue; 
            }

            $approvatori = $this->approvatori($richiesta->id);

            // Evitiamo errori se l'array degli utenti è vuoto o non esiste
            if (empty($approvatori['users'])) {
                Log::warning("Nessun approvatore trovato o in stato pendente per la richiesta ID: {$richiesta->id}");
                continue;
            }

            $subject = 'Notifica Richiesta In Approvazione ' . strtotime(date('Y-m-d H:i:s'));
            
            // Invio notifiche
            foreach ($approvatori['users'] as $user) {
                $tokenEmailTmp = $tokenEmail . '-' . $richiesta->bacheca_id . '-' . $user['user_id'];
                
                try {
                    $this->email($richiesta->id, 'emails/email_richiesta_giorni_dipendente', $subject, $info, $user['email'], $approvatori['approvatori'], $giorniStringa, $tokenEmailTmp);
                    Log::info("Email di sollecito inviata con successo a: {$user['email']} per richiesta ID: {$richiesta->id}");
                } catch (\Exception $e) {
                    Log::error("Fallito invio email a: {$user['email']} per richiesta ID: {$richiesta->id}. Errore: " . $e->getMessage());
                }
            }
        }

        Log::info('=== FINE COMANDO: app:hr-sollecito-richiesta-giorni ===');
    }

    private function approvatori($richiestaId)
    {
        // Inizializzazione struttura per evitare indici indefiniti
        $result = [
            'approvatori' => [],
            'approvatori_id' => [], // Corretto il bug del '$' nella stringa
            'users' => []
        ];

        try {
            $approvatori = HrRequestPending::select('users.full_name', 'users.id', 'users.email')
                ->join('users', 'users.id', '=', 'hr_request_pendings.user_id')
                ->where('richiesta_id', $richiestaId)
                ->whereNull('hr_request_pendings.stato')
                ->get();

            foreach ($approvatori as $approvatore) {
                $result['approvatori'][] = $approvatore->full_name;
                $result['approvatori_id'][] = $approvatore->id; 
                $result['users'][] = [
                    'user_id' => $approvatore->id,
                    'email'   => $approvatore->email,
                ];
            }
        } catch (\Exception $e) {
            Log::error("Errore nella query degli approvatori per richiesta ID {$richiestaId}: " . $e->getMessage());
        }

        return $result;
    }

    private function email($id, $template, $oggetto, $info, $email, $approvatori, $giorni, $token = null)
    {
        Mail::send($template, compact('id', 'approvatori', 'giorni', 'info', 'token'), function ($message) use ($email, $oggetto) {
            $message
                ->to($email)
                ->subject($oggetto);
        });
    }
}