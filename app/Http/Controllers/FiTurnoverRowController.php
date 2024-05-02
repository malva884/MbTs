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
        $clienti= json_decode($request->clienti);
        $id = '';
        if(!empty($request->id))
            $id = $request->id;

        if (!$dataBy)
            $dataBy = [date('Y-m-d')];

        if (empty($sortByName)) {
            $sortByName = 'data_documento';
            $orderBy = 'desc';
        }
        $objs = DB::table('fi_turnover_rows')
            ->Where(function ($query) use ($id) {
                if ($id)
                    $query->Where('head',$id);
            })
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('materiale', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($clienti) {
                if (count($clienti))
                    $query->WhereIn('codice_cliente', $clienti);
            })
            ->Where(function ($query) use ($tipoCavoBy) {
                if ($tipoCavoBy)
                    $query->Where('tipologia_cavo', $tipoCavoBy);
            })
            ->Where(function ($query) use ($dataBy) {
                if (is_string($dataBy)) {
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
                if (is_string($dataBy)) {
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
                if (is_string($dataBy)) {
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
                if (is_string($dataBy)) {
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
                if (is_string($dataBy)) {
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
                if (is_string($dataBy)) {
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
                if (is_string($dataBy)) {
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
        return response()->json([$return]);
    }

    public function clienti(Request $request)
    {
        $tipoCavoBy = $request->get('tipologiaCavo');
        $materialeBy = $request->get('materiale');
        $dataBy = $request->get('data');
        $clienti= json_decode($request->clienti);


        $itaOttico = FiTurnoverRow::Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('materiale', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($clienti) {
                if (count($clienti))
                    $query->WhereIn('codice_cliente', $clienti);
            })
            ->Where(function ($query) use ($tipoCavoBy) {
                if ($tipoCavoBy)
                    $query->Where('tipologia_cavo', $tipoCavoBy);
            })
            ->Where(function ($query) use ($dataBy) {
                if (is_string($dataBy)) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2)
                        $query->whereBetween('data_documento', $dataBy);
                    else
                        $query->Where('data_documento', $dataBy);
                }
            })
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(kfkm) as kfkm'), 'codice_cliente', 'tipologia_cavo','cliente')
            ->groupBy('codice_cliente','tipologia_cavo','cliente')
            ->orderBy('cliente')
            ->get();

        //Log::channel('stderr')->info($itaOttico);

        return response()->json($itaOttico);

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
            ->where('check',false)
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
        $obj->check = true;
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
            ->where('check',false)
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

    public function get_cavi(Request $request, $id)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $tipoCavoBy = $request->get('tipologiaCavo');
        $materialeBy = $request->get('materiale');
        $searchBy = $request->get('search');
        $dataBy = $request->get('data');
        $tipologia = $request->get('tipologiaCavo');
        if (empty($sortByName)) {
            $sortByName = 'materiale';
            $orderBy = 'desc';
        }

        $objs = DB::table('fi_turnover_rows')
            ->selectRaw('materiale, count(id) as numero ,sum(importo_valuta_locale) as totale, sum(ckm) as ckm_t, sum(kfkm) as kfkm_t')
            ->where('codice_cliente',$id)
            ->where('tipologia_cavo',$tipologia)
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('materiale', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($searchBy) {
                if ($searchBy)
                    $query->Where('materiale', 'LIKE', '%' . $searchBy . '%');
            })
            ->Where(function ($query) use ($tipoCavoBy) {
                if ($tipoCavoBy)
                    $query->Where('tipologia_cavo', $tipoCavoBy);
            })
            ->Where(function ($query) use ($dataBy) {
                if (is_string($dataBy)) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2)
                        $query->whereBetween('data_documento', $dataBy);
                    else
                        $query->Where('data_documento', $dataBy);
                }
            })
            ->groupBy('materiale')
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function get_clienti()
    {
        $clienti = DB::table('fi_turnover_rows')
            ->select(DB::raw('DISTINCT cliente'), 'codice_cliente')
            ->orderBy('cliente')
            ->get();

        return response()->json($clienti);
    }
}
