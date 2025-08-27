<?php

namespace App\Http\Controllers;

use App\Imports\FiGoodsTrasitImport;
use App\Jobs\MenceInTransitoCalcoloDistanzaKm;
use App\Models\FiGoodsTransitHead;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class FiGoodsTransitHeadController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $macchinaBy = $request->get('macchina');

        if (empty($sortByName)) {
            $sortByName = 'created_at';
            $orderBy = 'desc';
        }
        $objs = DB::table('fi_goods_transit_heads')
            ->Where(function ($query) use ($macchinaBy) {
                if ($macchinaBy)
                    $query->Where('nome', 'LIKE', '%' . $macchinaBy . '%');
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
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

            $lastRecord = FiGoodsTransitHead::where('anno', date('Y'))->where('mese', date('m'))->orderBy('created_at', 'desc')->first();

            $obj = new FiGoodsTransitHead();
            $obj->user = Auth::id();
            $obj->anno = date('Y');
            $obj->mese = date('m');
            $obj->import = true;
            $obj->save();

            $import = new FiGoodsTrasitImport($obj->id);
            Excel::import($import, $file);

            $obj->value_cc = round($import->result['targhet_cc'],3);
            $obj->value_ofc = round($import->result['targhet_ofc'],3);
            $obj->value_fkm = round($import->result['targhet_fkm'],3);
            $obj->value_ckm = round($import->result['targhet_ckm'],3);
            $obj->value_ofc_ckm = round($import->result['target_ofc_ckm'],3);
            $obj->totale = $obj->value_cc + $obj->value_ofc;
            $obj->save();

            unlink($tmpFileObjectPathName); // delete temp file
            if(!empty($lastRecord->id))
                FiGoodsTransitHead::find($lastRecord->id)->delete();
            // calocolo delle distanze
            dispatch(new MenceInTransitoCalcoloDistanzaKm($obj->id));

        }

        $message = 'Messaggi.Merce-In-Viaggio-Importata';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'objs' => $obj
            ]
        );
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
