<?php

namespace App\Http\Controllers;

use App\Models\HrApproverRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrApproverRequestController extends Controller
{
    public function list(Request $request){

        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $userBy = $request->get('user');
        $centroBy = $request->get('centro');
        $disattivoBy = $request->get('disattivo');


        if(empty($sortByName)){
            $sortByName = 'created_at';
            $orderBy = 'asc';
        }
        $objs = DB::table('hr_approver_requests')
            ->select('hr_approver_requests.*','users.full_name')
            ->leftJoin('users','users.id','hr_approver_requests.user_id')
            ->Where(function ($query) use ($userBy) {
                if ($userBy)
                    $query->Where('users.full_name', 'LIKE','%'.$userBy.'%');
            })
            ->Where(function ($query) use ($centroBy) {
                if ($centroBy)
                    $query->Where('centro_ci_costo', 'LIKE','%'.$centroBy.'%');
            })
            ->Where(function ($query) use ($disattivoBy) {
                if ($disattivoBy)
                    $query->Where('disattivo',$disattivoBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function get_centro()
    {
        $objs = DB::connection('mysql_old')->table('employees')->get();
        return response()->json([]);
    }

    public function store(Request $request)
    {
        $obj = new HrApproverRequest();
        $obj->user_id = $request->user_id;
        $obj->livello = $request->livello;
        $obj->centro_ci_costo = $request->centro_ci_costo;
        $obj->notifica = ($request->notifica ? true:false);
        $obj->save();

        $message = 'Messaggi.Approvatore-Aggionto';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function update(Request $request, $id)
    {
        $obj = HrApproverRequest::where('id',$id)->first();
        $obj->livello = $request->livello;
        $obj->centro_ci_costo = $request->centro_ci_costo;
        $obj->notifica = ($request->notifica ? true:false);
        $obj->disattivo = ($request->disattivo ? true:false);
        $obj->save();

        $message = 'Messaggi.Approvatore-Modificato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }
}
