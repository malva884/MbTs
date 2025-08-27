<?php

namespace App\Jobs;

use App\Models\PlAsset;
use App\Models\Utility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class AssistenzaEmail implements ShouldQueue
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
        $obj = PlAsset::find($this->id);

        $info = [
            'richiesta' => 'Richiesta Di Assistenza',
            'device' => $obj->hostName,
            ];


        $subject = 'Richiesta Assistenza';

        $users = Utility::users_notify(['it_richiesta_assistenza']);

        Mail::send('emails/email_assistenza', compact('info'), function ($message) use ($users,$subject) {
            $message
                ->to($users)
                ->subject($subject);
        });
    }
}
