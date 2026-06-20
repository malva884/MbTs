<?php

namespace App\Http\Controllers;

use App\Jobs\TaskNotifiche;
use App\Models\Task;
use App\Models\TaskArea;
use App\Models\TaskLog;
use App\Models\TaskUserAssigned;
use App\Models\TaskUserNote;
use App\Services\GoogleDrive;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function list(Request $request, $id)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $priorietaBy = $request->get('priorieta');
        $statoBy = $request->get('stato');
        $titoloBy = $request->get('titolo');

        if (empty($sortByName)) {
            $sortByName = 'codice';
            $orderBy = 'desc';
        }

        if( $id!== '0000')
            $userArea = DB::table('task_uesr_areas')
                ->where('area_id', $id)
                ->where('user_id', Auth::id())
                ->first();

        if ((!isset($userArea->solo_assegnati) && $id == '0000') || $userArea->solo_assegnati) {
            $objs = Task::select('tasks.*', 'users.full_name')
                ->leftJoin('task_user_assigneds', 'tasks.id', 'task_user_assigneds.task_id')
                ->join('users', 'tasks.utente_id', 'users.id')
                //->Where('tasks.area_id', $id)
                ->Where(function ($query) use ($id) {
                    if ($id != '0000')
                        $query->Where('tasks.area_id', $id);
                    else
                        $query->Where('tasks.cross', true);
                })
                ->whereNull('tasks.padre')
                ->Where(function ($query) use ($titoloBy) {
                    if ($titoloBy)
                        $query->Where('tasks.titolo','Like', '%'.$titoloBy.'%');
                })
                ->Where(function ($query) use ($priorietaBy) {
                    if ($priorietaBy)
                        $query->Where('tasks.priorieta', $priorietaBy);
                })
                ->Where(function ($query) use ($statoBy) {
                    if ($statoBy){
                        $query->Where('tasks.stato', $statoBy);

                    }
                })
                ->Where('task_user_assigneds.user_id', Auth::id())
                ->OrWhere('tasks.stato', 3)->where('tasks.utente_id', Auth::id());
        } else {
            Log::channel('stderr')->info('NOOOOOO');
            $objs = Task::select('tasks.*', 'users.full_name')
                ->join('users', 'tasks.utente_id', 'users.id')
                ->Where('tasks.area_id', $id)
                ->whereNull('padre');
        }


        $objs = $objs->Where(function ($query) use ($titoloBy) {
                if ($titoloBy)
                    $query->Where('tasks.titolo','Like', '%'.$titoloBy.'%');
            })
            ->Where(function ($query) use ($priorietaBy) {
                if ($priorietaBy)
                    $query->Where('tasks.priorieta', $priorietaBy);
            })
            ->Where(function ($query) use ($statoBy) {
                if ($statoBy){
                    $query->Where('tasks.stato', $statoBy);

                }
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function sub_task_list(Request $request, $id)
    {
        $objs = DB::table('tasks')->select('tasks.*', 'users.full_name')
            ->join('users', 'users.id', 'tasks.utente_id')
            ->where('padre', $id)
            ->whereNotNull('padre')
            ->orderBy('codice')
            ->get();

        return response()->json($objs);
    }

    public function users_task_list($id)
    {
        $objs = DB::table('task_user_assigneds')
            ->join('users', 'task_user_assigneds.user_id', 'users.id')
            ->where('task_id', $id)
            ->orderBy('full_name')
            ->pluck('users.id');

        return response()->json($objs);
    }

    public function setUsers(Request $request, $id)
    {
        $task = DB::table('tasks')->where('id', $id)->first();
        TaskUserAssigned::checkAssignTask($request->users, $task, $request->area_id);
        dispatch(new TaskNotifiche($task->id, 'Task Assegnato.', 'Ti è stato assegnato un nuovo Task (' . $task->codice . ') .', false, false, true));

    }

    public function store(Request $request)
    {
        Task::stored($request);

        $message = 'Messaggi.Task-Salvato';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => null
            ]
        );
    }

    public function store_sub_task(Request $request)
    {
        $area = DB::table('task_areas')->select('task_areas.*')
            ->where('id', $request->area_id)
            ->first();

        $i = 65;
        $totalSubTask = DB::table('tasks')
            ->where('padre', $request->padre)
            ->count();
        $i += $totalSubTask;

        $obj = new Task();
        $obj->area_id = $request->area_id;
        $obj->padre = $request->padre;
        $obj->utente_id = Auth::id();
        $obj->codice = $request->codice . '_' . chr($i);
        $obj->responsabile_id = $request->responsabile_id;
        $obj->titolo = $request->titolo;
        $obj->descrizione = $request->descrizione;
        $obj->priorieta = $request->priorieta;
        $obj->completamento = 0;
        $obj->reparto_id = $request->reparto_id;
        $obj->mansione_id = $request->mansione_id;
        $obj->numero = $request->numero;
        $obj->path_drive = GoogleDrive::add_folder($request->path_drive, $obj->codice, 'google', false);
        if ($request->responsabile || !$area->approvazione_sub_task) {
            $obj->stato = 1;
        } else {
            $obj->stato = 3;
        }
        $obj->save();

        if ($request->responsabile) {
            TaskLog::newTaskLog($obj->padre, Auth::id(), 'Nuovo Sub Task: ' . $obj->codice, 'warning');
            Task::notifications($obj->id, 'Nuovo Sub Task.', 'Sub Task creato (' . $obj->codice . ') .', true, false, false);
        } else
            Task::notifications($obj->id, 'Sub Task Aperto.', 'Il Task (' . $obj->codice . ') è in attesa di Approvazione.', false, true, false);


        $message = 'Messaggi.Task-Salvato';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => null
            ]
        );
    }

    public function update_sub_task(Request $request, $id)
    {
        $logs = [];
        $task = Task::find($id);
        $taskStati = ['1' => 'Aperto', '2' => 'Chiuso', '3' => 'Da Approvare', '4' => 'Sospeso', '5' => 'In Svolgimento',];
        $taskPriorieta = ['1' => 'Basso', '2' => 'Normale', '3' => 'Alto', '4' => 'Critico',];
        $approvato = false;
        if ($task->stato == 3 && $task->stato != $request->stato)
            $approvato = true;
        if ($task->priorieta != $request->priorieta)
            $logs[] = '<div class="app-timeline-text mb-1">
                <span>Variazione Priorieta: ' . $taskPriorieta[$task->priorieta] . '</span>
                &#10145
                <span>' . $taskPriorieta[$request->priorieta] . '</span>
              </div>';
        if ($task->stato != $request->stato)
            $logs[] = '<div class="app-timeline-text mb-1">
                <span>Variazione Stato: ' . $taskStati[$task->stato] . '</span> 
                &#10145 
                <span>' . $taskStati[$request->stato] . '</span>
              </div>';
        $task->titolo = $request->titolo;
        $task->descrizione = $request->descrizione;
        $task->priorieta = $request->priorieta;
        if ($task->stato != $request->stato && $request->stato == 2)
            $task->data_chiusura = date('Y-m-d');
        $task->stato = $request->stato;
        $task->richiedente = $request->richiedente;
        if ($task->data_scadenza != $request->data_scadenza){
            $old = $task->data_scadenza;
            $logs[] = '<div class="app-timeline-text mb-1">
                <span>Variazione Scadenza: ' . $old . '</span>
                &#10145
                <span>' . $request->data_scadenza . '</span>
              </div>';
            $task->data_scadenza = $request->data_scadenza;
        }
        $task->save();

        foreach ($logs as $key => $log)
            TaskLog::newTaskLog($id, Auth::id(), $log, 'info');


        $message = 'Messaggi.Sub Task-Modificato.';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => null
            ]
        );
    }

    public function approvazioneTask(Request $request, $id)
    {
        $taskStati = ['1' => 'Aperto', '2' => 'Chiuso', '3' => 'Da Approvare', '4' => 'Sospeso', '5' => 'In Svolgimento',];
        $task = Task::find($id);
        $logs[] = '<div class="app-timeline-text mb-1">
                <span>Variazione Stato: ' . $taskStati[$task->stato] . '</span> 
                &#10145 
                <span>' . $taskStati[$request->task['stato']] . '</span>
              </div>';

        $task->stato = $request->task['stato'];
        $task->save();
        if (is_null($task->padre)) {
            TaskUserAssigned::checkAssignTask($request->users, $task, $task->area_id);
            dispatch(new TaskNotifiche($task->id, 'Task Assegnato.', 'Ti è stato assegnato un nuovo Task (' . $task->codice . ') .', false, false, true));
        }

        foreach ($logs as $key => $log)
            TaskLog::newTaskLog($id, Auth::id(), $log, 'info');

        $message = 'Messaggi.Sub Task-Modificato.';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => null
            ]
        );
    }

    public function avanzamento(Request $request, $id)
    {
        $task = Task::find($id);
        $task->completamento = $request->avanzamento;
        $task->save();

        TaskUserNote::nuovaNota($id, '<i>Avanzamento:</i> ' . $request->nota, $id);
        TaskLog::newTaskLog($id, Auth::id(), 'Avanzamento Task: ' . $task->codice, 'info');
        Task::notifications($id, 'Avanzamento Task.', 'Avanzamento Task ' . $task->codice . ' .', true, true, false);

        $message = 'Messaggi.Avanzamento-Salvato';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => null
            ]
        );
    }

    public function nuovaNota(Request $request, $id)
    {
        $task = Task::find($id);
        TaskUserNote::nuovaNota($id, $request->nota, $request->padre);
        TaskLog::newTaskLog($id, Auth::id(), 'Nuovo Commento: ' . $task->codice, 'primary');


        $message = 'Messaggi.Nota-Salvata';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => null
            ]
        );
    }

    public function noteTask($id)
    {

        $objs = TaskUserNote::select('task_user_notes.*', 'users.nome', 'users.cognome')
            ->join('users', 'users.id', 'task_user_notes.user_id')
            ->where('task_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $note = [];
        $user = Auth::user();
        $note['contact'] = [
            'id' => $user->id,
            'fullName' => $user->full_name,
            'role' => '',
            'about' => '',
            'avatar' => $user->avatar,
            'status' => '',
        ];

        foreach ($objs as $obj) {

            $note['users'][$obj->user_id]['name'] = $obj->cognome . ' ' . $obj->nome;

            $note['chat'][] = [
                'message' => $obj->nota,
                'time' => $obj->created_at,
                //'time'=> 'Mon Dec 11 2018 07:45:15 GMT+0000 (GMT)',
                'senderId' => $obj->user_id,
                'feedback' => [
                    'isSent' => true,
                    'isDelivered' => true,
                    'isSeen' => true,
                ],
            ];
        }
        return response()->json($note);
    }

    public function listaDaApprovare(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $statoBy = $request->get('stato');

        if (empty($sortByName)) {
            $sortByName = 'codice';
            $orderBy = 'desc';
        }

        $objs = Task::whereNull('padre')
            ->select('tasks.*', 'users.full_name')
            ->join('users', 'tasks.utente_id', 'users.id')
            ->whereIn('area_id', function ($query) {
                $query->select('area_id')->from('task_uesr_areas')
                    ->where('user_id', Auth::id())
                    ->where('responsabile', true);
            })
            ->Where(function ($query) use ($statoBy) {
                if ($statoBy)
                    $query->Where('tasks.stato', $statoBy);
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);
        return response()->json($objs);
    }

    public function listaAggiornati(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $statoBy = $request->get('stato');

        if (empty($sortByName)) {
            $sortByName = 'codice';
            $orderBy = 'desc';
        }

        $objs = Task::select('tasks.*', 'users.full_name')
            ->whereNull('padre')
            ->join('users', 'tasks.utente_id', 'users.id')
            ->where('tasks.stato', '<>', 3)
            ->Where(function ($query) use ($statoBy) {
                if ($statoBy)
                    $query->Where('tasks.stato', $statoBy);
            })
            ->whereIn('tasks.id', function ($query) {
                $query->select('task_id')->from('task_logs')
                    ->whereDate('tasks.created_at', '>=', date('Y-m-d', strtotime('-5 days')));
            })
            ->whereIn('tasks.id', function ($query) {
                $query->select('tasks.id')->from('tasks')
                    ->join('task_user_assigneds', 'tasks.id', 'task_user_assigneds.task_id')
                    ->join('task_uesr_areas', 'tasks.area_id', 'task_uesr_areas.area_id')
                    ->where('task_user_assigneds.user_id', Auth::id())
                    ->orWhere('task_uesr_areas.user_id', Auth::id())->where('task_uesr_areas.responsabile', 1)
                    ->whereNotNull('responsabile');
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);


        /*       $objs = Task::whereNull('padre')
                   ->select('tasks.*','users.full_name')
                   ->join('users','tasks.utente_id','users.id')
                   ->where('tasks.stato','<>',3)
                   ->whereIn('area_id', function($query) {
                   $query->select('area_id')->from('task_uesr_areas')
                       ->where('user_id',Auth::id())
                       ->whereNotNull('responsabile');
                   })
                   ->whereIn('tasks.id', function($query) {
                       $query->select('task_id')->from('task_logs')
                           ->whereDate('tasks.created_at','>=',date('Y-m-d',strtotime('-3 days')));
                   })
                   ->Where(function ($query) use ($statoBy) {
                       if ($statoBy)
                           $query->Where('tasks.stato', $statoBy);
                   })
                   ->orderBy($sortByName, $orderBy)
                   ->paginate($request->itemsPerPage);
       */
        return response()->json($objs);
    }

    public function mylist(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $statoBy = $request->get('stato');

        if (empty($sortByName)) {
            $sortByName = 'codice';
            $orderBy = 'desc';
        }


        $objs = Task::whereIn('id', function ($query) {
            $query->select('task_id')->from('task_user_assigneds')
                ->where('user_id', Auth::id());
        })
            ->Where(function ($query) use ($statoBy) {
                if ($statoBy)
                    $query->Where('stato', $statoBy);
            })
            ->whereNull('padre')
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function task_log($id)
    {
        $task = Task::find($id);
        if ($task->padre)
            $objs = TaskLog::select('task_logs.*', 'users.full_name')
                ->join('users', 'users.id', 'task_logs.user_id')
                ->where('task_id', $id)
                ->orderby('created_at', 'desc')
                ->get();
        else
            $objs = TaskLog::select('task_logs.*', 'users.full_name', 'tasks.titolo', 'tasks.codice')
                ->join('users', 'users.id', 'task_logs.user_id')
                ->join('tasks', 'task_logs.task_id', 'tasks.id')
                ->whereIn('task_id', function ($query) use ($id) {
                    $query->select('id')
                        ->from('tasks')
                        ->where('padre', $id)
                        ->orWhere('id', $id);
                })
                ->orderby('created_at', 'desc')
                ->get();


        return response()->json($objs);
    }

    public function uploadFile(Request $request, $id)
    {
        $task = DB::table('tasks')->select('path_drive')->where('id', $id)->first();
        $base64Image = $request->file_upload['file'];


        if (!$tmpFileObject = $this->validateBase64($base64Image, ['png', 'jpg', 'jpeg', 'HEIC', 'pdf', 'docx', 'exls', 'xlsx'])) {
            return response()->json([
                'error' => 'Invalid image format.'
            ], 415);
        }

        $tmpFileObjectPathName = $tmpFileObject->getPathname();

        $file = new UploadedFile(
            $tmpFileObjectPathName,
            $tmpFileObject->getFilename(),
            $tmpFileObject->getMimeType(),
            0,
            true
        );
        $filename = $request->file_upload['fileName'];

        GoogleDrive::add_file($task->path_drive, $filename, $file, true, 'google');
        TaskUserNote::nuovaNota($id, $request->nota, $id);
        TaskLog::newTaskLog($id, Auth::id(), 'Upload: ' . $filename, 'primary');


        unlink($tmpFileObjectPathName); // delete temp file
    }

    public function statistiche()
    {
        $taskApertiMese = DB::table('tasks')
            ->whereNull('padre')
            ->whereYear('created_at', Date('Y'))
            ->whereMonth('created_at', Date('m'))
            ->whereIn('area_id', function ($query) {
                $query->select('area_id')->from('task_uesr_areas')
                    ->where('user_id', Auth::id())
                    ->where('responsabile', true);
            })
            ->count();

        $taskChiusiMese = DB::table('tasks')
            ->whereNull('padre')
            ->where('stato', 2)
            ->whereYear('created_at', Date('Y'))
            ->whereMonth('created_at', Date('m'))
            ->whereIn('area_id', function ($query) {
                $query->select('area_id')->from('task_uesr_areas')
                    ->where('user_id', Auth::id())
                    ->where('responsabile', true);
            })
            ->count();

        $taskSospesi = DB::table('tasks')
            ->whereNull('padre')
            ->where('stato', 4)
            ->whereIn('area_id', function ($query) {
                $query->select('area_id')->from('task_uesr_areas')
                    ->where('user_id', Auth::id())
                    ->where('responsabile', true);
            })
            ->count();

        $taskLavorazione = DB::table('tasks')
            ->whereNull('padre')
            ->where('stato', 5)
            ->whereIn('area_id', function ($query) {
                $query->select('area_id')->from('task_uesr_areas')
                    ->where('user_id', Auth::id())
                    ->where('responsabile', true);
            })
            ->count();

        $taskAperti = DB::table('tasks')
            ->whereNull('padre')
            ->where('stato', 1)
            ->whereIn('area_id', function ($query) {
                $query->select('area_id')->from('task_uesr_areas')
                    ->where('user_id', Auth::id())
                    ->where('responsabile', true);
            })
            ->count();

        $taskAssegnati = DB::table('task_user_assigneds')
            ->join('tasks', 'task_user_assigneds.task_id', 'tasks.id')
            ->where('user_id', Auth::id())
            ->where('tasks.stato', '<>', 2)
            ->count();

        return response()->json(['taskAperti' => $taskAperti, 'taskApertiMese' => $taskApertiMese,
            'taskChiusiMese' => $taskChiusiMese, 'taskSospesi' => $taskSospesi,
            'taskLavorazione' => $taskLavorazione, 'taskAssegnati' => $taskAssegnati
        ]);

    }

    public function user($id)
    {
        $user = [];
        if($id != '0000')
            $user = DB::table('task_uesr_areas')
                ->where('area_id', $id)
                ->where('user_id', Auth::id())
                ->first();

        return response()->json($user);
    }

    public function notaScadenza(Request $request, $id)
    {
        $task = Task::find($id);
        $task->data_scadenza = Date('Y-m-d', strtotime('+' . $task->giorni_dopo_scadenza . ' Days'));
        $task->save();

        TaskUserNote::nuovaNota($id, $request->nota, $id);
        TaskLog::newTaskLog($id, Auth::id(), 'Aggiornata Data di Scadenza al: ' . $task->data_scadenza, 'warning');

        $message = 'Messaggi.Scadenza-Modificata';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => null
            ]
        );
    }

    private function validateBase64(string $base64data, array $allowedMimeTypes)
    {
        // strip out data URI scheme information (see RFC 2397)
        if (str_contains($base64data, ';base64')) {
            list(, $base64data) = explode(';', $base64data);
            list(, $base64data) = explode(',', $base64data);
        }

        // strict mode filters for non-base64 alphabet characters
        if (base64_decode($base64data, true) === false) {
            return false;
        }

        // decoding and then re-encoding should not change the data
        if (base64_encode(base64_decode($base64data)) !== $base64data) {
            return false;
        }

        $fileBinaryData = base64_decode($base64data);

        // temporarily store the decoded data on the filesystem to be able to use it later on
        $tmpFileName = tempnam(sys_get_temp_dir(), 'medialibrary');
        file_put_contents($tmpFileName, $fileBinaryData);

        $tmpFileObject = new File($tmpFileName);

        // guard against invalid mime types
        $allowedMimeTypes = Arr::flatten($allowedMimeTypes);

        // if there are no allowed mime types, then any type should be ok
        if (empty($allowedMimeTypes)) {
            return $tmpFileObject;
        }

        // Check the mime types
        $validation = Validator::make(
            ['file' => $tmpFileObject],
            ['file' => 'mimes:' . implode(',', $allowedMimeTypes)]
        );

        if ($validation->fails()) {
            return false;
        }

        return $tmpFileObject;
    }
}
