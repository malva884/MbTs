<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SystemNotificationController extends Controller
{
    public function index(Request $request){

        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $userBy = $request->get('user');
        $notificaBy = $request->get('notifica');


        if(empty($sortByName)){
            $sortByName = 'created_at';
            $orderBy = 'asc';
        }
        $objs = DB::table('system_notifications')
            ->select('system_notifications.*')
            ->leftJoin('users','users.id','system_notifications.user')
            ->Where(function ($query) use ($userBy) {
                if ($userBy)
                    $query->Where('users.full_name', 'LIKE','%'.$userBy.'%')
                        ->orWhere('system_notifications.nome', 'LIKE','%'.$userBy.'%');
            })
            ->Where(function ($query) use ($notificaBy) {
                if ($notificaBy)
                    $query->Where('notifica', $notificaBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function store(Request $request)
    {

        if(is_array($request->users) && count($request->users)){
            foreach ($request->users as $user){
                foreach ($request->notifiche as $notifica){
                    $obj = new SystemNotification();
                    $obj_user = User::find($user);
                    $obj->user = $obj_user->id;
                    $obj->nome = $obj_user->full_name;
                    $obj->email = $obj_user->email;
                    $obj->notifica = $notifica;
                    $obj->attivo = ($request->attivo ? true:null);
                    $check = DB::table('system_notifications')
                        ->where('notifica',$notifica)
                        ->where('email', $obj->email)
                        ->first();
                    if(empty($check->id))
                        $obj->save();
                }
            }
        }
        else{
            foreach ($request->notifiche as $notifica){
                $obj = new SystemNotification();
                $obj->nome = $request->nome;
                $obj->email = $request->email;
                $obj->notifica = $notifica;
                $obj->attivo = ($request->attivo ? true:null);
                $check = DB::table('system_notifications')
                    ->where('notifica',$notifica)
                    ->where('email', $obj->email)
                    ->first();
                if(empty($check->id))
                    $obj->save();
            }

        }

        $message = 'Messaggi.Notifica-Utente-Aggiunta';
        $color = 'success';


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
        $check = null;
        $obj = SystemNotification::find($id);
        $notifica_old = $obj->notifica;
        $obj->notifica = $request->notifica;
        $obj->attivo = ($request->attivo ? true:false);

        if($obj->notifica != $notifica_old)
            $check = DB::table('system_notifications')
                ->where('notifica',$request->notifica)
                ->where('email', $obj->email)
                ->first();

        if(is_null($check)){
            $obj->save();
            $message = 'Messaggi.Notifica-Utente-Modificata';
            $color = 'success';
        }
        else{
            $message = 'Messaggi.Notifica-Utente-Presente';
            $color = 'error';
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

    public function deleted($id)
    {
        $obj = SystemNotification::find($id);
        $obj->delete();
        $message = 'Messaggi.Notifica-Utente-Eliminata';
        $color = 'success';
        $success = true;

        $text ='
        <h6 class="font-weight-medium text-sm">Notifica Utente: '.$obj->notifica.' Utente: '.$obj->nome.'</h6>';
        LogActivity::addToLog('Notifica Utente Eliminato', ['text'=>$text],'error','deleted');
        return response()->json(
            [
                'success' => $success,
                'message' => $message ,
                'color' => $color,
            ]
        );

    }

    public function get_notifiche()
    {
        return response()->json(SystemNotification::$notifiche);
    }
}
