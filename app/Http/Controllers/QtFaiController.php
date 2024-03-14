<?php

namespace App\Http\Controllers;

use App\Models\QtFai;
use App\Services\GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\QueryBuilder\QueryBuilder;

class QtFaiController extends Controller
{
    public function index(Request $request){

        $sortByName = $request->get('sort');

        $objs = QueryBuilder::for(QtFai::class)
            //->allowedFilters(['ol', AllowedFilter::exact('stage'), AllowedFilter::exact('num_fo')])
            ->defaultSort('created_at')
            ->allowedSorts($sortByName)
            ->paginate($request->get('itemsPerPage'));

        return response()->json($objs);
    }

    public function store(Request $request){

        $lastRecord = QtFai::where('anno',date('Y'))->orderBy('num', 'desc')->first();
        if (empty($lastRecord->num))
            $num = 0;
        else
            $num = $lastRecord->num;

        $folder = GoogleDrive::search(env('ID_GOOGLE_DRIVE_FAI'), 'google', 'dir', date('Y'), true);

        $obj = new QtFai();
        $obj->data_creazione = date('Y-m-d H:i:s');
        $obj->anno = date('Y');
        $obj->num = $num + 1 ;
        $obj->numero_fai = $num + 1 .'-'.Date('Y');
        $obj->user = Auth::id();
        $obj->ol = $request->ol;
        $obj->cod_cavo = $request->cod_cavo;
        $obj->cod_materiale = $request->cod_materiale;
        $obj->descrizione = $request->descrizione;

        $obj->path_drive = GoogleDrive::add_folder($folder,$obj->numero_fai,'google',false);
        $obj->save();

        $data = array(
            'fullname' => $obj->numero_fai,
            'emailaddress' => '',
            'msg' => $obj->descrizione
        );

        Mail::send('emails/openFai', $data, function ($message) use ($data) {
            $message->to('gregorio.grande@stl.tech', "Contact Form");
            $message->subject('Apertura Fai');
        });

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


}
