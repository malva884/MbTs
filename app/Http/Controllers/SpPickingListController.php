<?php

namespace App\Http\Controllers;

use App\Models\SpPickingList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SpPickingListController extends Controller
{
    public function index(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $ordineBy = $request->get('ordineBy');

        Log::channel('stderr')->info($request);
        if (empty($sortByName)) {
            $sortByName = 'created_at';
            $orderBy = 'desc';
        }

        $objs = SpPickingList::select('*')
        //where('company_id',auth()->user()->company_id)
            ->Where(function ($query) use ($ordineBy) {
                if ($ordineBy)
                    $query->Where('ordine', 'LIKE', '%' . $ordineBy . '%');
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);
        return response()->json($objs);
    }

    public function stored(Request $request)
    {
        foreach ($request->ordini as $ordine){
            $obj = new SpPickingList();
            $obj->ordine = $ordine['cdOrdine'];
            $obj->numeroLotti = 0;
            $obj->save();
        }

        $message = 'Messaggi.Ordini-Inseriti.';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'objs' => ''
            ]
        );
    }
}
