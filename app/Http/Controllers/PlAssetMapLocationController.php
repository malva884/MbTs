<?php

namespace App\Http\Controllers;

use App\Models\PlAsset;
use App\Models\PlAssetMapLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlAssetMapLocationController extends Controller
{

    public function list(Request $request, $id)
    {
        $serialeBy = $request->get('seriale');
        $utenteBy = $request->get('utente');

        $objs = DB::table('pl_asset_map_locations')->select('pl_asset_map_locations.*','pl_asset_typologies.icona')
            ->leftJoin('pl_asset_typologies','pl_asset_typologies.tipologia','pl_asset_map_locations.tipo_asset')
            ->where('map_id',$id)
            ->Where(function ($query) use ($utenteBy) {
                if ($utenteBy)
                    $query->Where('pl_asset_map_locations.utente', 'LIKE', '%' . $utenteBy . '%');
            })
            ->Where(function ($query) use ($serialeBy) {
                if ($serialeBy)
                    $query->Where('pl_asset_map_locations.numero_seriale', $serialeBy);
            })
            ->get();

        return response()->json($objs);
    }

    public function store(Request $request)
    {
        $asset = DB::table('pl_assets')->where('id',$request->asset_id)->first();

        $obj = new PlAssetMapLocation();
        $obj->asset_id = $asset->id;
        $obj->map_id = $request->map;
        $obj->posX = $request->posX;
        $obj->posY = $request->posY;
        $obj->cordinate = $request->cordinate;
        $obj->utente = $asset->utente;
        $obj->ip_address = $asset->ip_address;
        $obj->numero_seriale = $asset->numero_seriale;
        $obj->data_allocazione = $asset->data_allocazione;
        $obj->tipo_asset = $asset->tipo_asset;
        $obj->user_id = Auth::id();
        $obj->save();

        if(!is_null($obj->ip_address))
            $obj->online = PlAssetMapLocation::ping($obj->ip_address);

        $message = 'Messaggi.Asset-Aggiunto';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function check(Request $request)
    {
        $obj = DB::table('pl_asset_map_locations')->where('asset_id',$request->asset_id)->first();

        if(empty($obj->id))
            return response()->json(false);
        else
            return response()->json(true);
    }

    public function run_ping(Request $request)
    {
        $ip = $request->ip;

        $attivo = false;
        if(!is_null($ip))
            $attivo = PlAssetMapLocation::ping($ip);

        return response()->json(['attivo' => $attivo]);
    }

    public function deleted($id)
    {

        $obj =  PlAssetMapLocation::find($id)->delete();

        $message = 'Messaggi.Allocazione-Eliminata';
        $color = 'success';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => $color,
            ]);
    }
}
