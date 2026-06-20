<?php

namespace App\Http\Controllers;

use App\Models\PlAssetMap;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PlAssetMapController extends Controller
{
    public function list(Request $request)
    {
        $gruppoBy = $request->get('gruppo');
        $serialeBy = $request->get('numero_serale');
        $utenteBy = $request->get('utente');

        $objs = DB::table('pl_asset_maps as b')->select('b.id','b.map','b.etichetta')
            ->leftJoin('pl_asset_map_locations as a','b.id','a.map_id')
            ->Where(function ($query) use ($utenteBy) {
                if ($utenteBy)
                    $query->Where('a.utente', 'LIKE', '%' . $utenteBy . '%');
            })
            ->Where(function ($query) use ($serialeBy) {
                if ($serialeBy)
                    $query->Where('a.numero_seriale', $serialeBy);
            })
            ->Where(function ($query) use ($gruppoBy) {
                if ($gruppoBy)
                    $query->Where('b.gruppo', $gruppoBy);
            })
            ->groupBy('b.id','b.map','b.etichetta')
            ->get();

        return response()->json($objs);
    }

    public function store(Request $request)
    {
        $base64Image = $request->file_upload['file'];

        if (!$tmpFileObject = $this->validateBase64($base64Image, ['png', 'jpeg', 'svg'])) {
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

        $storedFile = $file->store('maps', ['disk' => 'maps']);

        unlink($tmpFileObjectPathName);

        $obj = new PlAssetMap();
        $obj->map = $storedFile;
        $obj->etichetta = $request->map['etichetta'];
        $obj->gruppo = $request->map['gruppo'];
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

        $obj = PlAssetMap::find($id);
        $obj->etichetta = $request->map['etichetta'];
        $obj->gruppo = $request->map['gruppo'];
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

    public function view($id)
    {
        $obj = DB::table('pl_asset_maps')->where('id',$id)->first();

        return response()->json($obj);
    }

    public function deleted($id)
    {

        $obj =  PlAssetMap::find($id)->delete();

        $message = 'Messaggi.Mappa-Eliminata';
        $color = 'success';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => $color,
            ]);
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
}
