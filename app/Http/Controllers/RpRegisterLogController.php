<?php

namespace App\Http\Controllers;

use App\Jobs\RegisterNotifiche;
use App\Models\RegistroAccountWifi;
use App\Models\RpRegisterActivity;
use App\Models\RpRegisterLog;
use App\Models\RpRegisterNotification;
use App\Models\User;
use App\Models\Utility;
use App\Print\TemplateZpl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RpRegisterLogController extends Controller
{
    public function list(Request $request){
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $visitatore = $request->get('visitatore');
        $azienda = $request->get('azienda');
        $data = $request->get('data');

        if(empty($sortByName)){
            $sortByName = 'data_prevista';
            $orderBy = 'desc';
        }
        $objs = DB::table('rp_register_logs')->select('rp_register_logs.*','users.full_name')
            ->join('users','users.id','rp_register_logs.user')
            ->Where(function ($query) use ($visitatore) {
                $query->where('rp_register_logs.nome','LIKE', '%'.$visitatore.'%');
            })
            ->Where(function ($query) use ($azienda) {
                $query->where('rp_register_logs.azienda','LIKE', '%'.$azienda.'%');
            })

            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }
    public function getRegister(Request $request){
        $success = false;
        $codice = $request->code;
        $azione = '';

        // cerco il codice QR-CODE o in cod_riferimento o cod_tessera
        $obj = DB::table('rp_register_logs')->select('*')
            ->where('data_scadenza','>', date('Y-m-d H:i:s'))
            ->where('attivo', 1)
            ->Where(function ($query) use ($codice) {
                $query->where('cod_riferimento',$codice)->orWhere('cod_tessera',$codice);
            })
            ->first();

        if(!empty($obj->id)){
            $success = true;
            $log = DB::table('rp_register_activities')
                ->where('rp_register_id',$obj->id)
                ->where('presente',True)
                ->whereDate('data_azione',date('Y-m-d'))
                ->orderBy('data_azione', 'desc')
                ->first();

            $azione = 'Entrata';
            if(!empty($log->azione) && $log->azione == 'Entrata'){
                $azione = 'Uscita';
                $dataActivity = [
                    'id' => $obj->id,
                    'cod_riferimento' => $codice,
                    'entrata' => false,
                ];
                $object = (object) $dataActivity;
                RpRegisterActivity::store($object);
            }
        }

        return response()->json(
            [
                'success' => $success,
                'azione' => $azione,
                'obj' => $obj
            ]
        );

    }
    public function storeRegister(Request $request){

        $success = true;
        $request->entrata = true;

        RpRegisterActivity::store($request);

        return response()->json(
            [
                'success' => $success,
                'code' =>  $request->cod_tessera
            ]
        );

    }

    public function store(Request $request){

        try {
            $message ='';
            $color = '';
            if(empty($request['id'])){
                $obj = new RpRegisterLog();
                $obj->cod_riferimento = Str::uuid();
            }
            else{
                $obj = RpRegisterLog::find($request['id']);

                DB::table('rp_register_notifications')
                    ->where('register_id', $request['id'])
                    ->delete();
            }

            $obj->user = (!empty(Auth::id()) ? Auth::id():5);
            $obj->nome = ucwords(strtolower($request['nome']));
            $obj->email = strtolower($request['email']);
            $obj->azienda = ucwords(strtolower($request['azienda']));
            $obj->data_prevista = $request->get('data_prevista').' 07:00:00';
            $obj->data_scadenza = $request->get('data_scadenza').' 23:59:59';
            $obj->wifi = ($request['wifi'] == 'true' ? true:false );
            $obj->informativa = ($request['informativa'] == 'true' ? true:false );
            if($obj->wifi){
                $username = explode("@", $obj->email);
                $obj->username_wifi = $username[0];
                $obj->password_wifi = Str::password(8, true, true, false, false);
            }

            $obj->save();
            // se è richiesto l'account wifi creo il registo wifi i dati del visitatore.
            if($obj->wifi)
                RegistroAccountWifi::create($obj->nome, $obj->email, $obj->username_wifi, $obj->password_wifi, $obj->azienda,  $obj->data_prevista, $obj->data_scadenza, $obj->user, $obj->id);

            if(!is_array($request['user_interni']))
                $request['user_interni'] = [$request['user_interni']];
            foreach ($request['user_interni'] as $user){
                // creo gli unteti da avvisare all'arivo del visitatore
                $user = User::all()->where('email',$user)->first();
                $userIntero = new RpRegisterNotification();
                $userIntero->user = $user->id;
                $userIntero->register_id = $obj->id;
                $userIntero->cod_riferimento = $obj->cod_riferimento;
                $userIntero->save();
            }

            if(!empty(Auth::id())){
                // metto in coda l'inivio della notifica email al visitatore
                RegisterNotifiche::dispatch();
                $message = 'Messaggi.Nuovo-Visitatore-Inserito';
                $color = 'success';
            }
            else{
                $obj->notifica_inviata = true;
                $obj->save();
                $request->cod_riferimento = $obj->cod_riferimento;
                $request->cod_tessera = $obj->cod_riferimento;
                $request->id = $obj->id;
                RpRegisterActivity::store($request);
            }
        }catch (\Exception $e){
            $message = 'Messaggi.Errore-Salvataggio';
            $color = 'error';

        }

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => $color,
            ]
        );
    }

    public function update(Request $request, $id)
    {
        $obj = RpRegisterLog::find($id);
        if( $obj->data_prevista != $request['data_prevista'].' 07:00:00' || $obj->data_scadenza != $request['data_scadenza'].' 23:59:59'){
            $obj->data_prevista = $request['data_prevista'].' 07:00:00';
            $obj->data_scadenza = $request['data_scadenza'].' 23:59:59';

            if($request->wifi == 'true' && $obj->wifi == 1)
                RegistroAccountWifi::edit($obj->data_prevista, $obj->data_scadenza, $obj->id);
            elseif($request->wifi == 'true' && $obj->wifi == 0){
                $username = explode("@", $obj->email);
                $obj->username_wifi = $username[0];
                $obj->password_wifi = Str::password(8, true, true, false, false);
                $obj->wifi = true;
                RegistroAccountWifi::create($obj->nome, $obj->email, $obj->username_wifi, $obj->password_wifi, $obj->azienda,  $obj->data_prevista, $obj->data_scadenza, $obj->user, $obj->id);

            }
        }elseif($request->wifi == 'true' && $obj->wifi == 0){
            $username = explode("@", $obj->email);
            $obj->username_wifi = $username[0];
            $obj->password_wifi = Str::password(8, true, true, false, false);
            $obj->wifi = true;
            RegistroAccountWifi::create($obj->nome, $obj->email, $obj->username_wifi, $obj->password_wifi, $obj->azienda,  $obj->data_prevista, $obj->data_scadenza, $obj->user, $obj->id);
        }

        $obj->save();

        return response()->json(
            [
                'success' => true,
                'message' => 'Messaggi.Salvataggio',
                'color' => 'success',
            ]
        );
    }

    // api per rinoltrare il qr-code al visitatire
    public function send($id)
    {
        $message = 'Messaggi.Errore-Invio-Notifica';
        $color = 'error';
        $obj = RpRegisterLog::find($id);
        if(!empty($obj->id)){
            $obj->notifica_inviata = false;
            $obj->save();
            // metto in coda l'inivio della notifica email al visitatore
            RegisterNotifiche::dispatch();
            $message = 'Messaggi.Notifica-Inviata';
            $color = 'success';
        }


        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => $color,
            ]
        );

    }

    public function getReferenti()
    {
        $users = Utility::users_notify(['referenti_visitatori'],true);

        return response()->json($users);
    }

    public function auth_setting(Request $request)
    {
        $password = $request->password;
        $users = DB::table('users')->select('*')
            ->whereIN('role',  ['super admin','admin'])
            ->get();

        foreach ($users as $user){
            if (Hash::check($password, $user->password))
                return response()->json(['success'=>true, 'message' => '']);

        }
        return response()->json(['success'=>false, 'message' => 'Password-Error']);
    }

    public function totemList()
    {
        $objs = DB::table('rp_totems')->select('*')
            ->get();

        return response()->json(['success'=>true, 'totem' => $objs]);
    }

    public function searchVisitor(Request $request)
    {

        $obj = DB::table('rp_register_logs')->select('id','nome','azienda')->where('email',  $request->email)->first();

        return response()->json($obj);
    }

    public function printer($id)
    {
        $obj = DB::table('rp_register_logs')->where('id',  $id)->first();
        $print = DB::table('rp_totems')->where('nome',  'Reception')->first();
        $success = false;
        $message = 'Errore Stampa';
        if(!empty($obj->id) && !empty($print->id)){
            $info = [
                'Id' => (empty($obj->cod_tessera) ? $obj->cod_riferimento : $obj->cod_tessera),
                'Visitatore' => $obj->nome,
                'Azienda' => $obj->azienda,
                'Username' => $obj->username_wifi,
                'Password' => $obj->password_wifi,
                'Scadenza' => $obj->data_scadenza,
                'Ip_Printer' => $print->ip_stampante,
            ];
            TemplateZpl::printReception($info);
            $success = true;
            $message = 'Stampa Inviata';
        }

        return response()->json(['success'=>$success, 'message' => $message]);

    }
}
