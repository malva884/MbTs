<?php

namespace App\Models;

use App\Services\GoogleDrive;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskUserAssigned extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','area_id','user_id','task_id','responsible','notification'];

    static function assignTask($area_id, $user, $task, $responsible = false){

        $obj = new TaskUserAssigned();
        $obj->area_id = $area_id;
        $obj->task_id = $task->id;
        $obj->user_id = $user->id;
        $obj->responsible = $responsible;
        $obj->save();
        GoogleDrive::set_role($task->path_drive,$user->email,'writer','create');
    }

    static function checkAssignTask($users,$task, $area_id = null){
        if(empty($area_id))
            $area_id = DB::table('tasks')->select('tasks.*')
                ->where('id', $task->id)
                ->first()->area_id;

        $emploees_task = TaskUserAssigned::where('task_id', $task->id)
            //->whereNull('responsible')
            ->pluck('user_id')->toArray();

        foreach ($users as $user) {
            if (!in_array($user, $emploees_task)) {
                $user = User::find($user);
                TaskUserAssigned::assignTask($area_id, $user, $task);
                TaskLog::newTaskLog($task->id, Auth::id(), 'Assigned new user <br> &ensp;<b>' . $user->full_name . '</b>', 'dark');
            } else {
                //$user = User::find($user);
                //GoogleDrive::set_role($task->path_drive,$user->email,'writer','deleted');
                $key = array_search($user, $emploees_task);
                unset($emploees_task[$key]);
            }
        }

        if (count($emploees_task)) {
            foreach ($emploees_task as $userRemove){
                $user = User::find($userRemove);
                $romove = DB::table('task_user_assigneds')
                    ->where('task_id', $task->id)
                    ->where('user_id', $userRemove)
                    ->first();
                GoogleDrive::set_role($task->path_drive,$user->email,'writer','deleted');
                TaskLog::newTaskLog($task->id, Auth::id(), 'Remove user', 'warning');
                TaskUserAssigned::find($romove->id)->delete();
            }
        }
    }
}
