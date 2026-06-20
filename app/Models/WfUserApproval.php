<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WfUserApproval extends Model
{
    use HasFactory;

    protected $fillable = ['id','user_id','role_id','model_id','model','approval_action','comment'];

    static function approval($model_id, $model, $user_id, $role_id, $approval_action, $comment = null)
    {
        $obj = DB::table('wf_user_approvals')->where('model',$model)->where('model_id',$model_id)->where('user_id',$user_id)->first();
        if(!empty($obj->model_id))
            return NULL;

        $obj = new WfUserApproval();
        $obj->model_id = $model_id;
        $obj->model = $model;
        $obj->user_id = $user_id;
        $obj->role_id = $role_id;
        $obj->approval_action = $approval_action;
        $obj->comment = $comment;
        $obj->save();

        $users = self::checkFianlWf($model, $model_id, $role_id);

        if(empty($users))
            return true;
        else
            return false;
    }

    static function checkFianlWf($model_name, $model_id, $role_id)
    {
        $nameSpace = '\\App\\Models\\';
        $model = $nameSpace . $model_name;
        $wf = $model::find($model_id);
        $data = explode("T", $wf->created_at);
        $users = WfUser::where('model', $model_name)
            ->where('disabled',false)
            ->where('approval_start_date', '<=', $data[0])
            ->where('role_id',$role_id)
            ->whereNotIn('user_id', function($query) use ($model_name, $model_id, $role_id){
                $query->select('user_id')
                    ->from('wf_user_approvals')
                    ->where('model',$model_name)
                    ->where('model_id',$model_id)
                    ->where('role_id',$role_id);
            })->count();

        return $users;
    }
}
