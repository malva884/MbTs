<?php

namespace App\Http\Controllers;

use App\Models\RpRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RpRegisterController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $visitatore = $request->get('visitatore');
        $azienda = $request->get('azienda');

        if(empty($sortByName)){
            $sortByName = 'data_prevista';
            $orderBy = 'desc';
        }
        $objs = DB::table('rp_registers')->select('rp_registers.*','users.full_name')
            ->join('users','users.id','rp_register_logs.user')
            ->Where(function ($query) use ($visitatore) {
                $query->where('rp_registers.nome','LIKE', '%'.$visitatore.'%');
            })
            ->Where(function ($query) use ($azienda) {
                $query->where('rp_registers.azienda','LIKE', '%'.$azienda.'%');
            })

            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function stored(Request $request)
    {
        try {
            $obj = DB::table('rp_registers')->select('id')
                ->where('email',  strtolower($request['email']))
                ->first();

            if(empty($obj->id)){
                $message ='';
                $color = '';
                $obj = new RpRegister();
                $obj->user = (!empty(Auth::id()) ? Auth::id():5);
                $obj->nome = ucwords(strtolower($request['nome']));
                $obj->email = strtolower($request['email']);
                $obj->azienda = ucwords(strtolower($request['azienda']));
                $obj->save();

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
}
