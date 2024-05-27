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
        $itaOttico = FiShippedRow::where('type', 5420)
            ->where('head',$obj->id)
            ->select(DB::raw('SUM(qty_value) as totale'), DB::raw('SUM(delivered_qty) as ckm'), DB::raw('SUM(qty_fkm) as fkm'))->first();

        $itaRame = FiShippedRow::where('type', 5441)
            ->where('head',$obj->id)
            ->select(DB::raw('SUM(qty_value) as totale'), DB::raw('SUM(delivered_qty) as ckm'), DB::raw('SUM(qty_fkm) as fkm'))->first();

        $info = [
            'titolo' => 'Report Spedito',
            'periodo' => date('Y-m-d'),
            'italia' => ['totale' => number_format(str_replace("-","",$itaOttico->totale + $itaRame->totale), 2,",",".").' €', 'ckm' => str_replace("-","", $itaOttico->ckm + $itaRame->ckm), 'kfkm' => str_replace("-","", round(($itaOttico->fkm + $itaRame->fkm) / 1000,0))],
            'italia_ottico' => ['totale' =>  number_format(str_replace("-","",$itaOttico->totale), 2,",",".").' €', 'ckm' =>  str_replace("-","",$itaOttico->ckm), 'kfkm' =>  str_replace("-","",round($itaOttico->fkm / 1000,0))],
            'italia_rame' => ['totale' =>  number_format(str_replace("-","",$itaRame->totale), 2,",",".").' €', 'ckm' =>  str_replace("-","",$itaRame->ckm), 'kfkm' =>  str_replace("-","",round($itaRame->fkm / 1000,0))],

            'totali' => [
                'totale' =>  number_format(str_replace("-","",$itaOttico->totale + $itaRame->totale), 2,",",".").' €',
                'ckm' =>  str_replace("-","",$itaOttico->ckm + $itaRame->ckm ),
                'kfkm' =>  str_replace("-","",round(($itaOttico->fkm + $itaRame->fkm) / 1000,0))],
        ];


        $subject = 'Report Spedito';

        $users = Utility::users_notify(['fi_spedito_giornaliero']);

        Mail::send('emails/email_spedito', compact('info'), function ($message) use ($users,$subject) {
            $message
                ->to($users)
                ->subject($subject);
        });
    }
}
