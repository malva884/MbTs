<?php

namespace App\Http\Controllers;

use App\Models\ToClient;
use Illuminate\Http\Request;

class ToClientController extends Controller
{
    public function list(Request $request)
    {


        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $clienteBy = $request->get('cliente');
        $codiceBy = $request->get('codice');
		$statoBy = $request->get('stato');


        if (empty($sortByName)) {
            $sortByName = 'ragione_sociale';
            $orderBy = 'asc';
        }

        $objs = ToClient::Where(function ($query) use ($clienteBy) {
            if ($clienteBy)
                $query->Where('ragione_sociale', 'LIKE', '%'.$clienteBy.'%');
			})
            ->where(function ($query) use ($codiceBy) {
                if ($codiceBy)
                    $query->Where('codice_sap',$codiceBy);
            })
			->where(function ($query) use ($statoBy) {
                if ($statoBy == 1)
                    $query->Where('disabled',$statoBy);
				else
					$query->WhereNull('disabled')->orWhere('disabled',false);
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function get_list(Request $request)
    {
        $sortByName = 'ragione_sociale';
        $orderBy = 'asc';

        $objs = ToClient::orderBy($sortByName, $orderBy)->get();

        return response()->json($objs);
    }

    public function stored(Request $request)
    {
        $obj = new ToClient();
        $obj->ragione_sociale = ucfirst(strtolower($request->ragione_sociale));
        $obj->codice_sap = $request->codice_sap;
		$obj->email = $request->email;
        $obj->telefono = $request->telefono;
        $obj->provincia = $request->provincia;
        $obj->citta = $request->citta;
        $obj->cap = $request->cap;
        $obj->indirizzo = $request->indirizzo;
		$obj->disabled = $request->disabled;
        $obj->save();

        $message = 'Messaggi.Cliente-Aggiunto';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );

    }

    public function update(Request $request, $id)
    {
        $obj = ToClient::find($id);
        $obj->ragione_sociale = ucfirst(strtolower($request->ragione_sociale));
        $obj->codice_sap = $request->codice_sap;
		$obj->email = $request->email;
        $obj->telefono = $request->telefono;
        $obj->provincia = $request->provincia;
        $obj->citta = $request->citta;
        $obj->cap = $request->cap;
        $obj->indirizzo = $request->indirizzo;
		$obj->disabled = $request->disabled;
        $obj->save();

        $message = 'Messaggi.Cliente-Modificato';

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
