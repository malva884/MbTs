<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use App\Models\ToCableStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ToCableStructureController extends Controller
{
    public function store(Request $request, $id)
    {

        $rows = ToCableStructure::where('cavo_id', $id)->orderby('posizione', 'asc')->get();

        $obj = new ToCableStructure();
        $obj->cavo_id = $id;
        $obj->centro = $request->centro;
        $obj->materiale = $request->materiale;
        $obj->descrizione = $request->descrizione;
        $obj->diametro = $request->diametro;
        $obj->peso = $request->peso;
        $obj->ordinata = $request->ordinata;
        $obj->elementi = $request->elementi;
        $obj->posizione = $request->posizione;
        $obj->nota = $request->nota;
        $obj->save();

        $i = 1;
        foreach ($rows as $row) {
            if ($row->posizione == $request->posizione) {
                $i++;
                $row->posizione = $i;
            } else {
                $row->posizione = $i;
            }
            $row->save();
            $i++;
        }

        $message = 'Messaggi.Elemento-Aggiunto';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function update(Request $request)
    {
        $rows = ToCableStructure::where('cavo_id', $request->cavo_id)->orderby('posizione', 'asc')->where('id', '<>', $request->id)->get();

        $obj = ToCableStructure::find($request->id);
        $obj->centro = $request->centro;
        $obj->materiale = $request->materiale;
        $obj->descrizione = $request->descrizione;
        $obj->diametro = $request->diametro;
        $obj->peso = $request->peso;
        $obj->ordinata = $request->ordinata;
        $obj->elementi = $request->elementi;
        $obj->nota = $request->nota;

        $i = 1;
        foreach ($rows as $row) {
            if ($request->posizione < $obj->posizione) {
                if ($row->posizione == $request->posizione) {
                    $i = $row->posizione = $i + 1;
                    $i++;
                } else {
                    $row->posizione = $i;
                    $i++;
                }
            } elseif ($request->posizione > $obj->posizione) {
                if($i == $request->posizione)
                    $i++;

                if ($row->posizione == $request->posizione) {

                    $i = $row->posizione = $i;

                    $i++;
                } else {
                    $row->posizione = $i;
                    $i++;
                }
            }
            $row->save();

        }
        $obj->posizione = $request->posizione;

        $obj->save();

        $message = 'Messaggi.Elemento-Modificato';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function deleted(Request $request, $id)
    {
        $obj = ToCableStructure::find($request->id);
        $obj->delete();

        $rows = ToCableStructure::where('cavo_id', $id)->orderby('posizione', 'asc')->get();

        $i = 1;
        foreach ($rows as $row) {
            if ($row->posizione == $request->posizione) {
                $i++;
                $row->posizione = $i;
            } else {
                $row->posizione = $i;
            }
            $row->save();
            $i++;
        }

        $message = 'Messaggi.Riga-Eliminata';
        $color = 'success';

        return response()->json(
        [
            'success' => true,
            'message' => $message ,
            'color' => $color,
        ]);
    }
}
