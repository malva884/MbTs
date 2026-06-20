<?php

namespace App\Models;

use App\Jobs\TaskNotifiche;
use App\Services\GoogleDrive;
use http\Message;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Task extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','area_id','padre','responsabile_id','utente_id','codice','stato','reparto_id',
        'mansione_id','titolo','descrizione','data_chiusura','data_scadenza','data_scadenza_iniziale','giorni_dopo_scadenza','completamento',
        'priorieta','numero','near_miss_id','path_drive','company_id','cross','created_at','richiedente'];

    static function stored($request){

        try {
            $approvato = false;
            if(empty($request->created_at))
                $request->created_at = date('Y-m-d');

            $lastRecord = DB::table('tasks')->select('numero')
                ->where('area_id',$request->area_id)
                ->whereNull('padre')
                ->Where(function ($query) use ($request) {
                    if ($request->created_at){
                        $data = explode("-",$request->created_at);
                        $query->whereYear('created_at',$data[0]);
                        $query->whereMonth('created_at',$data[1]);
                        $query->whereDay('created_at',$data[2]);
                    }
                })
                ->orderBy('created_at', 'desc')->first();

            $area =  DB::table('task_areas')->where('id',$request->area_id)->first();

            $obj = new Task();
            $numero = 1;
            if (!empty($lastRecord->numero))
                $numero = $lastRecord->numero + 1;


            if(!empty($request->created_at)){
                $data = explode("-",$request->created_at);
                $ref = $area->sigla .$data[0].$data[1].$data[2].'_' . $numero;
                $obj->created_at = $data[0].'-'.$data[1].'-'.$data[2].' '.date('H:i:s');
            }else
                $ref = $area->sigla . date('Ymd') . '_' . $numero;

            $obj->area_id = $request->area_id;
            $obj->codice = $ref;
            $obj->responsabile_id = $area->responsabile_id;
            $obj->titolo = $request->titolo;
            $obj->descrizione = $request->descrizione;
            $obj->giorni_dopo_scadenza = $request->giorni_dopo_scadenza;
            $obj->priorieta = $request->priorieta;
            $obj->data_scadenza = date('Y-m-d', strtotime($request->data_scadenza));
            $obj->data_scadenza_iniziale = date('Y-m-d', strtotime($request->data_scadenza));
            $obj->reparto_id = $request->reparto_id;
            $obj->mansione_id = $request->mansione_id;
            $obj->path_drive = GoogleDrive::add_folder($area->cartella_drive, $obj->codice, 'google', false);
            $obj->numero = $numero;
            $obj->completamento = 0;
            $obj->utente_id = Auth::id();
            $obj->richiedente = $request->richiedente;

            if ($area->approvazione_task && !$request->responsabile)
                $obj->stato = 3; // Da Approvare
            else {
                $obj->stato = $request->stato;
                $approvato = true;
            }
            $obj->save();

            if($approvato === true){
                TaskLog::newTaskLog($obj->id, Auth::id(), 'Task Approvato');
                TaskUserAssigned::checkAssignTask($request->users, $obj, $request->area_id);
                dispatch(new TaskNotifiche($obj->id, 'Task Assegnato.', 'Ti è stato assegnato un nuovo Task (' . $obj->codice . ') .', false, false, true));
            }else
                dispatch(new TaskNotifiche($obj->id, 'Task Aperto.', 'Il Task (' . $obj->codice . ') è in attesa di Approvazione.', false, true,false));


            return [
                'success' => true,
                'message' => 'Messaggi.Task-Salvato',
                'color' => 'success',
                'obj' => null
            ];
        } catch (\Exception $e) {
            Log::channel('stderr')->info($e);
            return [
                'success' => true,
                'message' => 'Messaggi.404',
                'color' => 'error',
                'obj' => null
            ];
        }
    }

    static function notifications ($task_id,$object,$content, $assignedOnly = false, $responsibleOnly = false, $newAssigned = false){

        $users = DB::table('task_user_assigneds')->select('users.*','task_uesr_areas.responsabile')
            ->join('users','users.id','task_user_assigneds.user_id')
            ->join("task_uesr_areas",function($join){
                $join->on("task_uesr_areas.area_id","=","task_user_assigneds.area_id")
                    ->on("task_uesr_areas.user_id","=","task_user_assigneds.user_id");
            })
            ->where('task_user_assigneds.task_id', $task_id)
            ->Where(function ($query) use ($assignedOnly) {
                if ($assignedOnly) {
                    $query->where('task_uesr_areas.responsabile', false);
                }
            })
            ->Where(function ($query) use ($responsibleOnly) {
                if ($responsibleOnly) {
                    $query->where('task_uesr_areas.responsabile', true);
                }
            })
            ->Where(function ($query) use ($newAssigned) {
                if ($newAssigned) {
                    $query->where('task_user_assigneds.notification', false);
                }
            })
            ->get();


        foreach ($users as $user){
            $email = 'gregorio.grande@stl.tech'; //$user->email;
            $name = $user->full_name;
            Mail::send('emails/email_task', compact('content','name','task_id'), function ($message) use ($email, $object) {
                $message
                    ->to($email)
                    ->subject($object);
            });
        }

        if ($newAssigned)
            DB::table('task_user_assigneds')
                ->where('task_id',$task_id)
                ->update([
                    'notification' => true
                ]);

    }
}
