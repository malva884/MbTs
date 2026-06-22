<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function account()
    {
        return response()->json(Auth::user());
    }

    public function update(Request $request)
    {

        $input = $request->except(['nome','cognome']);
        $input['userId'] = $request->id;
        $input['nome'] = ucfirst(strtolower($request->nome));
        $input['cognome'] = ucfirst(strtolower($request->cognome));
        $input['full_name'] = $input['nome'].' '.$input['cognome'];
        User::find(Auth::id())->update($input);

        $message = 'Messaggi.Dati-Salvati.';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
            ]
        );
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();

        if (!Hash::check($request->oldPassword,$user->password)){
            $message = 'Messaggi.Password-Corrente-Non-Valida';
            $success = false;
        }
        else
        {
            $user->password = Hash::make($request->newPassword);
            $user->password_changed_at = date('Y-m-d H:i:s');
            $user->save();
            $message = 'Messaggi.Password-Cambiata-Correttamente';
            $success = true;
        }

        return response()->json(
            [
                'success' => $success,
                'message' => $message ,
            ]
        );

    }
}
