<?php

namespace App\Jobs;

use App\Models\QtFai;
use App\Models\Utility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Fai implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $title;
    protected $id;

    /**
     * Create a new job instance.
     */
    public function __construct($id, $title)
    {
        $this->id = $id;
        $this->title = $title;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $obj = QtFai::find($this->id);

        $info = array(
            'numero_fai' => $obj->numero_fai,
            'titolo' => $this->title,
            'descrizione' => $obj->descrizione
        );
        $subject = $this->title;

        $users = Utility::users_notify('qt.fai.notification');

        Mail::send('emails/email_fai', compact('info'), function ($message) use ($users,$subject) {
            $message
                ->to($users)
                ->subject($subject);
        });
    }
}
