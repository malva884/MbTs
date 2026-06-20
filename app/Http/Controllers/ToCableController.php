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
            ->where('to_cables.disattivo','<>',1)
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
        //$objs = ToCableStructure::where('cavo_id', $id)->orderby('posizione', 'asc')->get();

        $objs = DB::table('to_cable_structures')
            ->leftJoin('to_center_costs','to_cable_structures.centro','to_center_costs.centro')
            ->leftJoin('to_materials','to_cable_structures.materiale','to_materials.materiale')
            ->select('to_cable_structures.*','to_center_costs.id as centro_check','to_materials.id as matariale_check', DB::raw('IIF(peso = 0.00, 0.22, peso) as  peso_mat'))
            ->where('cavo_id', $id)
            ->orderby('posizione', 'asc')
            ->get();

        $checkCentro = $objs->whereNotNull('centro')->whereNull('centro_check')->first();
        $checkMateriale = $objs->whereNotNull('materiale')->whereNull('matariale_check')->first();

        Log::channel('stderr')->info((array) $checkMateriale);


        return response()->json(['objs' => $objs, 'checkMateriale' => !empty($checkMateriale), 'checkCentro' => !empty($checkCentro)]);
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
