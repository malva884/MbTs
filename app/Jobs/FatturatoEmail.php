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

        // Calcola subtotali per tipo di cavo
        $subtotale_rame = [
            'totale' => $itaRame->totale + $eu_rame->totale + $ex_rame->totale,
            'ckm' => $itaRame->ckm + $eu_rame->ckm + $ex_rame->ckm,
            'kfkm' => round(($itaRame->fkm + $eu_rame->fkm + $ex_rame->fkm) / 1000, 0)
        ];

        $subtotale_ottico = [
            'totale' => $itaOttico->totale + $eu_ottico->totale + $ex_ottico->totale,
            'ckm' => $itaOttico->ckm + $eu_ottico->ckm + $ex_ottico->ckm,
            'kfkm' => round(($itaOttico->fkm + $eu_ottico->fkm + $ex_ottico->fkm) / 1000, 0)
        ];

        $info = [
            'titolo' => 'Report Fatturato',
            'periodo' => $dataBy,
            'italia' => ['totale' => number_format(abs($itaOttico->totale + $itaRame->totale), 2, ',', '.'), 'ckm' => number_format(abs($itaOttico->ckm + $itaRame->ckm), 0, ',', '.'), 'kfkm' => number_format(abs(round(($itaOttico->fkm + $itaRame->fkm) / 1000, 0)), 0, ',', '.')],
            'italia_ottico' => ['totale' => number_format(abs($itaOttico->totale), 2, ',', '.'), 'ckm' => number_format(abs($itaOttico->ckm), 0, ',', '.'), 'kfkm' => number_format(abs(round($itaOttico->fkm / 1000, 0)), 0, ',', '.')],
            'italia_rame' => ['totale' => number_format(abs($itaRame->totale), 2, ',', '.'), 'ckm' => number_format(abs($itaRame->ckm), 0, ',', '.'), 'kfkm' => number_format(abs(round($itaRame->fkm / 1000, 0)), 0, ',', '.')],

            'eu' => ['totale' => number_format(abs($eu_ottico->totale + $eu_rame->totale), 2, ',', '.'), 'ckm' => number_format(abs($eu_ottico->ckm + $eu_rame->ckm), 0, ',', '.'), 'kfkm' => number_format(abs(round(($eu_ottico->fkm + $eu_rame->fkm) / 1000, 0)), 0, ',', '.')],
            'eu_ottico' => ['totale' => number_format(abs($eu_ottico->totale), 2, ',', '.'), 'ckm' => number_format(abs($eu_ottico->ckm), 0, ',', '.'), 'kfkm' => number_format(abs(round($eu_ottico->fkm / 1000, 0)), 0, ',', '.')],
            'eu_rame' => ['totale' => number_format(abs($eu_rame->totale), 2, ',', '.'), 'ckm' => number_format(abs($eu_rame->ckm), 0, ',', '.'), 'kfkm' => number_format(abs(round($eu_rame->fkm / 1000, 0)), 0, ',', '.')],

            'exstra' => ['totale' => number_format(abs($ex_ottico->totale + $ex_rame->totale), 2, ',', '.'), 'ckm' => number_format(abs($ex_ottico->ckm + $ex_rame->ckm), 0, ',', '.'), 'kfkm' => number_format(abs(round(($ex_ottico->fkm + $ex_rame->fkm) / 1000, 0)), 0, ',', '.')],
            'exstra_ottico' => ['totale' => number_format(abs($ex_ottico->totale), 2, ',', '.'), 'ckm' => number_format(abs($ex_ottico->ckm), 0, ',', '.'), 'kfkm' => number_format(abs(round($ex_ottico->fkm / 1000, 0)), 0, ',', '.')],
            'exstra_rame' => ['totale' => number_format(abs($ex_rame->totale), 2, ',', '.'), 'ckm' => number_format(abs($ex_rame->ckm), 0, ',', '.'), 'kfkm' => number_format(abs(round($ex_rame->fkm / 1000, 0)), 0, ',', '.')],

            'subtotale_rame' => ['totale' => number_format(abs($subtotale_rame['totale']), 2, ',', '.'), 'ckm' => number_format(abs($subtotale_rame['ckm']), 0, ',', '.'), 'kfkm' => number_format(abs($subtotale_rame['kfkm']), 0, ',', '.')],
            'subtotale_ottico' => ['totale' => number_format(abs($subtotale_ottico['totale']), 2, ',', '.'), 'ckm' => number_format(abs($subtotale_ottico['ckm']), 0, ',', '.'), 'kfkm' => number_format(abs($subtotale_ottico['kfkm']), 0, ',', '.')],

            'totali' => [
                'totale' => number_format(abs($ex_ottico->totale + $ex_rame->totale + $itaOttico->totale + $itaRame->totale + $eu_ottico->totale + $eu_rame->totale), 2, ',', '.'),
                'ckm' => number_format(abs($ex_ottico->ckm + $ex_rame->ckm + $itaOttico->ckm + $itaRame->ckm + $eu_ottico->ckm + $eu_rame->ckm), 0, ',', '.'),
                'kfkm' => number_format(abs(round(($ex_ottico->fkm + $ex_rame->fkm + $itaOttico->fkm + $itaRame->fkm + $eu_ottico->fkm + $eu_rame->fkm) / 1000, 0)), 0, ',', '.')],
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
