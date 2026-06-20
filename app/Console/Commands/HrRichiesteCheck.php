<?php

namespace App\Console\Commands;


use App\Models\HrHoursRequested;
use Illuminate\Console\Command;


class HrRichiesteCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:hr_richieste_check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'controllo richieste non approvate';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('max_execution_time', -1);
        stream_context_set_default(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
        $token = "exWm8aP5MjxLUj2b28$2Fd";
        $path = 'https://app.metallurgicabresciana.it/turni/mb/richieste/api/check.php?';
        $path .= 'tk=' . $token;
        $getMovieList = file_get_contents($path);
        $result = json_decode($getMovieList);
        if ($result->stato == 200) {
            foreach ($result->list as $richiesta) {
                $check = HrHoursRequested::where('bacheca_id',$richiesta->richiesta->richiesta_id)
                    ->where('stato',true)
                    ->first();
                if(!empty($check->id)){

                }



            }
        }
    }
}
