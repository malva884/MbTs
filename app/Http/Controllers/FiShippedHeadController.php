<?php

namespace App\Http\Controllers;

use App\Imports\FiShippedImport;
use App\Jobs\SpeditoCalcoloDistanzaKm;
use App\Jobs\SpeditoEmail;
use App\Models\FiShippedHead;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class FiShippedHeadController extends Controller
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
        $objs = DB::table('fi_shipped_heads')
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
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 3600); // 3600 seconds = 60 minutes
        set_time_limit(3600);
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

            $lastRecord = FiShippedHead::where('anno', date('Y'))->where('mese', date('m'))->orderBy('created_at', 'desc')->first();

            $obj = new FiShippedHead();
            $obj->target_cc = $request->targhetCc;
            $obj->target_ofc = $request->targhetOfc;
            $obj->target_fkm = $request->targhetKfm;
            $obj->user = Auth::id();
            $obj->anno = date('Y');
            $obj->mese = date('m');
            $obj->import = true;
            $obj->save();

            $targets = [
                ['titolo'=>'value_cc','target'=>$request->targhetCc,'id'=>$obj->id],
                ['titolo'=>'value_ofc','target'=>$request->targhetOfc,'id'=>$obj->id],
                ['titolo'=>'fkm_ofc','target'=>$request->targhetKfm,'id'=>$obj->id],
                ['titolo'=>'ckm_cc','target'=>'1000','id'=>$obj->id],

            ];

            $d = date('Y-m-01');
            $t = new TargetController();
            $t->store($targets,2,$d);

            $import = new FiShippedImport($obj->id);
            Excel::import($import, $file);

            $targets = [
                'value_cc' => round($import->result['target_cc'],3),
                'value_ofc' => round($import->result['target_ofc'],3),
                'fkm_ofc' => round($import->result['target_fkm'],3),
                'ckm_cc' =>round($import->result['target_ckm'],3),
            ];

            $t->update($targets,2,$d);

            $obj->value_cc = round($import->result['target_cc'],3);
            $obj->value_ofc = round($import->result['target_ofc'],3);
            $obj->value_fkm = round($import->result['target_fkm'],3);
            $obj->totale_spedito = $obj->value_cc + $obj->value_ofc;
            $obj->save();

            unlink($tmpFileObjectPathName); // delete temp file
            if(!empty($lastRecord->id))
                FiShippedHead::find($lastRecord->id)->delete();
            // calocolo delle distanze
            dispatch(new SpeditoCalcoloDistanzaKm($obj->id));
            // Creazione File Google Sheet

            // invio email di notifica alla coda
            dispatch(new SpeditoEmail($obj->id));

        }

        $message = 'Messaggi.Magazziono Importato.';

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

    public function getTarghet()
    {
        $lastRecord = FiShippedHead::where('anno', date('Y'))->where('mese', date('m'))->orderBy('created_at', 'desc')->first();
        return response()->json($lastRecord);
    }
}
