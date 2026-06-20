<?php

namespace App\Http\Controllers;

use App\Models\WfUser;
use App\Models\WfUserApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WfUserController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $utenteBy = $request->get('user');
        $modelBy = $request->get('model');
        $ruoloBy = $request->get('ruoli');

        if (empty($sortByName)) {
            $sortByName = 'full_name';
            $orderBy = 'asc';
        }

        $objs = DB::table('wf_users')
            ->join('users','wf_users.user_id','users.id')
            ->join('wf_roles','wf_users.role_id','wf_roles.id')
            ->select('users.full_name','wf_roles.role','wf_users.*')
            ->Where(function ($query) use ($utenteBy) {
                if ($utenteBy)
                    $query->Where('wf_users.user_id', $utenteBy);
            })
            ->Where(function ($query) use ($modelBy) {
                if ($modelBy)
                    $query->Where('wf_users.model', $modelBy);
            })
            ->Where(function ($query) use ($ruoloBy) {
                if ($ruoloBy)
                    $query->Where('wf_users.role_id', $ruoloBy);
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function store(Request $request)
    {
        $obj = WfUser::where('user_id',$request->user_id)->where('model',$request->model)->where('role_id',$request->role_id)->first();

        if(empty($obj->id)){
            $obj = new WfUser();
            $obj->user_id = $request->user_id;
            $obj->model = $request->model;
            $obj->role_id = $request->role_id;
            $obj->approval_start_date = $request->approval_start_date;
            $obj->disabled = ($request->disabled ? true:false);
            $obj->save();

            $message = 'Messaggi.Salvato';
            $color = 'success';
        }
        else{
            $message = 'Messaggi.Utente-Gia-Presente';
            $color = 'warning';
        }

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => $color,
                'obj' => $obj
            ]
        );
    }

    public function update(Request $request, $id)
    {
        $obj = WfUser::find($id);
        //$obj->user_id = $request->user_id;
        $obj->model = $request->model;
        $obj->role_id = $request->role_id;
        $obj->approval_start_date = $request->approval_start_date;
        $obj->disabled = ($request->disabled ? true:false);
        $obj->save();

        $message = 'Messaggi.Salvato';
        $color = 'success';
    }

    public function is_approver(Request $request)
    {

        $date = explode("T", $request->date);
        $nameSpace = '\\App\\Models\\';
        $model = $nameSpace . $request->model_name;
        $roles = $model::$roleIdApproved;
        $user =  WfUser::select('wf_roles.*','wf_roles.id as role_id')
            ->join('wf_roles','wf_users.role_id','wf_roles.id')
            ->where('wf_users.model',$request->model_name)
            ->where('wf_users.user_id',Auth::id())
            ->where('wf_users.disabled',false)
            ->where('wf_users.approval_start_date','<=', $date[0])
            ->whereIn('wf_roles.role',$roles)
            ->first();


        $role_id = null;
        $status = null;
        if(!empty($user->id)){
            $role_id = $user->role_id;
            $status = WfUserApproval::select('user_id')
                ->where('model_id',$request->id)
                ->where('user_id',Auth::id())
                ->where('model',$request->model_name)
                ->first();
        }

        return response()->json(['is_approver' => !empty($user->id), 'role_id' => $role_id, 'i_approved' => !empty($status->user_id), 'info_approved' => $status]);
    }

    public function delete($id)
    {
        $obj = WfUser::find($id);
        $obj->disabled = true;
        $obj->save();

    }
}
