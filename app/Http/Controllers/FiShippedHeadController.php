<?php

namespace App\Http\Controllers;

use App\Imports\FiShippedImport;
use App\Jobs\SpeditoCalcoloDistanzaKm;
use App\Jobs\SpeditoEmail;
use App\Jobs\SpeditoEmailMensile;
use App\Models\FiShippedHead;
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

			$month = date('m');
            $year = date('Y');
            if($request->mese_precendente){
                $data_importazione = date('Y-m-d H:i:s',strtotime('-1 months'));
                $d = explode("-",$data_importazione);
                $month = $d[1];
                $year = $d[0];
            }

            $lastRecord = FiShippedHead::where('anno', $year)->where('mese',  $month)->orderBy('created_at', 'desc')->first();

            $obj = new FiShippedHead();
            $obj->target_cc = $request->targhetCc;
            $obj->target_ofc = $request->targhetOfc;
            $obj->target_fkm = $request->targhetKfm;
            $obj->target_ckm_ofc =  $request->targhetOfcCkm;
            $obj->target_ckm_cc =  $request->targhetCcCkm;
            $obj->user = Auth::id();
            $obj->anno = $year;
            $obj->mese = $month;
            $obj->import = true;
			if($request->mese_precendente){
                $obj->created_at = $data_importazione;
                $obj->updated_at = $data_importazione;
            }
            $obj->save();

            $targets = [
                ['titolo'=>'value_cc','target'=>$request->targhetCc,'id'=>$obj->id],
                ['titolo'=>'value_ofc','target'=>$request->targhetOfc,'id'=>$obj->id],
                ['titolo'=>'fkm_ofc','target'=>$request->targhetKfm,'id'=>$obj->id],
                ['titolo'=>'ckm_cc','target'=>$request->targhetCcCkm,'id'=>$obj->id],
                ['titolo'=>'ckm_ofc','target'=>$request->targhetOfcCkm,'id'=>$obj->id],
            ];

            $d = $year.'-'.$month.'-01';
            $t = new TargetController();
            $t->store($targets,2,$d);

            $import = new FiShippedImport($obj->id);
            Excel::import($import, $file);

            $targets = [
                'value_cc' => round($import->result['target_cc'],3),
                'value_ofc' => round($import->result['target_ofc'],3),
                'fkm_ofc' => round($import->result['target_fkm'],3),
                'ckm_cc' =>round($import->result['target_ckm'],3),
                'ckm_ofc' =>round($import->result['target_ofc_ckm'],3),
            ];

            $t->update($targets,2,$d);

            $obj->value_cc = round($import->result['target_cc'],3);
            $obj->value_ofc = round($import->result['target_ofc'],3);
            $obj->value_fkm_ofc = round($import->result['target_fkm'],3);
            $obj->value_ckm_ofc = round($import->result['target_ofc_ckm'],3);
            $obj->value_ckm_cc = round($import->result['target_ckm'],3);
            $obj->totale_spedito = $obj->value_cc + $obj->value_ofc;
            $obj->save();

            unlink($tmpFileObjectPathName); // delete temp file
            if(!empty($lastRecord->id))
                FiShippedHead::find($lastRecord->id)->delete();
            // calocolo delle distanze
            dispatch(new SpeditoCalcoloDistanzaKm($obj->id));
            // Creazione File Google Sheet

            // invio email di notifica alla coda
			if($request->mese_precendente)
                dispatch(new SpeditoEmailMensile($obj->id));
            else
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
	
	public function get_target($id)
    {
        $obj = DB::table('fi_shipped_heads')->where('id',$id)->first();
        $return = [
            ['titolo'=>'Target-Cc','dimensione'=>250,'percentuale'=>round(((($obj->target_cc - $obj->value_cc) / $obj->target_cc) - 1) * - 100,0),'target'=>number_format($obj->target_cc,2,',','.'),'valore'=>number_format($obj->value_cc,2,',','.')],
            ['titolo'=>'Target-Ofc','dimensione'=>250,'percentuale'=>round(((($obj->target_ofc - $obj->value_ofc) / $obj->target_ofc) - 1) * - 100,0),'target'=>number_format($obj->target_ofc,2,',','.'),'valore'=>number_format($obj->value_ofc,2,',','.')],
			['titolo'=>'Target-Ofc-Fkm','dimensione'=>250,'percentuale'=>round(((($obj->target_fkm - $obj->value_fkm_ofc) / $obj->target_fkm) - 1) * - 100,0),'target'=>number_format($obj->target_fkm,3,',','.'),'valore'=>number_format($obj->value_fkm_ofc,3,',','.')],
            ['titolo'=>'Target-Ofc-Ckm','dimensione'=>250,'percentuale'=>round(((($obj->target_ckm_ofc - $obj->value_ckm_ofc) / $obj->target_ckm_ofc) - 1) * - 100,0),'target'=>number_format($obj->target_ckm_ofc,3,',','.'),'valore'=>number_format($obj->value_ckm_ofc,3,',','.')],
            ['titolo'=>'Target-Cc-Ckm','dimensione'=>250,'percentuale'=>round(((($obj->target_ckm_cc - $obj->value_ckm_cc) / $obj->target_ckm_cc) - 1) * - 100,0),'target'=>number_format($obj->target_ckm_cc,3,',','.'),'valore'=>number_format($obj->value_ckm_cc,3,',','.')],
        ];

        return response()->json($return);
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
        $obj = FiShippedHead::find($id);
        $obj->delete();
        $message = 'Spedito-Eliminato';
        $color = 'success';
        $success = true;

        $text ='
        <h6 class="font-weight-medium text-sm">Spedito Del: '.$obj->anno.'-'.$obj->mese.'</h6>';
        LogActivity::addToLog('Spedito Eliminato', ['text'=>$text],'error','deleted');
        return response()->json(
            [
                'success' => $success,
                'message' => $message ,
                'color' => $color,
            ]
        );
    }

    public function getTarghet(Request $request)
    {
        $anno =  date('Y');
        $mese =  date('m');

        if($request->mese_precendente == 'true'){
            $data = date('Y-m',strtotime('-1 months'));
            $anno =  explode("-",$data)[0];
            $mese =  explode("-",$data)[1];
        }
        $lastRecord = FiShippedHead::where('anno', $anno)->where('mese', $mese)->orderBy('created_at', 'desc')->first();
        return response()->json($lastRecord);
    }
}
