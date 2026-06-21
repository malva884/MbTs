<?php

namespace App\Jobs;

use App\Models\FiShippedHead;
use App\Models\FiShippedRow;
use App\Models\Utility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ControlloQuantitaMagazzino implements ShouldQueue
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
        $obj = DB::table('pl_warehouses')
            ->select('quantita','quantita_minima','pn_interno')
            ->where('id',$this->id)
            ->first();
        if($obj->quantita_minima >= $obj->quantita ){
            $subject = $obj->pn_interno.' Quantità a magazzino bassa.';
            $content = 'PN Interno: '.$obj->pn_interno.' <br/> Quantità a magazzino: '.$obj->quantita;

            $users = Utility::users_notify(['it_monitoring_wherause']);

            Mail::send('emails/email_white', compact('content'), function ($message) use ($users,$subject) {
                $message
                    ->to($users)
                    ->subject($subject);
            });
        }
    }
}
