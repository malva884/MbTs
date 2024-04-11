<?php

namespace App\Http\Controllers;

use App\Jobs\FatturatoEmail;
use App\Models\FiTurnoverHead;
use App\Models\FiTurnoverRow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FiTurnoverRowController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $tipoCavoBy = $request->get('tipologiaCavo');
        $materialeBy = $request->get('materiale');
        $dataBy = $request->get('data');
        if (!$dataBy)
            $dataBy = [date('Y-m-d')];

        if (empty($sortByName)) {
            $sortByName = 'data_documento';
            $orderBy = 'desc';
        }
        $objs = DB::table('fi_turnover_rows')
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('materiale', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($tipoCavoBy) {
                if ($tipoCavoBy)
                    $query->Where('tipologia_cavo', $tipoCavoBy);
            })
            ->Where(function ($query) use ($dataBy) {
                if ($dataBy) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2)
                        $query->whereBetween('data_documento', $dataBy);
                    else
                        $query->Where('data_documento', $dataBy);

                }

            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }


    public function report(Request $request)
    {
        $tipoCavoBy = $request->get('tipologiaCavo');
        $materialeBy = $request->get('materiale');
        $dataBy = $request->get('data');

        $return = [];
        Log::channel('stderr')->info($request);
        $itaOttico = FiTurnoverRow::where('tipologia_cavo', 5420)
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('materiale', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($tipoCavoBy) {
                if ($tipoCavoBy)
                    $query->Where('tipologia_cavo', $tipoCavoBy);
            })
            ->Where(function ($query) use ($dataBy) {
                if ($dataBy) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2)
                        $query->whereBetween('data_documento', $dataBy);
                    else
                        $query->Where('data_documento', $dataBy);
                }
            })
            ->where('paese', 'ITA')
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(kfkm) as kfkm'))->first();
        // Log::channel('stderr')->info($itaOttico->totale);
        $itaRame = FiTurnoverRow::where('tipologia_cavo', 5441)
            ->where('paese', 'ITA')
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('materiale', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($tipoCavoBy) {
                if ($tipoCavoBy)
                    $query->Where('tipologia_cavo', $tipoCavoBy);
            })
            ->Where(function ($query) use ($dataBy) {
                if ($dataBy) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2)
                        $query->whereBetween('data_documento', $dataBy);
                    else
                        $query->Where('data_documento', $dataBy);
                }
            })
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(kfkm) as kfkm'))->first();


        $eu_ottico = FiTurnoverRow::where('tipologia_cavo', 5420)
            ->where('paese', 'UE')
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('materiale', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($tipoCavoBy) {
                if ($tipoCavoBy)
                    $query->Where('tipologia_cavo', $tipoCavoBy);
            })
            ->Where(function ($query) use ($dataBy) {
                if ($dataBy) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2)
                        $query->whereBetween('data_documento', $dataBy);
                    else
                        $query->Where('data_documento', $dataBy);
                }
            })
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(kfkm) as kfkm'))->first();

        $eu_rame = FiTurnoverRow::where('tipologia_cavo', 5441)
            ->where('paese', 'UE')
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('materiale', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($tipoCavoBy) {
                if ($tipoCavoBy)
                    $query->Where('tipologia_cavo', $tipoCavoBy);
            })
            ->Where(function ($query) use ($dataBy) {
                if ($dataBy) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2)
                        $query->whereBetween('data_documento', $dataBy);
                    else
                        $query->Where('data_documento', $dataBy);
                }
            })
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(kfkm) as kfkm'))->first();


        $ex_ottico = FiTurnoverRow::where('tipologia_cavo', 5420)
            ->where('paese', 'EX-UE')
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('materiale', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($tipoCavoBy) {
                if ($tipoCavoBy)
                    $query->Where('tipologia_cavo', $tipoCavoBy);
            })
            ->Where(function ($query) use ($dataBy) {
                if ($dataBy) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2)
                        $query->whereBetween('data_documento', $dataBy);
                    else
                        $query->Where('data_documento', $dataBy);
                }
            })
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(kfkm) as kfkm'))->first();

        $ex_rame = FiTurnoverRow::where('tipologia_cavo', 5441)
            ->where('paese', 'EX-UE')
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('materiale', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($tipoCavoBy) {
                if ($tipoCavoBy)
                    $query->Where('tipologia_cavo', $tipoCavoBy);
            })
            ->Where(function ($query) use ($dataBy) {
                if ($dataBy) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2)
                        $query->whereBetween('data_documento', $dataBy);
                    else
                        $query->Where('data_documento', $dataBy);
                }
            })
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(kfkm) as kfkm'))->first();
        $return = [
            'italia_totali' => ['totale' => $itaOttico->totale + $itaRame->totale, 'ckm' => $itaOttico->ckm + $itaRame->ckm, 'kfkm' => $itaOttico->kfkm + $itaRame->kfkm],
            'italia_5420' => ['totale' => $itaOttico->totale, 'ckm' => $itaOttico->ckm, 'kfkm' => $itaOttico->kfkm],
            'italia_5441' => ['totale' => $itaRame->totale, 'ckm' => $itaRame->ckm, 'kfkm' => $itaRame->kfkm],

            'eu_totali' => ['totale' => $eu_ottico->totale + $eu_rame->totale, 'ckm' => $eu_ottico->ckm + $eu_rame->ckm, 'kfkm' => $eu_ottico->kfkm + $eu_rame->kfkm],
            'eu_5420' => ['totale' => $eu_ottico->totale, 'ckm' => $eu_ottico->ckm, 'kfkm' => $eu_ottico->kfkm],
            'eu_5441' => ['totale' => $eu_rame->totale, 'ckm' => $eu_rame->ckm, 'kfkm' => $eu_rame->kfkm],

            'exstra_totali' => ['totale' => $ex_ottico->totale + $ex_rame->totale, 'ckm' => $ex_ottico->ckm + $ex_rame->ckm, 'kfkm' => $ex_ottico->kfkm + $ex_rame->kfkm],
            'exstra_5420' => ['totale' => $ex_ottico->totale, 'ckm' => $ex_ottico->ckm, 'kfkm' => $ex_ottico->kfkm],
            'exstra_5441' => ['totale' => $ex_rame->totale, 'ckm' => $ex_rame->ckm, 'kfkm' => $ex_rame->kfkm],

        ];
        //Log::channel('stderr')->info($ita);
        return response()->json([$return]);
    }

    public function check(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $tipoCavoBy = $request->get('tipologiaCavo');
        $materialeBy = $request->get('materiale');
        $dataBy = $request->get('data');


        if (empty($sortByName)) {
            $sortByName = 'data_documento';
            $orderBy = 'desc';
        }
        $objs = DB::table('fi_turnover_rows')
            ->where('quantita', '0.000')
            ->whereNotIn('account', ['404000', '452100', '452000'])
            ->whereNotIn('documento_tipo', ['M8', 'M9', 'V8', 'V9'])
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('materiale', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($tipoCavoBy) {
                if ($tipoCavoBy)
                    $query->Where('tipologia_cavo', $tipoCavoBy);
            })
            ->Where(function ($query) use ($dataBy) {
                if ($dataBy) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2)
                        $query->whereBetween('data_documento', $dataBy);
                    else
                        $query->Where('data_documento', $dataBy);

                }

            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function set_quantita(Request $request, $id)
    {
        $obj = FiTurnoverRow::find($id);
        $obj->quantita = $request->quantita;
        $obj->unit = $request->unit;

        if ($obj->tipologia_cavo == '5441') {
            if ($obj->unit == 'M') {
                $obj->ckm = round($obj->quantita / 1000, 3);
            } elseif ($obj->unit == 'KM') {
                $obj->ckm = $obj->quantita;
            }
        } else {
            $numeroFibre = substr($obj->materiale, 7, 4);
            if ($obj->unit == 'M' && $numeroFibre > 0) {
                $obj->kfkm = round(($obj->quantita / 1000) * $numeroFibre, 3);
                $obj->ckm = round($obj->quantita / 1000, 3);
            } elseif ($obj->unit == 'KM' && $numeroFibre > 0) {
                $obj->kfkm = round($numeroFibre * $obj->quantita, 3);
                $obj->ckm = $obj->quantita;
            }
        }
        $obj->save();

        $head = FiTurnoverHead::find($obj->head);

        $check = DB::table('fi_turnover_rows')
            ->select('id')
            ->where('quantita', '0.000')
            ->whereNotIn('account', ['404000', '452100', '452000'])
            ->whereNotIn('documento_tipo', ['M8', 'M9', 'V8', 'V9'])
            ->count();

        if (!$check) {
            // importo import a true che significa che il file e stato importato e pronto per l'invio della notifica
            $head->import = true;

            // invio email di notifica alla coda
            dispatch(new FatturatoEmail($head->id));
        }

        $head->value_kfkm = $obj->kfkm + $head->value_kfkm;
        $head->value_ckm = $obj->ckm + $head->value_ckm;
        $head->save();


        $message = 'Messaggi.Qauntita-Salvata';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => $obj
            ]
        );

    }
}
