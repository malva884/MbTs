<?php

namespace App\Http\Controllers;

use App\Jobs\RatingFornitore;
use App\Models\LogActivitySupllier;
use App\Models\QtSupplier;
use App\Models\QtSupplierNotice;
use App\Models\QtSupplierUser;
use App\Services\GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QtSupplierController
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $ragioneSocialeBy = $request->get('ragioneSociale');
        $cdSapBy = $request->get('cdSap');
        $categoriaBy = $request->get('categoria');

        if (empty($sortByName)) {
            $sortByName = 'ragioneSociale';
            $orderBy = 'asc';
        }

        $objs = DB::connection('sqlsrv_fornitori')->table('suppliers')
            ->select('suppliers.*')
            ->Where(function ($query) use ($ragioneSocialeBy) {
                if ($ragioneSocialeBy)
                    $query->Where('ragioneSociale', 'LIKE', '%' . $ragioneSocialeBy . '%');
            })
            ->Where(function ($query) use ($categoriaBy) {
                if ($categoriaBy)
                    $query->Where('categoria', $categoriaBy);
            })
            ->Where(function ($query) use ($cdSapBy) {
                if ($cdSapBy)
                    $query->Where('codiceSap',$cdSapBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function rating(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $ragioneSocialeBy = $request->get('ragioneSociale');
        $categoriaBy = $request->get('categoria');
        $cdSapBy = $request->get('cdSap');

        if (empty($sortByName)) {
            $sortByName = 'ragioneSociale';
            $orderBy = 'asc';
        }

        $certificazioni = DB::connection('sqlsrv_fornitori')->table('certifications')->get();

        $objs = DB::connection('sqlsrv_fornitori')->table('suppliers')
            ->Where(function ($query) use ($ragioneSocialeBy) {
                if ($ragioneSocialeBy)
                    $query->Where('ragioneSociale', 'LIKE', '%' . $ragioneSocialeBy . '%');
            })
            ->Where(function ($query) use ($categoriaBy) {
                if ($categoriaBy)
                    $query->Where('categoria', $categoriaBy);
            })
            ->Where(function ($query) use ($cdSapBy) {
                if ($cdSapBy)
                    $query->Where('codiceSap',$cdSapBy);
            });

        $objs = $objs->addSelect('suppliers.*');
        foreach($certificazioni as $certificazione){
            $objs = $objs->addSelect(DB::raw("(SELECT CASE
                                WHEN a.approvato = 1 THEN CONCAT(a.livello, ' ( ', a.scadenza,' )')
                                WHEN a.approvato = 0 THEN '0'
                                ELSE 'N'
                                END AS valutazione FROM supplier_certifications as a
                                left Join certifications as b on a.certificato_id = b.id
                                WHERE a.fornitore_id = suppliers.id
                                AND b.id = '".$certificazione->id."') as '".$certificazione->id."' "));
        }

        $data =  $objs->orderBy($sortByName, $orderBy)
        ->paginate($request->itemsPerPage);



        return response()->json(['list'=> $data, 'certificazioni' => $certificazioni]);
    }

    public function view($id)
    {
        $obj = QtSupplier::find($id);

        return response()->json($obj);
    }

    public function stored(Request $request)
    {
        $obj = new QtSupplier();
        $obj->ragioneSociale = ucfirst(strtolower($request->ragioneSociale));
        $obj->email = ucfirst($request->email);
        $obj->nazione = strtoupper($request->nazione);
        $obj->indirizzo = ucfirst($request->indirizzo);
        $obj->cap = $request->cap;
        $obj->citta = ucfirst(strtolower($request->citta));
        $obj->codiceSap = $request->codiceSap;
        $obj->categoria = strtoupper($request->categoria);
        $obj->qualificato = ($request->qualificato ? true:false);
        $obj->prezzo = $request->prezzo;
        $obj->servizio = strtoupper($request->servizio);
        $obj->critico = ($request->critico ? true:false);
        $obj->folderID = '111';//GoogleDrive::add_folder([env('ID_GOOGLE_FORNITORI')], $obj->ragioneSociale . ' ( ' . $obj->codiceSap.' )', 'google', true);
        $obj->save();

        $message = 'Messaggi.Fornitore-Aggiunto';

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
        $obj = QtSupplier::find($id);
        //$obj->ragioneSociale = ucfirst(strtolower($request->ragioneSociale));
        $obj->email = ucfirst($request->email);
        $obj->nazione = strtoupper($request->nazione);
        $obj->indirizzo = ucfirst($request->indirizzo);
        $obj->cap = $request->cap;
        $obj->citta = ucfirst(strtolower($request->citta));
        //$obj->codiceSap = $request->codiceSap;
        $obj->categoria = strtoupper($request->categoria);
        $obj->qualificato = ($request->qualificato ? true:false);
        $obj->prezzo = $request->prezzo;
        $obj->servizio = strtoupper($request->servizio);
        $obj->critico = ($request->critico ? true:false);
        $obj->save();

        if(!empty($obj->prezzo) && !empty($obj->servizio) && !empty($obj->qualificato))
            RatingFornitore::dispatch($id);
        $message = 'Messaggi.Fornitore-Modificato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function users(Request $request, $id)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $nomeBy = $request->get('nome');
        $emailBy = $request->get('email');

        if (empty($sortByName)) {
            $sortByName = 'nome';
            $orderBy = 'asc';
        }

        $objs = DB::connection('sqlsrv_fornitori')->table('users')
            ->select('users.*')
            ->where('supplier_id',$id)
            ->Where(function ($query) use ($nomeBy) {
                if ($nomeBy)
                    $query->Where('nome', 'LIKE', '%' . $nomeBy . '%');
            })
            ->Where(function ($query) use ($emailBy) {
                if ($emailBy)
                    $query->Where('email', 'LIKE', '%' . $emailBy . '%');
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function new_user(Request $request, $id)
    {
        $obj = new QtSupplierUser();
        $obj->nome = ucwords(strtolower($request->nome));
        $obj->supplier_id = $id;
        $obj->email = strtolower($request->email);
        $obj->disattivo = ($request->disattivo ? true:false);
        $obj->save();

        $message = 'Messaggi.Utente-Salvato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function update_user(Request $request, $id, $uid)
    {
        $obj = QtSupplierUser::find($uid);
        $obj->nome = ucwords(strtolower($request->nome));
        $obj->email = strtolower($request->email);
        $obj->disattivo = ($request->disattivo ? true:false);
        $obj->save();

        $message = 'Messaggi.Utente-Modificato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function notice(Request $request, $id)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $nomeBy = $request->get('nome');
        $emailBy = $request->get('email');

        if (empty($sortByName)) {
            $sortByName = 'id';
            $orderBy = 'asc';
        }

        $objs = DB::connection('sqlsrv_fornitori')->table('notice_suppliers')
            ->leftJoin('supplier_certifications','notice_suppliers.certificato_id','supplier_certifications.id')
            ->leftJoin('certifications','supplier_certifications.certificato_id','certifications.id')
            ->select('notice_suppliers.*','certifications.titolo as titolo_certificazione')
            ->where('supplier_id',$id)
            ->Where(function ($query) use ($nomeBy) {
                if ($nomeBy)
                    $query->Where('id', 'LIKE', '%' . $nomeBy . '%');
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function new_notice(Request $request, $id)
    {
        QtSupplierNotice::stored($request, $id);

        $message = 'Messaggi.Avviso-Inviato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
            ]
        );
    }

    public function deleted($id)
    {
        $obj = QtSupplier::find($id);
        $obj->disattivo = true;
        $obj->save();

        DB::connection('sqlsrv_fornitori')->table('users')
            ->where('supplier_id',$id)
            ->update(['disattivo' => true, 'updated_at' => date('Y-m-d h:i:s')]);

        $message = 'Messaggi.Fornitore-Eliminato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
            ]
        );
    }

    public function log($id)
    {
        return response()->json(LogActivitySupllier::where('user_id', $id)->orderBy('id', 'DESC')->take(25)->get());
    }
}
