<?php

namespace App\Http\Controllers;

use App\Models\Target;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TargetController extends Controller
{
    public function store($items, $tipo, $data)
    {
        foreach ($items as $item){
            $obj = Target::where('data_riferimento',$data)->where('tipo',$tipo)->where('titolo',$item['titolo'])->first();
            if(empty($obj->id))
                $obj = new Target();

            $obj->titolo = $item['titolo'];
            $obj->tipo = $tipo;
            $obj->target = $item['target'];
            $obj->data_riferimento = $data;
            $obj->id_riferimento = $item['id'];
            $obj->user = Auth::id();
            $obj->save();
        }
    }

    public function update($items, $tipo, $data){
        $objs = Target::where('data_riferimento',$data)->where('tipo',$tipo)->get();
        foreach ($objs as $obj){
            if(!empty($items[$obj->titolo])){

                $obj->valore = $items[$obj->titolo];
                $obj->save();
            }

        }
    }

    public function list(Request $request, $id)
    {

        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');


        if (empty($sortByName)) {
            $sortByName = 'data_riferimento';
            $orderBy = 'desc';
        }
        $objs = DB::table('targets')
            ->where('tipo',$id)
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);
        Log::channel('stderr')->info($objs);
        return response()->json($objs);
    }

    public function save(Request $request)
    {
        $obj = new Target();
        $obj->titolo = $request->titolo;
        $obj->tipo =$request->modulo;
        $obj->target = $request->target;
        $obj->data_riferimento = $request->anno.'-'.$request->mese.'-01';
        $obj->user = Auth::id();
        $obj->save();

        $message = 'Messaggi.Target-Aggiunto';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function edit(Request $request, $id)
    {

        $obj = Target::find($id);
        $obj->titolo = $request->titolo;
        $obj->tipo =$request->modulo;
        $obj->target = $request->target;
        $obj->data_riferimento = $request->anno.'-'.$request->mese.'-01';
        $obj->user = Auth::id();
        $obj->save();

        $message = 'Messaggi.Target-Modificato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }
}
