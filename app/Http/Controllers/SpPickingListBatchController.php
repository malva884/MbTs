<?php

namespace App\Http\Controllers;

use App\Models\SpPickingList;
use App\Models\SpPickingListBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SpPickingListBatchController extends Controller
{
    public function index(Request $request,$id)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');

        if (empty($sortByName)) {
            $sortByName = 'created_at';
            $orderBy = 'desc';
        }

        $objs = SpPickingListBatch::select('*')
            ->where('picking_id',$id)
            //where('company_id',auth()->user()->company_id)
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);
       //Log::channel('stderr')->info($id);
        return response()->json($objs);
    }

    public function add(Request $request, $id)
    {
        $message = '';
        $color = '';

        if(!empty($request->batch)){
            $packing = SpPickingList::find($id);
            $giacenzaObj = DB::connection('sqlsrv_gp')->table('AGG_GIACENZE')
                ->select('AGG_GIACENZE.*')
                ->join('AGG_MASTER_TMP','AGG_MASTER_TMP.cdProdotto','AGG_GIACENZE.cdProdotto')
                ->where('cdOrdine',$packing->ordine)
                ->where('cdLotto',$request->batch)
                ->first();

            $checkObj = SpPickingListBatch::all()->where('ordine',$packing->ordine)->where('lotto',$request->batch)->first();

            if(!empty($checkObj->id) && !empty($giacenzaObj->cdLotto)){
                $message = 'Messaggi.Batch-Gia-Presente';
                $color = 'warning';
            }
            elseif(!empty($giacenzaObj->cdLotto) && empty($checkObj->id)){
                $obj = new SpPickingListBatch();
                $obj->picking_id = $id;
                $obj->lotto = $request->batch;
                $obj->ordine = $packing->ordine;
                $obj->materiale = $giacenzaObj->cdProdotto;
                $obj->giacenza = round($giacenzaObj->Giacenza,3);
                $obj->um = $giacenzaObj->cdUM;
                $obj->save();
                $packing->numeroLotti+= 1;
                $packing->save();

                $message = 'Messaggi.Batch-Inserito';
                $color = 'success';
            }else{
                $message = 'Messaggi.Batch-Non-Trovato';
                $color = 'error';
            }
        }
        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => $color,
                'objs' => null
            ]
        );
    }
}
