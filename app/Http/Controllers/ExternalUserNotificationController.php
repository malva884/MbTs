<?php

namespace App\Http\Controllers;

use App\Models\ExternalUserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExternalUserNotificationController extends Controller
{
   public function list(Request $request)
   {
       $sortByName = $request->get('sortBy');
       $orderBy = $request->get('orderBy');
       $nomeBy = $request->get('nome');
       $attivo = null;
       if (!empty($request->attivo) && $request->attivo === true)
           $attivo = true;
       elseif (!empty($request->attivo) && $request->attivo === false)
           $attivo = false;

       $objs = DB::table('external_user_notifications')
           ->Where(function ($query) use ($nomeBy) {
               if ($nomeBy)
                   $query->Where('nome','LIKE', '%'.$nomeBy.'%');
           })
           ->Where(function ($query) use ($attivo) {
               if ($attivo)
                   $query->Where('attivo', $attivo);
           })
           ->orderBy($sortByName, $orderBy) //order in descending order
           ->paginate($request->itemsPerPage);

       return response()->json($objs);
   }

   public function stored(Request $request)
   {
       try {
           $obj = new ExternalUserNotification();
           $obj->nome = $request->nome;
           $obj->email = $request->email;
           $obj->attivo = $request->attivo;
           $obj->tipologia_notifica = $request->tipologia_notifica;
           $obj->save();

           return response()->json(
               [
                   'success' => true,
                   'message' => 'Messaggi.Utente-Salvato' ,
                   'color' => 'success',
               ]
           );
       } catch (\Exception $e) {

           return response()->json(
               [
                   'success' => true,
                   'message' => 'Messaggi.Errore-Interno' ,
                   'color' => 'error',
               ]
           );
       }


   }

   public function update(Request $request, $id)
   {
       try {
           $obj = ExternalUserNotification::find($id);
           $obj->nome = $request->nome;
           $obj->email = $request->email;
           $obj->attivo = $request->attivo;
           $obj->tipologia_notifica = $request->tipologia_notifica;
           $obj->save();

           return response()->json(
               [
                   'success' => true,
                   'message' => 'Messaggi.Modifiche-Salvate' ,
                   'color' => 'success',
               ]
           );
       } catch (\Exception $e) {

           return response()->json(
               [
                   'success' => true,
                   'message' => 'Messaggi.Errore-Interno' ,
                   'color' => 'error',
               ]
           );
       }
   }
}
