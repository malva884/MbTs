<?php

namespace App\Http\Controllers;

use App\Models\HrHoursRequested;
use App\Models\HrHoursRequestedDetail;
use Illuminate\Http\Request;

class HrHoursRequestedDetailController extends Controller
{
    public function listUserOff(Request $request, $id)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');


        if (empty($sortByName)) {
            $sortByName = 'data';
            $orderBy = 'desc';
        }

        $richiesta = HrHoursRequested::where('id',$id)->first();
        $giorniRichiesta = HrHoursRequestedDetail::where('richiesta_id',$id)->orderBy('data')->get();
        $days = [];
        foreach ($giorniRichiesta as $giorni)
            $days[] = $giorni->data;


        $utentiOff = HrHoursRequestedDetail::select('hr_hours_requested_details.id','dipendente_nome','dipendente_cognome','hr_hours_requested_details.data','hr_hours_requested_details.tipologia','hr_hours_requested_details.ore_richieste')
            ->join('hr_hours_requesteds','hr_hours_requesteds.id','hr_hours_requested_details.richiesta_id')
            //->where('richiesta_id','<>',$id)
            ->where('hr_hours_requested_details.confermato',true)
            ->where('centro_di_costo',$richiesta->centro_di_costo)
            ->whereIn('hr_hours_requested_details.data',$days)
            ->orderBy('data', 'desc')
            ->orderBy('dipendente_nome', 'asc')
            ->get();

        return response()->json($utentiOff);

    }
}
