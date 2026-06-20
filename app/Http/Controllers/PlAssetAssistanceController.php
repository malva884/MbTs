<?php

namespace App\Http\Controllers;

use App\Jobs\AssistenzaEmail;
use App\Models\PlAssetAssistance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlAssetAssistanceController extends Controller
{
    public function list(Request $request, $id = null)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $utenteBy = $request->get('utente');
        $serialeBy = $request->get('seriale');
        $numeroBy = $request->get('numero');
        $statoBy = $request->get('stato');
        $assetBy = $id;
        if(empty($sortByName)){
            $sortByName = 'numero_segnalazione';
            $orderBy = 'desc';
        }


        $objs = DB::table('pl_asset_assistances')->select('pl_asset_assistances.*','pl_assets.numero_seriale','pl_assets.hostName','pl_assets.anydesk_alias')
            ->join('pl_assets','pl_assets.id','pl_asset_assistances.asset_id')
            ->Where(function ($query) use ($utenteBy) {
                if ($utenteBy)
                    $query->Where('pl_asset_assistances.utente', 'LIKE','%'.$utenteBy.'%');
            })
            ->Where(function ($query) use ($serialeBy) {
                if ($serialeBy)
                    $query->Where('pl_assets.numero_seriale', $serialeBy);
            })
            ->Where(function ($query) use ($numeroBy) {
                if ($numeroBy)
                    $query->Where('numero_segnalazione', $numeroBy);
            })
            ->Where(function ($query) use ($statoBy) {
                if ($statoBy)
                    $query->Where('pl_asset_assistances.stato', $statoBy);
            })
            ->Where(function ($query) use ($assetBy) {
                if ($assetBy)
                    $query->Where('pl_asset_assistances.asset_id', $assetBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function open(Request $request)
    {
        $asset = DB::table('pl_assets')->where('numero_seriale',$request->Seriale)->first();

        if(!empty($asset->id)){
            $last = DB::table('pl_asset_assistances')->select('numero_segnalazione')->whereYear('created_at', date('Y'))->orderBy('created_at','desc')->first();

            if(!empty($last->numero_segnalazione))
                $numero = $last->numero_segnalazione + 1;
            else
                $numero = date('Y').'00001';
            $obj = new PlAssetAssistance();
            $obj->asset_id = $asset->id;
            $obj->numero_segnalazione = $numero;
            $obj->utente = $asset->utente;
            $obj->save();

            Dispatch(new AssistenzaEmail($asset->id));
            return response()->json(['status'=> true, 'code'=> '001']); // Segnalazione Aperta
        }else{
            return response()->json(['status'=> true, 'code'=> '010']); // Asset Non Trovato
        }

    }

    public function store(Request $request)
    {
        $obj = new PlAssetAssistance();
        $asset = DB::table('pl_assets')->where('id',$request->asset_id)->first();
        $obj->numero_segnalazione = strtotime(date('Y-m-d H:i:s'));
        $obj->utente = $asset->utente;
        $obj->asset_id = $request->asset_id;
        $obj->stato = $request->stato;
        $obj->motivazione = $request->motivazione;
        $obj->soluzione = $request->soluzione;
        $obj->save();

        $message = 'Messaggi.Richiesta-Inserita';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'objs' => null
            ]
        );

    }

    public function update(Request $request, $id)
    {
        $obj = PlAssetAssistance::find($id);

        if($obj->asset_id != $request->asset_id){
            $asset = DB::table('pl_assets')->where('id',$request->asset_id)->first();
            $obj->utente = $asset->utente;
        }
        $obj->asset_id = $request->asset_id;
        $obj->stato = $request->stato;
        $obj->motivazione = $request->motivazione;
        $obj->soluzione = $request->soluzione;
        $obj->save();

        $message = 'Messaggi.Richiesta-Modificata';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'objs' => null
            ]
        );

    }
}
