<?php

namespace App\Http\Controllers;

use App\Jobs\WfLogOrdrer;
use App\Models\WfDocument;
use App\Models\WfOrder;
use App\Models\WfUser;
use App\Models\WfUserApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WfOrderController extends Controller
{
    public function list(Request $request)
    {
        

        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $viewBy = $request->get('view');
        $commessaBy = $request->get('commessa');
        $tipologiaBy = $request->get('tipologia');

        $is_approver = WfUser::select('id','approval_start_date')->where('model',WfOrder::$modelName)->where('user_id',Auth::id())->where('disabled',false)->first();

        if (empty($sortByName)) {
            $sortByName = 'created_at';
            $orderBy = 'desc';
        }


        $objs = WfOrder::select('wf_orders.*');
        if(!empty($is_approver->id))
            $objs = $objs->select('wf_orders.*','wf_user_approvals.approval_action');
        else
            $objs = $objs->select('wf_orders.*');

        if($viewBy == 1 && !empty($is_approver->id)){
			$objs = $objs->leftJoin('wf_user_approvals', function($join)
                {
                    $join->on('wf_orders.id', '=', 'wf_user_approvals.model_id');
                    $join->where('wf_user_approvals.user_id','=',Auth::id());
                })
                ->where('wf_orders.stato','In-Approval')
				
                ->whereDate('wf_orders.created_at','>=', $is_approver->approval_start_date)
                ->Where(function ($query) use ($commessaBy) {
                    if ($commessaBy)
                        $query->WhereNull('model_id')->Where('commessa', 'LIKE', '%'.$commessaBy.'%');
                    else
                        $query
						//->WhereNotIn('user_id',[Auth::id()])
                        ->WhereNull('model_id')
                        ->where('visibile',true);
                });
        }
        elseif ($viewBy == 2 && !empty($is_approver->id)){
            $objs = $objs->leftJoin('wf_user_approvals', 'wf_orders.id', '=', 'wf_user_approvals.model_id')
                ->whereDate('wf_orders.created_at','>=', $is_approver->approval_start_date)
                ->WhereIn('user_id',[Auth::id()])
                //->orWhere('wf_orders.stato','Approved')
                ->Where(function ($query) use ($commessaBy) {
                    if ($commessaBy)
                        $query->Where('commessa', 'LIKE', '%'.$commessaBy.'%');
                    else
                        $query->where('visibile',true);
                });
        }
        elseif ($viewBy == 3 && !empty($is_approver->id)){
            $objs = $objs->leftJoin('wf_user_approvals', 'wf_orders.id', '=', 'wf_user_approvals.model_id')
                //->WhereIn('user_id',[Auth::id()])
                //->orWhere('wf_orders.stato','Approved')
                ->Where(function ($query) use ($commessaBy) {
                    if ($commessaBy)
                        $query->Where('commessa', 'LIKE', '%'.$commessaBy.'%');
                    else
                        $query->where('visibile',true);
                });
        }
		elseif ($viewBy == 3 && empty($is_approver->id)){
            $objs = $objs->Where(function ($query) use ($commessaBy) {
                    if ($commessaBy)
                        $query->Where('commessa', 'LIKE', '%'.$commessaBy.'%');
                    else
                        $query->where('visibile',true);
                });
        }
        elseif($viewBy == 1 && empty($is_approver->id)){
            $objs = $objs->where('wf_orders.stato','In-Approval')
                ->Where(function ($query) use ($commessaBy) {
                    if ($commessaBy)
                        $query->Where('commessa', 'LIKE', '%'.$commessaBy.'%');
                    else
                        $query->where('visibile',true);
                });
        }
        elseif ($viewBy == 2 && empty($is_approver->id)){

            $objs = $objs->where('wf_orders.stato','Approved')
                ->Where(function ($query) use ($commessaBy) {
                    if ($commessaBy)
                        $query->Where('commessa', 'LIKE', '%'.$commessaBy.'%');
                    else
                        $query->where('visibile',true);
                });
        }
        else{
            $objs = $objs->leftJoin('wf_user_approvals', function($join) use ($viewBy)
                {
                    if($viewBy)
                        $join->on('wf_orders.id', '=', 'wf_user_approvals.model_id');
                })
                ->Where(function ($query) use ($viewBy) {
                    if ($viewBy == 2)
                        $query->Where('user_id', Auth::id());
                });
        }

        $objs = $objs->Where(function ($query) use ($tipologiaBy) {
                if ($tipologiaBy)
                    $query->Where('tipologia', $tipologiaBy);
            });

        $objs = $objs->orderBy($sortByName, $orderBy)
			->distinct('wf_orders.id')	
            ->paginate($request->itemsPerPage);

        return response()->json(['objs' => $objs, 'is_approver' => !empty($is_approver->id) ]);
    }

    public function getDocument($id)
    {
		// 1. Controllo di sicurezza: se l'ID è la stringa "undefined" o non è un UUID valido, rispondi con errore o array vuoto
		if ($id === 'undefined' || empty($id) || !preg_match('/^[a-f\d]{8}-(?:[a-f\d]{4}-){3}[a-f\d]{12}$/i', $id)) {
			Log::warning("Id Commessa non valido ricevuto in getDocument: '{$id}'");
			return response()->json([]); // Oppure: return response()->json(['error' => 'ID non valido'], 400);
		}

		Log::info("Id Commessa valido elaborato: {$id}");

		$objs = DB::table('wf_documents')
			->where('model_id', $id)
			->orWhere('model_head_id', $id)
			->distinct()
			->orderBy('created_at', 'asc')
			->get();

		return response()->json($objs);
    }

    public function approval(Request $request)
    {
        $obj = WfOrder::find($request->id);
        $completed = WfUserApproval::approval($request->id, 'WfOrder', Auth::id(), $request->role_id, 'Approved', null);

        if($completed)
            DB::table("wf_orders")
                ->where('id',$request->id)
                ->orWhere('id_commessa_padre',$request->id)
                ->update(['stato' => 'Approved', 'data_approvazione' => date('Y-m-d')]);
				
		$is_approver = WfUser::select('id','approval_start_date')->where('model',WfOrder::$modelName)->where('user_id',Auth::id())->where('disabled',false)->first();

        $next = WfOrder::select('wf_orders.*')
            ->select('wf_orders.*','wf_user_approvals.approval_action')
			->leftJoin('wf_user_approvals', function($join)
                {
                    $join->on('wf_orders.id', '=', 'wf_user_approvals.model_id');
                    $join->where('wf_user_approvals.user_id','=',Auth::id());
                })
            ->where('wf_orders.stato','In-Approval')
            ->whereDate('wf_orders.created_at','>=', $is_approver->approval_start_date)
            //->WhereNotIn('user_id',[Auth::id()])
                 ->WhereNull('model_id')
                 ->where('visibile',true)
            ->orderBy('created_at', 'desc')
            ->first();
			

		if(!is_null($completed))
			Dispatch(new WfLogOrdrer($obj->id));
		
		if(empty($next->id))
			$next = '0';
		
        return response()->json(
            [
                'success' => true,
                'message' => 'Commessa Approvata' ,
                'color' => 'success',
                'obj' => $next
            ]
        );
    }
	
	public function userOpenFile(Request $request, $id)
    {
        $document = WfDocument::find($id);
        $document->userOpenFile = Auth::user()->matricola;
        $document->save();
    }
	
	public function getFile(Request $request)
	{
		
		$document = WfDocument::where('userOpenFile', $request->user)->first();

        $idFile = null;
        if(!empty($document->id)){
            $idFile = $document->id_file_drive;
            $document->userOpenFile = null;
            $document->save();
        }

        return response()->json(
            [
                'success' => true,
                'idFile' => $idFile,
            ]
        );
	}
}
