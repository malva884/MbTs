<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TaskUesrArea extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','area_id','user_id','solo_assegnati','aprire_task','modificare_task','chiudere_task','eliminare_task','responsabile'];

    public static function addUser($idArea, $idUtente, $responsabile = false)
    {
        $obj = new TaskUesrArea();
        $obj->area_id = $idArea;
        $obj->user_id = $idUtente;
        $obj->responsabile = $responsabile;
        if($responsabile){
            $obj->solo_assegnati = false;
            $obj->aprire_task = true;
            $obj->modificare_task = true;
            $obj->chiudere_task = true;
            $obj->eliminare_task = true;
        }
        $obj->save();

    }

    public function user()
    {
        return $this->hasOne(User::class,"id","user_id");
    }

    public static function setPermissionFolders ($email, $execution, $areaId, $soloAssegnati = true){
        if($soloAssegnati){
            $tasks = DB::table('task_user_assigneds')
                ->select('task_uesr_areas.*','users.email','users.id')
                ->join('tasks','tasks.id','task_uesr_areas.area')
                ->join('users','users.id','task_uesr_areas.user_id')
                ->where('area_id',$areaId)
                ->where('users.email',$email)
                ->get();
        }else{
            $tasks = DB::table('task_uesr_areas')
                ->select('task_uesr_areas.*','users.email')
                ->join('users','users.id','task_uesr_areas.user_id')
                ->where('area_id',$areaId)
                ->groupBy()
                ->get();
        }

    }
}
