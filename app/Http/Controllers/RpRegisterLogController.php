<?php

namespace App\Http\Controllers;

use App\Jobs\RegisterNotifiche;
use App\Models\RegistroAccountWifi;
use App\Models\RpRegisterActivity;
use App\Models\RpRegisterLog;
use App\Models\RpRegisterNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
    public function getRegister($id){
        $success = false;

        $obj = DB::table('rp_register_logs')->select('*')
            ->where('data_scadenza','>', date('Y-m-d H:i:s'))
            ->where('attivo', 1)
            ->Where(function ($query) use ($id) {
                $query->where('cod_riferimento',$id)->orWhere('cod_tessera',$id);
            })
            ->first();


        if(!empty($obj->id))
            $success = true;

        return response()->json(
            [
                'success' => $success,
                'obj' => $obj
            ]
        );

        //Log::channel('stderr')->info(Str::uuid());

    }
    public function storeRegister(Request $request,$id){

        $success = true;
        $obj = new RpRegisterActivity();
        $obj->rp_register_id = $id;
        $obj->cod_riferimento = $request->cod_riferimento;
        $obj->data_azione = date('Y-m-d H:i:s');
        $obj->azione = ($request->entrata == true ? 'Entrata':'Uscita');
        $obj->save();
        $code = '';
        if($request->entrata == true && !$request->cod_tessera){
            $registerLog =  RpRegisterLog::find($request->id);
            $registerLog->cod_tessera = Str::uuid();
            $registerLog->save();
            RpRegisterLog::inviaNotifica($registerLog->id);
            $code = $registerLog->cod_tessera;
        }elseif($request->entrata == true && $request->cod_tessera){
            $code = $request->cod_tessera;
        }

        return response()->json(
            [
                'success' => $success,
                'code' => $code
            ]
        );

    }

    public function store(Request $request){


        try {
            $obj = new RpRegisterLog();
            $obj->cod_riferimento = Str::uuid();
            $obj->user = Auth::id();
            $obj->nome = ucwords(strtolower($request['nome']));
            $obj->email = strtolower($request['email']);
            $obj->azienda = ucwords(strtolower($request['azienda']));
            $obj->data_prevista = $request->get('data_prevista');
            $obj->data_scadenza = $request->get('data_scadenza');
            $obj->wifi = ($request['wifi'] == 'true' ? true:false );
            if($obj->wifi){
                $username = explode("@", $obj->email);
                $obj->username_wifi = $username[0];
                $obj->password_wifi = Str::password(8, true, true, false, false);
            }
            $obj->save();
            if($obj->wifi)
                RegistroAccountWifi::create($obj->nome, $obj->email, $obj->username_wifi, $obj->password_wifi, $obj->azienda,  $obj->data_prevista, $obj->data_scadenza, $obj->user, $obj->id);
            foreach ($request['user_interni'] as $user){
                $user = User::all()->where('email',$user)->first();
                $userIntero = new RpRegisterNotification();
                $userIntero->user = $user->id;
                $userIntero->register_id = $obj->id;
                $userIntero->cod_riferimento = $obj->cod_riferimento;
                $userIntero->save();
            }

            RegisterNotifiche::dispatch();
            $message = 'Messaggi.Nuovo-Visitatore-Inserito';
            $color = 'success';
        }catch (Exception $e){
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

    public function send($id)
    {
        $message = 'Messaggi.Errore-Invio-Notifica';
        $color = 'error';
        $obj = RpRegisterLog::find($id);
        if(!empty($obj->id)){
            $obj->notifica_inviata = false;
            $obj->save();
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
}
