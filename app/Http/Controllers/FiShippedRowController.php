<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FiShippedRowController extends Controller
{
    public function list(Request $request, $id)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
		$materialeBy = $request->get('materiale');
        $lavorazioneBy = $request->get('lavorazione');
        $dataBy = $request->get('data');

        if (empty($sortByName)) {
            $sortByName = 'date_row';
            $orderBy = 'desc';
        }
		//\Log::info($id);
        $objs = DB::table('fi_shipped_rows')
			->Where('head', $id)
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('material', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($lavorazioneBy) {
                if ($lavorazioneBy)
                    $query->Where('type',$lavorazioneBy);
            })
            ->Where(function ($query) use ($dataBy) {
                if ($dataBy){
                    $dataBy = explode(' to ',$dataBy);
                    if(count($dataBy) == 2)
                        $query->whereBetween('date_row', $dataBy);
                    else
                        $query->Where('date_row', $dataBy);

                }
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }
}
