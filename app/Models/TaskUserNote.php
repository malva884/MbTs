<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskUserNote extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','task_id','user_id','padre','nota'];

    public static function nuovaNota($task_id,$nota,$padre=null){

        $obj = new TaskUserNote();
        $obj->task_id = $task_id;
        $obj->user_id = Auth::id();
        $obj->nota = $nota;
        $obj->padre = $padre;
        $obj->save();

        if(!$padre){
            $obj->padre = $obj->id;
            $obj->save();
        }

    }

    static function note($task_id){
        return DB::table('task_user_notes')->select('task_user_notes.*', 'users.full_name', 'users.avatar')
            ->join('users', 'users.id', 'task_user_notes.user_id')
            ->where('task_id', $task_id)
            ->orderBy('task_user_notes.created_at')
            ->get();
    }

    public function userNota()
    {
        return $this->hasOne(User::class,"id","user_id");
    }
}
