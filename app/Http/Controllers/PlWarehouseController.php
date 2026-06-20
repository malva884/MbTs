<?php

namespace App\Http\Controllers;

use App\Imports\PlAssetImport;
use App\Jobs\ControlloQuantitaMagazzino;
use App\Jobs\FatturatoEmailMensile;
use App\Models\PlAsset;
use App\Models\PlWarehouse;
use App\Models\PlWarehouseInfo;
use App\Models\PlWarehouseLog;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Revolution\Google\Sheets\Sheets;

class PlWarehouseController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $marcaBy = $request->get('marca');
        $descrizioneBy = $request->get('descrizione');
        $pnInternoBy = $request->get('pnInterno');

        if (empty($sortByName)) {
            $sortByName = 'marca';
            $orderBy = 'desc';
        }
        $objs = DB::table('pl_warehouses')
            ->Where(function ($query) use ($marcaBy) {
                if ($marcaBy)
                    $query->Where('marca', 'LIKE', '%' . $marcaBy . '%');
            })
            ->Where(function ($query) use ($descrizioneBy) {
                if ($descrizioneBy)
                    $query->Where('descrizione', 'LIKE', '%' . $descrizioneBy . '%');
            })
            ->Where(function ($query) use ($pnInternoBy) {
                if ($pnInternoBy)
                    $query->Where('pn_interno', 'LIKE', '%' . $pnInternoBy . '%');
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function store(Request $request)
    {
        $obj = new PlWarehouse();
        $obj->marca = $request->marca;
        $obj->descrizione = $request->descrizione;
        $obj->tipologia = 'aa';
        $obj->pn_interno = $request->pn_interno;
        $obj->pn_oem = $request->pn_oem;
        $obj->quantita_minima = $request->quantita_minima;
        $obj->quantita = $request->quantita;
        $obj->data_fornitura = $request->data_fornitura;
        $obj->prezzo = $request->prezzo;
        $obj->save();


        $message = 'Messaggi.Materiale-Salvato';

        return response()->json(
            [
                'success' => 200,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function update(Request $request,$id)
    {
        $obj = PlWarehouse::find($id);
        $obj->marca = $request->marca;
        $obj->descrizione = $request->descrizione;
        $obj->tipologia = $request->tipologia;
        $obj->pn_interno = $request->pn_interno;
        $obj->pn_oem = $request->pn_oem;
        $obj->quantita_minima = $request->quantita_minima;
        //$obj->quantita = $request->quantita;
        $obj->prezzo = $request->prezzo;
        $obj->save();

        $message = 'Messaggi.Materiale-Aggiornato';

        return response()->json(
            [
                'success' => 200,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function storeInfo(Request $request)
    {
        $obj = new PlWarehouseInfo();
        $obj->magazzino_id = $request->magazzino_id;
        $obj->tipologia = $request->tipologia;
        $obj->sito = $request->sito;
        $obj->link = $request->link;
        $obj->pn_oem = $request->pn_oem;
        $obj->prezzo = $request->prezzo;
        $obj->save;

        $message = 'Messaggi.Salvato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function view($id)
    {
        $obj =  PlWarehouse::find($id);

        return response()->json($obj);
    }

    public function getInfo($id)
    {
        $objs = PlWarehouseInfo::Where('magazzino_id', $id)->get();

        return response()->json($objs);
    }

    public function storeQuantity(Request $request, $id)
    {
        $obj =  PlWarehouse::find($id);
        $obj->quantita+= $request->quantita;
        $obj->data_fornitura = date('Y-m-d');
        $obj->save();

        $message = 'Messaggi.Quantita-Aggiunta';

        return response()->json(
            [
                'success' => 200,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function storeProvider(Request $request, $id)
    {
        $obj = new PlWarehouseInfo();
        $obj->magazzino_id = $id;
        $obj->tipologia = $request->tipologia;
        $obj->sito = ucwords(strtolower($request->sito));
        $obj->link = strtolower($request->link);
        $obj->prezzo = $request->prezzo;
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

    public function register(Request $request,$id)
    {
        $obj = PlWarehouse::find($id);
        $obj->quantita = $obj->quantita - $request->quantita;
        $obj->save();

        $asset = PlAsset::find($request->idRegistrazione);
        $asset->registrato = true;
        $asset->save();

        PlWarehouseLog::stored($asset->id, $id, $request->quantita);

        dispatch(new ControlloQuantitaMagazzino($obj->id));


        $message = 'Messaggi.Registrato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
            ]
        );
    }

    public function devideAsset(Request $request, $id)
    {
        $obj = PlWarehouse::find($id);
        $obj->quantita = $obj->quantita - $request->quantita;
        $obj->save();

        PlWarehouseLog::stored($request->idAsset, $id, $request->quantita);

        dispatch(new ControlloQuantitaMagazzino($obj->id));


        $message = 'Messaggi.Registrato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
            ]
        );
    }

    public function deviceBroken(Request $request, $id)
    {
        $obj = PlWarehouseLog::find($request->deviceId);
        $obj->dismesso = true;
        $obj->data_dismesso = date('Y-m-d');
        $obj->save();

        $message = 'Messaggi.Registrato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
            ]
        );
    }

    public function deviceReturned(Request $request, $id)
    {
        $obj = PlWarehouseLog::find($request->deviceId);
        $obj->ritirato = true;
        $obj->data_ritirato = date('Y-m-d');
        $obj->save();

        $warehouse = PlWarehouse::find($obj->magazzino_id);
        $warehouse->quantita+= $obj->quantita;
        $warehouse->save();

        $message = 'Messaggi.Registrato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
            ]
        );
    }

    public function deviceNota(Request $request, $id)
    {
        $obj = PlWarehouseLog::find($id);
        $obj->descrizione = $request->descrizione;
        $obj->save();

        $message = 'Messaggi.Registrato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
            ]
        );
    }
}
