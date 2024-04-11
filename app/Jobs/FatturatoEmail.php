<?php

namespace App\Jobs;

use App\Models\FiTurnoverHead;
use App\Models\QtFai;
use App\Models\Utility;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FatturatoEmail implements ShouldQueue
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
        $obj = FiTurnoverHead::find($this->id);
        $myDate = $obj->anno.'-'.$obj->mese.'-1';
        $periodo = Carbon::createFromFormat('Y-m-d', $myDate)->format('Y-M');

        $info = array(
            'titolo' => 'Nuovo Fatturato Caricato',
            'periodo' => $periodo,
        );
        $subject = 'Nuovo Fatturato';

        $users = Utility::users_notify('fi.fatturato.notification');

        Mail::send('emails/email_fatturato', compact('info'), function ($message) use ($users,$subject) {
            $message
                ->to($users)
                ->subject($subject);
        });
    }
}
