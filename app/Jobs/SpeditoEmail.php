<?php

namespace App\Jobs;

use App\Models\FiShippedHead;
use App\Models\Utility;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SpeditoEmail implements ShouldQueue
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
        $obj = FiShippedHead::find($this->id);
        $myDate = $obj->anno.'-'.$obj->mese.'-1';
        $periodo = Carbon::createFromFormat('Y-m-d', $myDate)->format('Y-M');

        $info = array(
            'titolo' => 'Nuovo Spedito Caricato',
            'periodo' => $periodo,
        );
        $subject = 'Nuovo Spedito';

        $users = Utility::users_notify(['fi.spedito.notification','fi.spedito.admin']);

        Mail::send('emails/email_spedito', compact('info'), function ($message) use ($users,$subject) {
            $message
                ->to($users)
                ->subject($subject);
        });
    }
}
