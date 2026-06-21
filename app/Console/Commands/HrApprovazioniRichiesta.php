<?php

namespace App\Console\Commands;

use App\Jobs\RichiesteGiorniDipendenti;
use App\Models\HrHoursRequested;
use App\Models\HrRequestPending;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class HrApprovazioniRichiesta extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:hr-approvazioni-richieste';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recupero le approvazioni provenienti dalle email ogni 5 minutes.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('HR Approvazioni: Inizio lettura API esterna.');

		ini_set('max_execution_time', -1);
        stream_context_set_default(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
        $token = "exWm8aP5MjxLUj2b28$2Fd";
        $url = 'https://app.metallurgicabresciana.it/turni/mb/richieste/api/get_approvazioni.php';

        try {
            // Eseguiamo la chiamata in modo sicuro con un timeout di 30 secondi
            $response = Http::timeout(30)->get($url, ['tk' => $token]);

            if ($response->failed()) {
                Log::error('HR Approvazioni: Errore di connessione all\'API esterna. Status: ' . $response->status());
                return Command::FAILURE;
            }

            $result = $response->object();

            if (!empty($result->stato) && $result->stato == 200 && !empty($result->list)) {
                foreach ($result->list as $approvazione) {
                    
                    // 1. Controllo esistenza Utente (Evita crash alla riga successiva)
                    $user = User::find($approvazione->user);
                    if (!$user) {
                        Log::error("HR Approvazioni: Utente ID {$approvazione->user} non trovato nel database locale. Salto record.");
                        continue;
                    }

                    // 2. Controllo esistenza Richiesta ancora pendente (stato NULL)
                    $richiesta = HrHoursRequested::where('bacheca_id', $approvazione->richiesta)
                        ->whereNull('stato')
                        ->first();

                    if (!$richiesta) {
                        // Questo evita il crash se l'API rimanda una richiesta già chiusa
                        Log::warning("HR Approvazioni: Richiesta bacheca_id {$approvazione->richiesta} non trovata o già elaborata (stato non NULL). Salto record.");
						
						$this->setRichiestaRicevuta($approvazione->richiesta, $approvazione->token);
						
                        continue;
                    }

                    Log::info("HR Approvazioni - Utente valido: {$user->id} | Richiesta valida: {$richiesta->id}");

                    // 3. Controllo esistenza Record Pending
                    $pending = HrRequestPending::where('richiesta_id', $richiesta->id)
                        ->where('user_id', $user->id)
                        ->first();

                    if (!$pending) {
                        Log::error("HR Approvazioni: Record pending non trovato per richiesta_id {$richiesta->id} e user_id {$user->id}. Salto record.");
                        continue;
                    }

                    // Determino l'esito (se esito è 2 è rifiutata, altrimenti approvata)
                    $esitoStato = ($approvazione->esito != 2);

                    $pending->stato = $esitoStato;
                    $pending->approvatore = $user->full_name;
                    
                    if (!empty($approvazione->nota)) {
                        $pending->nota = $approvazione->nota;
                    }

                    if ($pending->save()) {
                        // Aggiorna massivamente i record correlati ancora senza stato impostato
                        DB::table("hr_request_pendings")
                            ->where('richiesta_id', $richiesta->id)
                            ->whereNull('stato')
                            ->update([
                                'approvatore' => $user->full_name, 
                                'stato' => $esitoStato
                            ]);

                        // Avvio del Job in coda
                        dispatch(new RichiesteGiorniDipendenti($richiesta->id, $approvazione->user));
                        
                        // Notifica di feedback all'API esterna
                        $this->setRichiestaRicevuta($approvazione->richiesta, $approvazione->token);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('HR Approvazioni: Eccezione nel comando Artisan: ' . $e->getMessage());
            return Command::FAILURE;
        }

        Log::info('HR Approvazioni: Fine elaborazione.');
        return Command::SUCCESS;
    }
    
    private function setRichiestaRicevuta($id, $token)
    {
        $url = 'https://app.metallurgicabresciana.it/turni/mb/richieste/api/set_approvazione.php';
        
        try {
            Http::timeout(10)->get($url, [
                'tk' => $token,
                'id' => $id,
                'inviata' => 1
            ]);
        } catch (\Exception $e) {
            Log::error("HR Approvazioni: Impossibile impostare richiesta ricevuta per ID {$id}: " . $e->getMessage());
        }
    }
}