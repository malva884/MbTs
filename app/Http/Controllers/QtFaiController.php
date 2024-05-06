<?php

namespace App\Http\Controllers;

use App\Jobs\Fai;
use App\Models\LogActivity;
use App\Models\QtFai;
use App\Services\GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QtFaiController extends Controller
{
    public function index(Request $request){

        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $ordineBy = $request->get('ordine');
        $materialeBy = $request->get('materiale');


        if(empty($sortByName)){
            $sortByName = 'data_creazione';
            $orderBy = 'asc';
        }
        $objs = DB::table('qt_fais')
            ->Where(function ($query) use ($ordineBy) {
                if ($ordineBy)
                    $query->Where('ol', 'LIKE','%'.$ordineBy.'%');
            })
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('cod_materiale', 'LIKE','%'.$materialeBy.'%');
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function store(Request $request){
        // Recupero l'ultimo Fai inserito
        $lastRecord = QtFai::where('anno',date('Y'))->orderBy('num', 'desc')->first();
        // controllo che se il primo fai dell'anno o recupero l'unito numero
        if (empty($lastRecord->num))
            $num = 0;
        else
            $num = $lastRecord->num;
        // cerco la cartella drive dell'anno corrente altrimenti la creo.
        $folder = GoogleDrive::search(env('ID_GOOGLE_DRIVE_FAI'), 'google', 'dir', date('Y'), true);

        $obj = new QtFai();
        $obj->data_creazione = date('Y-m-d H:i:s');
        $obj->anno = date('Y');
        $obj->num = $num + 1 ;  // incermoento il numero fai
        $obj->numero_fai = $num + 1 .'-'.Date('Y');
        $obj->user = Auth::id();
        $obj->ol = $request->ol;
        $obj->cod_cavo = $request->cod_cavo;
        $obj->cod_materiale = $request->cod_materiale;
        $obj->descrizione = $request->descrizione;
        // Creo La cartella del fai su  Drive
        $obj->path_drive = GoogleDrive::add_folder($folder,'FAI_'.$obj->numero_fai,'google',true);
        $obj->save();
        // metto in coda l'inivio della notifica email
        dispatch(new Fai($obj->id,'Apertura Fai'));

        $message = 'Messaggi.Nuovo-Fai-Salvato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function closed(Request $request, $id){

        if(!empty($request->rusultato)){
            $obj = QtFai::find($id);
            $obj->risultato = $request->rusultato;
            $obj->data_chiusura = date('Y-m-d H:i:s');
            $obj->save();
            // metto in coda l'inivio della notifica email
            dispatch(new Fai($obj->id,'Chiusura Fai'));
        }

        return response()->json(
            [
                'success' => true,
                'message' => 'Messaggi.Fai-Chiuso' ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function deleted($id){

        $obj = QtFai::find($id);
        $message = 'Messaggi.Errore-Eliminazione-Fai';
        $color = 'error';
        $success = false;
        if(!empty($obj->id)){
            $path_drive = $obj->path_drive;
            $numero_fai = $obj->numero_fai;
            // elimino il fai
            if($obj->delete()){
                // Rinomino La Cartella Driver Aggiungendo (ELIMINATO)
                GoogleDrive::rename_dir($path_drive, $numero_fai.' ( ELIMINATO )');
                $message = 'Messaggi.Fai-Eliminato';
                $color = 'success';
                $success = true;
            }
        }
        // Aggiungo l'attività effettuata.
        LogActivity::addToLog('Fai Eliminato', ['text'=>'Fai Numero: '.$obj->numero_fai],'error','deleted');
        return response()->json(
            [
                'success' => $success,
                'message' => $message ,
                'color' => $color,
            ]
        );
    }

    public function get_fai()
    {
        $objs = DB::table('qt_fais')
            ->whereNull('risultato')
            ->get();

        return response()->json($objs);
    }


}
