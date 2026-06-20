<?php

namespace App\Console\Commands;

use App\Jobs\RichiesteGiorniDipendenti;
use App\Models\HrHoursRequested;
use App\Models\HrRequestPending;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    protected $description = 'Recupero le approvazione provenieti dalle email ogni 5 minuti.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Entro2: ');
        ini_set('max_execution_time', -1);
        stream_context_set_default(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
        $token = "exWm8aP5MjxLUj2b28$2Fd";
        $path = 'https://app.metallurgicabresciana.it/turni/mb/richieste/api/get_approvazioni.php?';
        $path .= 'tk=' . $token;
        $getMovieList = file_get_contents($path);
        $result = json_decode($getMovieList);
        if (!empty($result->stato) && $result->stato == 200) {
            foreach ($result->list as $approvazione) {
                Log::info('User_id : '.$approvazione->user);
                Log::info('Richiesta_id : '.$approvazione->richiesta);
                // Recupero l'approvatore
                $user = User::find($approvazione->user);
                // Recupero la richiesta
                //$richiesta = HrHoursRequested::where('bacheca_id',$approvazione->richiesta)->whereNull('stato')->first();
                $richiesta = HrHoursRequested::where('bacheca_id',$approvazione->richiesta)->first();

                Log::info('Utente : '.$user->id);
                Log::info('Ruchiesta : '.$richiesta->id);
                $pending = HrRequestPending::where('richiesta_id',$richiesta->id)->where('user_id',$user->id)->first();
                $pending->stato = ($approvazione->esito == 2 ? false:true);
                $pending->approvatore = $user->full_name;
                if(!empty($approvazione->nota))
                    $pending->nota = $approvazione->nota;
                if($pending->save()){
                    DB::table("hr_request_pendings")
                        ->where('richiesta_id',$richiesta->id)
                        ->whereNull('stato')
                        ->update(['approvatore' => $user->full_name, 'stato' => ($approvazione->esito == 2 ? false:true)]);
                    dispatch(new RichiesteGiorniDipendenti($richiesta->id, $approvazione->user));
                    $this->setRichiestaRicevuta($approvazione->richiesta, $approvazione->token);
                }
            }
        }
    }

    private function setRichiestaRicevuta($id, $token)
    {
        $path = 'https://app.metallurgicabresciana.it/turni/mb/richieste/api/set_approvazione.php?';
        $path .= 'tk=' . $token;
        $path .= '&id='. $id;
        $path .= '&inviata=1';
        $getMovieList = file_get_contents($path);
    }
}
