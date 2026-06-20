<?php

namespace App\Http\Controllers;

use App\Models\TaskArea;
use App\Models\TaskUesrArea;
use App\Models\User;
use App\Services\GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskUesrAreaController extends Controller
{
    public function list(Request $request, $id)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $userBy = $request->get('search');
        if(empty($sortByName)){
            $sortByName = 'full_name';
            $orderBy = 'asc';
        }


        $objs = TaskUesrArea::select('task_uesr_areas.*','users.full_name','task_areas.responsabile_id as responsabile_area')
            ->join('users','users.id','task_uesr_areas.user_id')
            ->leftJoin('task_areas','task_areas.id','task_uesr_areas.area_id')
            ->where('area_id',$id)
            ->Where(function ($query) use ($userBy) {
                if ($userBy)
                    $query->where('users.full_name','LIKE','%'.$userBy.'%');
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);


        return response()->json($objs);
    }

    public function addUser(Request $request,$id)
    {
        $obj = new TaskUesrArea();
        $obj->area_id = $id;
        $obj->user_id = $request->user_id;
        $obj->solo_assegnati = $request->solo_assegnati;
        $obj->save();

        if(empty($obj->solo_assegnati)){
            $area = TaskArea::find($id);
            $user = User::find($obj->user_id);
            GoogleDrive::set_role($area->cartella_drive,$user->email,'writer','create');
        }


        $message = 'Messaggi.Utente-Aggiunto';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => $obj
            ]
        );

    }

    public function editUser(Request $request,$id)
    {

        $obj = TaskUesrArea::find($id);
        $obj->responsabile = $request->responsabile;
        $obj->solo_assegnati = $request->solo_assegnati;
        $obj->aprire_task = $request->aprire_task;
        $obj->modificare_task = $request->modificare_task;
        $obj->chiudere_task = $request->chiudere_task;
        $obj->eliminare_task = $request->eliminare_task;
        $obj->save();

        if(!$obj->solo_assegnati){
            $area = TaskArea::find($obj->area_id);
            $user = User::find($obj->user_id);
            GoogleDrive::set_role($area->cartella_drive,$user->email,'writer','create');
        }

        $message = 'Messaggi.Utente-Task-Modificato';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function getUsers(Request $request, $id)
    {
        if($id == 'undefined')
            return response()->json([]);


        $only = $request->get('only');
        $escludiGiaMenbri = $request->get('escludiGiaMenbri');

        if(empty($only)) {
            $users = User::select('users.id','users.full_name','users.avatar')
                //->leftJoin('task_uesr_areas','task_uesr_areas.user_id','users.id')
                ->Where(function ($query) use ($escludiGiaMenbri,$id) {
                    if ($escludiGiaMenbri){
                        $query->whereNotIn('users.id',DB::table('task_uesr_areas')->select('user_id')->where('area_id',$id));

                    }
                })
                ->orderBy('users.full_name')
                ->get();
        }
        else{
            $users = DB::table('task_uesr_areas')->select('users.id','users.full_name','users.avatar')
                ->join('users','users.id','task_uesr_areas.user_id')
                ->where('task_uesr_areas.area_id',$id)
                ->Where(function ($query) use ($escludiGiaMenbri,$id) {
                    if ($escludiGiaMenbri)
                        $query->whereNotIn('users.id',DB::table('task_user_assigneds')->select('user_id')->where('task_id',$escludiGiaMenbri));
                })
                ->orderBy('users.full_name')
                ->get();
        }

        return response()->json($users);
    }
}
