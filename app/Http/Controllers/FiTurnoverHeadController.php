<?php

namespace App\Http\Controllers;

use App\Imports\FiTurnoverImport;
use App\Jobs\FatturatoEmail;
use App\Models\FiTurnoverHead;
use App\Models\LogActivity;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class FiTurnoverHeadController extends Controller
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
        $objs = DB::table('fi_turnover_heads')
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

            $lastRecord = FiTurnoverHead::where('anno', date('Y'))->where('mese', date('m'))->orderBy('created_at', 'desc')->first();
            if(!empty($lastRecord->id))
                $obj = FiTurnoverHead::find($lastRecord->id);
            else{
                $obj = new FiTurnoverHead();
                $obj->user = Auth::id();
                $obj->anno = date('Y');
                $obj->mese = date('m');
                $obj->import = false;
            }

            $obj->target_cc = $request->targhetCc;
            $obj->target_ofc = $request->targhetOfc;
            $obj->target_kfkm = $request->targhetKfkm;
            $obj->target_ckm = $request->targhetCkm;
            $obj->save();

            $import = new FiTurnoverImport($obj->id);
            Excel::import($import, $file);

            $obj->value_cc = $obj->value_cc + str_replace("-", "", round($import->result['targhet_cc'],3));
            $obj->value_ofc = $obj->value_of + str_replace("-", "", round($import->result['targhet_ofc'],3));
            $obj->value_kfkm =  $obj->value_kfkm + str_replace("-", "", round($import->result['targhet_kfkm'],3));
            $obj->value_ckm = $obj->value_ckm + str_replace("-", "", round($import->result['targhet_ckm'],3));
            $obj->totale_fatturato = $obj->totale_fatturato + str_replace("-", "", $obj->value_cc + $obj->value_ofc);
            $obj->import = ($import->result['check'] === false ? true:false);
            $obj->save();

            unlink($tmpFileObjectPathName); // delete temp file

            // Invio notifica Email
            if($obj->import)
                dispatch(new FatturatoEmail($obj->id));

        }

        $message = 'Messaggi.Fatturato-Importato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'check' => $import->result['check']
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

    public function deleted($id)
    {
        $obj = FiTurnoverHead::find($id);
        $obj->delete();
        $message = 'Fattorato-Eliminato';
        $color = 'success';
        $success = true;

        $text ='
        <h6 class="font-weight-medium text-sm">Fatturato Del: '.$obj->anno.'-'.$obj->mese.'</h6>';
        LogActivity::addToLog('Fatturato Eliminato', ['text'=>$text],'error','deleted');
        return response()->json(
            [
                'success' => $success,
                'message' => $message ,
                'color' => $color,
            ]
        );
    }

    public function getTarghet()
    {
        $lastRecord = FiTurnoverHead::where('anno', date('Y'))->where('mese', date('m'))->orderBy('created_at', 'desc')->first();
        return response()->json($lastRecord);
    }
}
