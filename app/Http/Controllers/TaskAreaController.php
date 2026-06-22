<?php

namespace App\Http\Controllers;

use App\Models\TaskArea;
use App\Models\TaskUesrArea;
use App\Services\GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskAreaController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $nascostaBy = $request->get('nascosta');
        $user_id = $request->get('user_id');


        if (empty($sortByName)) {
            $sortByName = 'area';
            $orderBy = 'asc';
        }
		
		$cross = new TaskArea();
        $cross->id = '0000';
        $cross->area = 'CROSS';
        $cross->tipologia = '1';
        $cross->colore = 'error';

        $objs = TaskArea::select('task_areas.*','task_uesr_areas.responsabile','task_areas.responsabile_id as responsabile_area')
            ->join('task_uesr_areas','task_uesr_areas.area_id','task_areas.id')
            ->where('task_uesr_areas.user_id',$user_id)
            ->Where(function ($query) use ($nascostaBy) {
                if ($nascostaBy)
                    $query->Where('nascosta', $nascostaBy);
            })
            ->orderBy($sortByName, $orderBy)
            ->get();

		$objs->prepend($cross);

        return response()->json($objs);
    }

    public function store(Request $request)
    {

        $obj = new TaskArea();
        $obj->area =  ucfirst(strtolower($request->area));
        $obj->responsabile_id = $request->responsabile_id;
        $obj->tipologia = $request->tipologia;
        $obj->sigla = strtoupper($request->sigla);
        $obj->colore = $request->colore;
        $obj->cartella_drive = $request->cartella_drive;
        if(empty($obj->cartella_drive ))
            $obj->cartella_drive = GoogleDrive::add_folder([env('ID_GOOGLE_TASK')], $obj->area, 'google', false);

        $obj->save();

        TaskUesrArea::addUser($obj->id, $request->responsabile_id, true);

        $message = 'Messaggi.Nuova-Area-Creata';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function update(Request $request, $id)
    {
        $obj = new TaskArea();
        $obj->responsabile = $request->responsabile;
        $obj->tipologia = $request->tipologia;
        $obj->colore = $request->colore;
        $obj->cartella_drive = $request->cartella_drive;
        $obj->save();

        $message = 'Messaggi.Area-Modificata';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function checkResponsabile(Request $request)
    {
		if($request->area_id != '0000')
			$obj = DB::table('task_uesr_areas')->select('task_uesr_areas.id','task_areas.responsabile_id')
				->join('task_areas','task_areas.id','task_uesr_areas.area_id')
				->where('area_id',$request->area_id)
				->where('user_id',$request->user_id)
				->where('responsabile',1)
				->first();

        $responsabileArea = false;
        if(!empty($obj->id))
            $responsabileArea = ($obj->responsabile_id == $request->user_id);

        return response()->json(['responsabile'=> !empty($obj->id), 'responsabile_area'=> $responsabileArea]);
    }

    public function view($id)
    {
		$obj = null;
        if($id != '0000')
			$obj = TaskArea::find($id);

        return response()->json($obj);
    }

    public function update_set(Request $request, $id)
    {

        $obj = TaskArea::find($id);
        $obj->approvazione_task = (in_array('new-task',$request->setting) ? true:false);
        $obj->approvazione_sub_task = (in_array('new-sub-task',$request->setting) ? true:false);
        $obj->notifiche = (in_array('notifiche',$request->setting) ? true:false);
        $obj->nascosta = (in_array('Disattiva',$request->setting) ? true:false);
		$obj->tipologia = (in_array('Privato',$request->setting ) ? 2:1);

        $obj->save();

        $message = 'Messaggi.Impostazioni-Salvate';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }
}
