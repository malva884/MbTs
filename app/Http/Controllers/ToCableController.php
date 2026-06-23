<?php

namespace App\Http\Controllers;

use App\Models\ToCable;
use App\Models\ToCableStructure;
use App\Models\ToCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ToCableController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $categoriaBy = $request->get('categoria');
        $normaBy = $request->get('norma');
        $codiceBy = $request->get('cavo');
        $dataBy = $request->get('data');

        if (empty($sortByName)) {
            $sortByName = 'created_at';
            $orderBy = 'desc';
        }

        $objs = ToCable::select('to_cables.*')
            ->leftJoin('to_categories','to_categories.id','to_cables.categoria_id')
			->where('disattivo','<>',1)
            ->Where(function ($query) use ($categoriaBy) {
                if ($categoriaBy)
                    $query->Where('categoria_id', $categoriaBy);
            })
            ->Where(function ($query) use ($normaBy) {
                if ($normaBy)
                    $query->Where('norma','LIKE', '%'.$normaBy.'%');
            })
            ->Where(function ($query) use ($codiceBy) {
                if ($codiceBy)
                    $query->Where('codice', 'LIKE', '%' . $codiceBy . '%')
                        ->orWhere('descrizione', 'LIKE', '%' . $codiceBy . '%')
                        ->orWhere('to_categories.legistrazione', 'LIKE', '%' . $codiceBy . '%');
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function get_list(Request $request)
    {
        $sortByName = 'categoria';
        $orderBy = 'asc';

        $objs = ToCable::select('to_cables.id','codice','to_categories.categoria','descrizione')
            ->join('to_categories', 'to_categories.id', '=', 'to_cables.categoria_id')
			->where('disattivo','<>',true)
            ->orderBy($sortByName, $orderBy)->get();

        return response()->json($objs);
    }

    public function store(Request $request)
    {

        $category = DB::table('to_categories')->select('categoria')->where('id', '=', $request->categoria_id)->first();

        $obj = new ToCable();
        $obj->codice = $request->codice;
        $obj->categoria_id = $request->categoria_id;
        $obj->categoria = $category->categoria;
        $obj->descrizione = $request->descrizione;
		$obj->norma = $request->norma;
        $obj->save();

        $message = '';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function update(Request $request, $id)
    {
        $category = DB::table('to_categories')->select('categoria')->where('id', '=', $request->categoria_id)->first();

        $obj = ToCable::find($id);
        $obj->codice = $request->codice;
        $obj->descrizione = $request->descrizione;
        $obj->categoria_id = $request->categoria_id;
        $obj->categoria = $category->categoria;
		$obj->norma = $request->norma;
        $obj->save();

        $obj->categoria_obj;


        $message = 'Messaggi.Cavo-Modificato';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function view($id)
    {
        $obj = ToCable::find($id);

        $obj->categoria_obj;

        return response()->json($obj);
    }

    public function rows($id)
    {
        $objs = ToCableStructure::where('cavo_id', $id)
            ->with(['center', 'material'])
            ->orderBy('posizione', 'asc')
            ->get()
            ->map(function ($row) {
                $row->centro_missing   = !empty($row->centro)   && is_null($row->center);
                $row->materiale_missing = !empty($row->materiale) && is_null($row->material);
                return $row;
            });

        return response()->json($objs);
    }

    public function duplica(Request $request,$id)
    {
        $obj = ToCable::find($id);
        $objs = ToCableStructure::where('cavo_id', $obj->id)->get();

        $cavo = new ToCable();
        $cavo->codice = $request->codice;
        $cavo->categoria_id = $obj->categoria_id;
        $cavo->categoria = $obj->categoria;
        $cavo->descrizione = $obj->descrizione;
		$cavo->norma = $obj->norma;
        $cavo->save();

        foreach ($objs as $row) {
            $struttura = new ToCableStructure();
            $struttura->cavo_id = $cavo->id;
            $struttura->centro = $row->centro;
            $struttura->materiale = $row->materiale;
            $struttura->descrizione = $row->descrizione;
            $struttura->diametro = $row->diametro;
            $struttura->peso = $row->peso;
            $struttura->ordinata = $row->ordinata;
            $struttura->elementi = $row->elementi;
            $struttura->nota = $row->nota;
            $struttura->posizione = $row->posizione;
            $struttura->save();
        }

        $message = 'Messaggi.Cavo-Duplicato';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
            ]
        );
    }

    public function get_diametro($id){

        $cable_structures = DB::table('to_cable_structures')->select('diametro')->where('cavo_id', '=', $id)->where('centro','COLL')->first();

        return json_encode($cable_structures->diametro);
    }
	
	public function deleted($id)
    {
								Log::info('ID: '.$id);

        $obj = ToCable::find($id);
        $obj->disattivo = true;
        $obj->save();

        $message = 'Messaggi.Cavo-Eliminato';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
            ]
        );
    }
}
