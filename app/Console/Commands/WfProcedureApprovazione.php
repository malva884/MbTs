<?php

namespace App\Console\Commands;

use App\Models\Utility;
use App\Models\WfProcedure;
use App\Models\WfUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;


class WfProcedureApprovazione extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:wf_procedure_to_be_approved';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nuove Procedure Emesse o Revisionate';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $documents = WfProcedure::where('notification',true)
            ->whereNull('padre_id')
            ->whereNull('sup')
            ->get();

        $users = DB::table('wf_users')
            ->join('users','wf_users.user_id','users.id')
            ->select('email')
            ->where('model', 'WfProcedure')
            ->where('disabled',false)
            ->where('approval_start_date', '<=', date('Y-m-d'))
            ->pluck('email')->toArray();

        foreach ($documents as $document){
           $url = URL::to("/workflow/procedure/view/".$document->id);
            Mail::send('emails/wfProcedureNotifica', compact('document','url'), function ($message) use ($users,$document) {
                $message
                    ->to($users)
                    ->subject('Richiesta di Revisione Procedura: '.$document->procedura);
            });
            $document->notification = false;
            $document->save();

        }
    }
}
