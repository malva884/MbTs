<?php

namespace App\Jobs;

use App\Models\Utility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class NotificaCreazioneWifi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id;

    /**
     * Create a new job instance.
     */
    public function __construct($id)
    {
        $this->id = $id;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if(!is_array($this->id))
            $this->id = [$this->id];

        $accounts = DB::table('registro_account_wifis')->select('registro_account_wifis.*')
            ->whereIn('register_id', $this->id)
            ->get();


        $users = Utility::users_notify(['richiesta_wifi']);

        Mail::send('emails/email_richiesta_wifi', compact('accounts'), function ($message) use ($users) {
            $message
                ->to($users)
                ->subject('Richiesta Credenzila Wifi');
        });

    }
}
