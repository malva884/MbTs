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
        $clientiBy = json_decode($request->get('clienti'));
        $idBy = $request->get('id');
        $clienti = [];
        foreach ($clientiBy as $cliente)
            $clienti[]= $cliente->id;
       // Log::channel('stderr')->info($cliente->id);
        $return = [];

        $itaOttico = FiTurnoverRow::where('tipologia_cavo', 5420)
            ->Where(function ($query) use ($idBy) {
                if ($idBy)
                    $query->Where('head',$idBy);
            })
            ->Where(function ($query) use ($clienti) {
                if ($clienti)
                    $query->WhereIn('codice_cliente',$clienti);
            })
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
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(fkm) as fkm'))->first();

        $itaRame = FiTurnoverRow::where('tipologia_cavo', 5441)
            ->where('paese', 'ITA')
            ->Where(function ($query) use ($idBy) {
                if ($idBy)
                    $query->Where('head',$idBy);
            })
            ->Where(function ($query) use ($clienti) {
                if ($clienti)
                    $query->WhereIn('codice_cliente',$clienti);
            })
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
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(fkm) as fkm'))->first();


        $eu_ottico = FiTurnoverRow::where('tipologia_cavo', 5420)
            ->where('paese', 'UE')
            ->Where(function ($query) use ($idBy) {
                if ($idBy)
                    $query->Where('head',$idBy);
            })
            ->Where(function ($query) use ($clienti) {
                if ($clienti)
                    $query->WhereIn('codice_cliente',$clienti);
            })
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
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(fkm) as fkm'))->first();

        $eu_rame = FiTurnoverRow::where('tipologia_cavo', 5441)
            ->where('paese', 'UE')
            ->Where(function ($query) use ($idBy) {
                if ($idBy)
                    $query->Where('head',$idBy);
            })
            ->Where(function ($query) use ($clienti) {
                if ($clienti)
                    $query->WhereIn('codice_cliente',$clienti);
            })
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
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(fkm) as fkm'))->first();


        $ex_ottico = FiTurnoverRow::where('tipologia_cavo', 5420)
            ->where('paese', 'EX-UE')
            ->Where(function ($query) use ($idBy) {
                if ($idBy)
                    $query->Where('head',$idBy);
            })
            ->Where(function ($query) use ($clienti) {
                if ($clienti)
                    $query->WhereIn('codice_cliente',$clienti);
            })
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
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(fkm) as fkm'))->first();

        $ex_rame = FiTurnoverRow::where('tipologia_cavo', 5441)
            ->where('paese', 'EX-UE')
            ->Where(function ($query) use ($idBy) {
                if ($idBy)
                    $query->Where('head',$idBy);
            })
            ->Where(function ($query) use ($clienti) {
                if ($clienti)
                    $query->WhereIn('codice_cliente',$clienti);
            })
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
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(fkm) as fkm'))->first();
        $return = [
            'italia_totali' => ['totale' => $itaOttico->totale + $itaRame->totale, 'ckm' => round($itaOttico->ckm + $itaRame->ckm,3), 'kfkm' => round(($itaOttico->fkm + $itaRame->fkm) / 1000, 0)],
            'italia_5420' => ['totale' => $itaOttico->totale, 'ckm' => round($itaOttico->ckm,3), 'kfkm' => round($itaOttico->fkm / 1000,0)],
            'italia_5441' => ['totale' => $itaRame->totale, 'ckm' => round($itaRame->ckm,3), 'kfkm' => round($itaRame->fkm / 1000,0)],

            'eu_totali' => ['totale' => $eu_ottico->totale + $eu_rame->totale, 'ckm' => round($eu_ottico->ckm + $eu_rame->ckm,3), 'kfkm' => round(($eu_ottico->fkm + $eu_rame->fkm) / 1000,0)],
            'eu_5420' => ['totale' => $eu_ottico->totale, 'ckm' => round($eu_ottico->ckm,3), 'kfkm' => round($eu_ottico->fkm / 1000,0)],
            'eu_5441' => ['totale' => $eu_rame->totale, 'ckm' => round($eu_rame->ckm,3), 'kfkm' => round($eu_rame->fkm / 1000,0)],

            'exstra_totali' => ['totale' => $ex_ottico->totale + $ex_rame->totale, 'ckm' => round($ex_ottico->ckm + $ex_rame->ckm,3), 'kfkm' => round(($ex_ottico->fkm + $ex_rame->fkm) / 1000,0)],
            'exstra_5420' => ['totale' => $ex_ottico->totale, 'ckm' => round($ex_ottico->ckm,3), 'kfkm' =>round($ex_ottico->fkm / 1000,0)],
            'exstra_5441' => ['totale' => $ex_rame->totale, 'ckm' => round($ex_rame->ckm,3), 'kfkm' => round($ex_rame->fkm / 1000,0)],
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
            ->select(DB::raw('SUM(importo_valuta_locale) as totale'), DB::raw('SUM(ckm) as ckm'), DB::raw('SUM(fkm) as fkm'), 'codice_cliente', 'tipologia_cavo','cliente')
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

        $cc_ckm = $ofc_ckm = $ofc_fkm = 0;

        if ($obj->tipologia_cavo == '5441') {
            if ($obj->unit == 'M') {
                $obj->ckm = round($obj->quantita / 1000, 3);
            } elseif ($obj->unit == 'KM') {
                $obj->ckm = $obj->quantita;
            }
            $cc_ckm = $obj->ckm;
        } else {
            $numeroFibre = substr($obj->materiale, 7, 4);
            if ($obj->unit == 'M' && $numeroFibre > 0) {
                $obj->fkm = round(($obj->quantita / 1000) * $numeroFibre, 3);
                $obj->ckm = round($obj->quantita / 1000, 3);
            } elseif ($obj->unit == 'KM' && $numeroFibre > 0) {
                $obj->fkm = round($numeroFibre * $obj->quantita, 3);
                $obj->ckm = $obj->quantita;
            }
            $ofc_ckm = $obj->ckm;
            $ofc_fkm = $obj->fkm;
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

        $head->value_fkm_ofc = $ofc_fkm + $head->value_fkm_ofc;
        $head->value_ckm_cc = $cc_ckm + $head->value_ckm_cc;
        $head->value_ckm_ofc = $ofc_ckm + $head->value_ckm_ofc;
        $head->save();

        $data = explode("-",$obj->data_documento);
        $d = $data[0].'-'.$data[1].'-01';
        $t = new TargetController();

        $targets = [
            'fkm_ofc' => str_replace("-", "", $head->value_fkm_ofc),
            'ckm_cc' => str_replace("-", "",$head->value_ckm_cc ),
            'ckm_ofc' => str_replace("-", "", $head->value_ckm_ofc),
        ];
        $t->update($targets,1,$d);


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
            ->selectRaw('materiale, count(id) as numero ,sum(importo_valuta_locale) as totale, sum(ckm) as ckm_t, sum(fkm) as fkm_t')
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
