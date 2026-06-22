<?php

namespace App\Http\Controllers;

use App\Imports\PlAssetImport;
use App\Models\PlAsset;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class PlAssetController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $utenteBy = $request->get('utente');
        $numero_serialeBy = $request->get('numero_seriale');
        $tipologiaBy = $request->get('tipologia');
        $statoBy = $request->get('stato');
        $registratiBy = $request->get('registrati');

        if(empty($sortByName)){
            $sortByName = 'utente';
            $orderBy = 'desc';
        }
        $objs = DB::table('pl_assets')
            ->Where(function ($query) use ($utenteBy) {
                if ($utenteBy)
                    $query->Where('utente', 'LIKE','%'.$utenteBy.'%');
            })
            ->Where(function ($query) use ($tipologiaBy) {
                if ($tipologiaBy)
                    $query->Where('tipo_asset', $tipologiaBy);
            })
            ->Where(function ($query) use ($statoBy) {
                if ($statoBy)
                    $query->Where('stato', $statoBy);
            })
            ->Where(function ($query) use ($numero_serialeBy) {
                if ($numero_serialeBy)
                    $query->Where('numero_seriale', $numero_serialeBy);
            })
            ->Where(function ($query) use ($registratiBy) {
                if (!is_null($registratiBy))
                    $query->Where('registrato', $registratiBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function get_list(Request $request)
    {
        $statoBy = $request->get('stato');

        $objs = DB::table('pl_assets')
            ->Where(function ($query) use ($statoBy) {
                if ($statoBy)
                    $query->Where('stato', $statoBy);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($objs);
    }

    public function view($id)
    {

        $obj = DB::table('pl_assets')->where('id',$id)->first();
        return response()->json($obj);
    }

    public function store(Request $request)
    {
        $obj =  new PlAsset();
    }

    public function update(Request $request, $id)
    {
        $obj = PlAsset::find($id);
        $obj->codice_asset = $request['codice_asset'];
        $obj->stato = $request['stato'];
        $obj->condizione_asset = $request['condizione_asset'];
        $obj->utilizzo = $request['utilizzo'];
        $obj->utente = $request['utente'];
        $obj->email = $request['email'];
        $obj->data_allocazione = $request['data_allocazione'];
        $obj->tipo_allocazione = $request['tipo_allocazione'];
        $obj->data_dismesso = $request['data_dismesso'];
        $obj->motivazione_dismesso = $request['motivazione_dismesso'];
        $obj->hostName = $request['hostName'];
        $obj->nome_utente_effetivo = $request['nome_utente_effetivo'];
        $obj->tipo_asset = $request['tipo_asset'];
        $obj->cpu = $request['cpu'];
        $obj->cpu_numero = $request['cpu_numero'];
        $obj->hdd_capienza = $request['hdd_capienza'];
        $obj->hdd_numero = $request['hdd_numero'];
        $obj->ip_address = $request['ip_address'];
        $obj->marca = $request['marca'];
        $obj->modello = $request['modello'];
        $obj->mause = $request['mause'];
        $obj->sistema_operativo = $request['sistema_operativo'];
        $obj->ram_numero = $request['ram_numero'];
        $obj->ram_memoria = $request['ram_memoria'];
        $obj->numero_seriale = $request['numero_seriale'];
        $obj->anydesk_alias = $request['anydesk_alias'];
		$obj->monitoraggio_attivo = $request['monitoraggio_attivo'];
        $obj->registrato = 1;
        $obj->save();

        $message = 'Messaggi.Asset-Modificato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'objs' => null
            ]
        );
    }

    public function import(Request $request)
    {

        if (!empty($request)) {
            $base64Image = $request->file_upload['file'];

            if (!$tmpFileObject = $this->validateBase64($base64Image, ['xls', 'xlsx'])) {
                return response()->json([
                    'error' => 'Invalid image format.'
                ], 415);
            }

            $tmpFileObjectPathName = $tmpFileObject->getPathname();

            $file = new UploadedFile(
                $tmpFileObjectPathName,
                $tmpFileObject->getFilename(),
                $tmpFileObject->getMimeType(),
                0,
                true
            );

            Excel::import(new PlAssetImport, $file);

            unlink($tmpFileObjectPathName); // delete temp file
        }

        $message = 'Messaggi.Asset-Importati';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'objs' => null
            ]
        );
    }

    public function register(Request $request)
    {
        $token = 'Xo9rqkOpiomDHbKka8y1QvTJBI3lhEzBBTPGAX1gFVxHSDzTdRV06xxp74L4ECtc';
  
        if($token === $request->Token){
            $obj = DB::table('pl_assets')
                ->where('numero_seriale', $request->Seriale)
                ->first();

            if(empty($obj->id)){
                $obj = new PlAsset();
                $obj->hostName = $request->Host;
                $obj->cpu = $request->Cpu;
				$obj->condizione_asset = 'Good';
                $obj->nazione = 'Italy';
                $obj->cpu_numero = $request->CpuN;
                $obj->hdd_capienza = explode(".", $request->Hdd)[0];
                $obj->ip_address = $request->Ip;
                $obj->modello = $request->Modello;
                $obj->sistema_operativo = $request->Sistema;
                $obj->ram_memoria = $request->RamSize;
                $obj->numero_seriale = $request->Seriale;
                $obj->marca = $request->Marca;
                $obj->utente = $request->Utente;
                $obj->tipo_asset = $request->Tipologia;
                $obj->email = $request->Email;
				$obj->tag_asset = $request->Categoria;
                $obj->registrato = true;
                $obj->save();
                return response()->json(['status'=> true, 'code'=> '100']); // Asset Registrato
            }else
                return response()->json(['status'=> true, 'code'=> '101']); // Asset Presente

        }else
            return response()->json(['status'=> false, 'code'=> '300']); // Token Errato


    }

    private function validateBase64(string $base64data, array $allowedMimeTypes)
    {
        // strip out data URI scheme information (see RFC 2397)
        if (str_contains($base64data, ';base64')) {
            list(, $base64data) = explode(';', $base64data);
            list(, $base64data) = explode(',', $base64data);
        }

        // strict mode filters for non-base64 alphabet characters
        if (base64_decode($base64data, true) === false) {
            return false;
        }

        // decoding and then re-encoding should not change the data
        if (base64_encode(base64_decode($base64data)) !== $base64data) {
            return false;
        }

        $fileBinaryData = base64_decode($base64data);

        // temporarily store the decoded data on the filesystem to be able to use it later on
        $tmpFileName = tempnam(sys_get_temp_dir(), 'medialibrary');
        file_put_contents($tmpFileName, $fileBinaryData);

        $tmpFileObject = new File($tmpFileName);

        // guard against invalid mime types
        $allowedMimeTypes = Arr::flatten($allowedMimeTypes);

        // if there are no allowed mime types, then any type should be ok
        if (empty($allowedMimeTypes)) {
            return $tmpFileObject;
        }

        // Check the mime types
        $validation = Validator::make(
            ['file' => $tmpFileObject],
            ['file' => 'mimes:' . implode(',', $allowedMimeTypes)]
        );

        if($validation->fails()) {
            return false;
        }

        return $tmpFileObject;
    }
	
	public function get_not_associated()
    {
        $asset = DB::table('pl_assets')->select('id',DB::raw("CONCAT(numero_seriale,' - ',marca) AS titolo"))
            ->where('registrato',false)
            ->get();

        return response()->json($asset);
    }

    public function get_associated()
    {
        $asset = DB::table('pl_assets')->select('id',DB::raw("CONCAT(utente,' - ',numero_seriale) AS titolo"))
            //->where('registrato',true)
            ->whereIn('tipo_asset', ['Desktop','Laptop','Tablet'])
            ->orderby('utente','asc')
            ->get();

        return response()->json($asset);
    }

    public function devices(Request $request, $id)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');

        if (empty($sortByName)) {
            $sortByName = 'updated_at';
            $orderBy = 'asc';
        }

        $asset = DB::table('pl_warehouse_logs')->select('pl_warehouse_logs.*','pl_warehouses.descrizione', 'pl_warehouses.tipologia','pl_warehouse_logs.descrizione as descrizione_log')
            ->join('pl_assets','pl_warehouse_logs.asset_id','pl_assets.id')
            ->join('pl_warehouses','pl_warehouse_logs.magazzino_id','pl_warehouses.id')
            ->where('pl_warehouse_logs.asset_id',$id)
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($asset);
    }
}
