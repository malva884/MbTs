<?php

namespace App\Jobs;

use App\Http\Controllers\FiTurnoverRowController;
use App\Models\FiTurnoverHead;
use App\Models\FiTurnoverRow;
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
        $dataBy = date('Y-m-01').' to '.date('Y-m-d');
        $return = [];

        $itaOttico = FiTurnoverRow::where('tipologia_cavo', 5420)
            ->Where(function ($query) use ($dataBy) {
                if (is_string($dataBy)) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2)
                        $query->whereBetween('data_documento', $dataBy);
                    else
                        $query->Where('data_documento', $dataBy);
                }
            })
            ->where('paese', 'ITA')
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(fkm) as fkm'))->first();

        $itaRame = FiTurnoverRow::where('tipologia_cavo', 5441)
            ->where('paese', 'ITA')
            ->Where(function ($query) use ($dataBy) {
                if (is_string($dataBy)) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2)
                        $query->whereBetween('data_documento', $dataBy);
                    else
                        $query->Where('data_documento', $dataBy);
                }
            })
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(fkm) as fkm'))->first();


        $eu_ottico = FiTurnoverRow::where('tipologia_cavo', 5420)
            ->where('paese', 'UE')
            ->Where(function ($query) use ($dataBy) {
                if (is_string($dataBy)) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2)
                        $query->whereBetween('data_documento', $dataBy);
                    else
                        $query->Where('data_documento', $dataBy);
                }
            })
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(fkm) as fkm'))->first();

        $eu_rame = FiTurnoverRow::where('tipologia_cavo', 5441)
            ->where('paese', 'UE')
            ->Where(function ($query) use ($dataBy) {
                if (is_string($dataBy)) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2)
                        $query->whereBetween('data_documento', $dataBy);
                    else
                        $query->Where('data_documento', $dataBy);
                }
            })
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(fkm) as fkm'))->first();


        $ex_ottico = FiTurnoverRow::where('tipologia_cavo', 5420)
            ->where('paese', 'EX-UE')
            ->Where(function ($query) use ($dataBy) {
                if (is_string($dataBy)) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2)
                        $query->whereBetween('data_documento', $dataBy);
                    else
                        $query->Where('data_documento', $dataBy);
                }
            })
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(fkm) as fkm'))->first();

        $ex_rame = FiTurnoverRow::where('tipologia_cavo', 5441)
            ->where('paese', 'EX-UE')
            ->Where(function ($query) use ($dataBy) {
                if (is_string($dataBy)) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2)
                        $query->whereBetween('data_documento', $dataBy);
                    else
                        $query->Where('data_documento', $dataBy);
                }
            })
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(fkm) as fkm'))->first();

        $info = [
            'titolo' => 'Report Fatturato',
            'periodo' => $dataBy,
            'italia' => ['totale' => str_replace("-","",$itaOttico->totale + $itaRame->totale), 'ckm' => str_replace("-","", $itaOttico->ckm + $itaRame->ckm), 'kfkm' => str_replace("-","", round(($itaOttico->fkm + $itaRame->fkm) / 1000,0))],
            'italia_ottico' => ['totale' =>  str_replace("-","",$itaOttico->totale), 'ckm' =>  str_replace("-","",$itaOttico->ckm), 'kfkm' =>  str_replace("-","",round($itaOttico->fkm / 1000,0))],
            'italia_rame' => ['totale' =>  str_replace("-","",$itaRame->totale), 'ckm' =>  str_replace("-","",$itaRame->ckm), 'kfkm' =>  str_replace("-","",round($itaRame->fkm / 1000,0))],

            'eu' => ['totale' =>  str_replace("-","",$eu_ottico->totale + $eu_rame->totale), 'ckm' =>  str_replace("-","",$eu_ottico->ckm + $eu_rame->ckm), 'kfkm' =>  str_replace("-","",round(($eu_ottico->fkm + $eu_rame->fkm) / 1000,0))],
            'eu_ottico' => ['totale' =>  str_replace("-","",$eu_ottico->totale), 'ckm' =>  str_replace("-","",$eu_ottico->ckm), 'kfkm' =>  str_replace("-","",round($eu_ottico->fkm / 1000,0))],
            'eu_rame' => ['totale' =>  str_replace("-","",$eu_rame->totale), 'ckm' =>  str_replace("-","",$eu_rame->ckm), 'kfkm' =>  str_replace("-","",round($eu_rame->fkm / 1000,0))],

            'exstra' => ['totale' =>  str_replace("-","",$ex_ottico->totale + $ex_rame->totale), 'ckm' =>  str_replace("-","",$ex_ottico->ckm + $ex_rame->ckm), 'kfkm' =>  str_replace("-","",round(($ex_ottico->fkm + $ex_rame->fkm) / 1000,0))],
            'exstra_ottico' => ['totale' =>  str_replace("-","",$ex_ottico->totale), 'ckm' =>  str_replace("-","",$ex_ottico->ckm), 'kfkm' =>  str_replace("-","",round($ex_ottico->fkm / 1000,0))],
            'exstra_rame' => ['totale' =>  str_replace("-","",$ex_rame->totale), 'ckm' =>  str_replace("-","",$ex_rame->ckm), 'kfkm' =>  str_replace("-","",round($ex_rame->fkm / 1000,0))],

            'totali' => [
                'totale' =>  str_replace("-","",$ex_ottico->totale + $ex_rame->totale + $itaOttico->totale + $itaRame->totale + $eu_ottico->totale + $eu_rame->totale),
                'ckm' =>  str_replace("-","",$ex_ottico->ckm + $ex_rame->ckm + $itaOttico->ckm + $itaRame->ckm + $eu_ottico->ckm + $eu_rame->ckm),
                'kfkm' =>  str_replace("-","",round(($ex_ottico->fkm + $ex_rame->fkm + $itaOttico->fkm + $itaRame->fkm + $eu_ottico->fkm + $eu_rame->fkm) / 1000,0))],
        ];


        $subject = 'Report Fatturato';
        $users = Utility::users_notify(['fi_fatturato_giornaliero']);

        Mail::send('emails/email_fatturato', compact('info'), function ($message) use ($users,$subject) {
            $message
                ->to(array_values($users))
                ->subject($subject);
        });
    }
}
