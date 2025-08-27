<?php

namespace App\Jobs;

use App\Models\FiShippedRow;
use App\Models\Gp;
use App\Models\QtFai;
use App\Models\Utility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class FiTurnoverCountFiber implements ShouldQueue
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
        $objs = FiShippedRow::where('head',$this->id)
            ->where('tipologia_cavo','5420')
            ->whereDate('created_at',date('Y-m-d'))
            ->get();

        foreach ($objs as $obj){
            $numeroFibre = Gp::numeroFibre($obj->materiale);
            if($obj->unit == 'KM')
                $kfkm = round($numeroFibre * $obj->quantita, 3);
            else
        }
        $subject = $this->title;

        $users = Utility::users_notify(['qt_fai_apertura']);

        Mail::send('emails/email_fai', compact('info'), function ($message) use ($users,$subject) {
            $message
                ->to($users)
                ->subject($subject);
        });
    }
}
