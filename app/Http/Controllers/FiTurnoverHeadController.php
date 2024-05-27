<?php

namespace App\Http\Controllers;

use App\Imports\FiTurnoverImport;
use App\Jobs\FatturatoEmail;
use App\Models\FiTurnoverHead;
use App\Models\LogActivity;
use App\Models\Target;
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
            $month = date('m');
            $year = date('Y');
            if($request->mese_precendente){
                $data_importazione = date('Y-m',strtotime('-1 months'));
                $d = explode("-",$data_importazione);
                $month = $d[1];
                $year = $d[0];
            }

            $lastRecord = FiTurnoverHead::where('anno', $year)->where('mese', $month)->orderBy('created_at', 'desc')->first();
            if(!empty($lastRecord->id)){
                $d = $year.'-'.$month.'-01';
                $obj = FiTurnoverHead::find($lastRecord->id);
            }
            else{
                $d = date('Y-m-01');
                $obj = new FiTurnoverHead();
                $obj->user = Auth::id();
                $obj->anno = date('Y');
                $obj->mese = date('m');
                $obj->import = false;
            }

            $obj->target_cc = $request->targhetCc;
            $obj->target_ofc = $request->targhetOfc;
            $obj->target_fkm = $request->targhetFkm;
            $obj->target_ckm_cc = $request->targhetCkm;
            $obj->target_ofc_ckm = $request->targhetCkmOfc;
            $obj->save();

            $targets = [
                ['titolo'=>'value_cc','target'=>$request->targhetCc,'id'=>$obj->id],
                ['titolo'=>'value_ofc','target'=>$request->targhetOfc,'id'=>$obj->id],
                ['titolo'=>'fkm_ofc','target'=>$request->targhetFkm,'id'=>$obj->id],
                ['titolo'=>'ckm_cc','target'=>$request->targhetCkm,'id'=>$obj->id],
                ['titolo'=>'ckm_ofc','target'=>$request->targhetCkmOfc,'id'=>$obj->id],
            ];

            $t = new TargetController();
            $t->store($targets,1,$d);

            $import = new FiTurnoverImport($obj->id);
            Excel::import($import, $file);

            $targets = [
                'value_cc' => $obj->value_cc + (float)str_replace("-", "", round($import->result['targhet_cc'],3)),
                'value_ofc' => $obj->value_ofc + (float)str_replace("-", "", round($import->result['targhet_ofc'],3)),
                'fkm_ofc' => $obj->value_fkm + (float)str_replace("-", "", round($import->result['targhet_fkm'],3)),
                'ckm_cc' => $obj->value_ckm + (float)str_replace("-", "", round($import->result['targhet_ckm'],3)),
                'ckm_ofc' => $obj->value_ckm + (float)str_replace("-", "", round($import->result['targhet_ofc_ckm'],3)),
            ];

            $t->update($targets,1,$d);
            $obj->value_cc = round($targets['value_cc'],3);
            $obj->value_ofc =  round($targets['value_ofc'],3);
            $obj->value_fkm_ofc =  round($targets['fkm_ofc'],3);
            $obj->value_ckm_cc = round($targets['ckm_cc'],3);
            $obj->value_ckm_ofc = round($targets['ckm_ofc'],3);
            $obj->totale_fatturato = $targets['value_cc'] + $targets['value_ofc'];
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
