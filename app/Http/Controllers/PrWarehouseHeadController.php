<?php

namespace App\Http\Controllers;

use App\Imports\FiGoodsTrasitImport;
use App\Imports\PrWarehouseImport;
use App\Jobs\MagazzinoCalcoloFinale;

use App\Jobs\MagazzinoEmail;
use App\Models\PrWarehouseHead;
use App\Models\PrWarehouseRows;
use App\Models\LogActivity;
use App\Services\GoogleDrive;
use App\Services\GoogleSheet;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Revolution\Google\Sheets\Facades\Sheets;

class PrWarehouseHeadController extends Controller
{
    public function list(Request $request)
    {
		
        $dataBy = $request->get('data');
		$periodoBy = $request->get('periodo');

        if (empty($sortByName)) {
            $sortByName = 'created_at';
            $orderBy = 'desc';
        }

        $objs = PrWarehouseHead::
			Where(function ($query) use ($dataBy) {
				if ($dataBy) {
					$dataBy = explode('-', $dataBy);
					$query->WhereYear('data_riferimento', $dataBy[0]);
					$query->WhereMonth('data_riferimento', $dataBy[1]);
				}
			})
			->Where(function ($query) use ($periodoBy) {
				$query->where('titolo','LIKE','%'.$periodoBy.'%');
			})
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function head($id)
    {
        $obj = PrWarehouseHead::find($id);

        return response()->json($obj);
    }

    public function view(Request $request, $id)
    {
		$sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $materialeBy = $request->get('materiale');
        $classeBy = $request->get('classe');

        if (empty($sortByName)) {
            $sortByName = 'materiale';
            $orderBy = 'desc';
        }

        $objs = PrWarehouseRows::
        Where('warehouse_id', $id)
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy) {
                    $query->where('materiale', 'LIKE', '%' . $materialeBy . '%');
                }
            })
            ->Where(function ($query) use ($classeBy) {
                if ($classeBy) {
                    $query->where('classe', $classeBy);
                }
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);


        return response()->json($objs);
    }

    public function import(Request $request)
    {
		ini_set('max_execution_time', -1);
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

            $data_riferimento = date('Y-m-d', strtotime(date('Y-m-d') . " -1 month")); # da reimpostare a -1
            $titolo = date('F Y', strtotime(date('Y-m-d') . " -1 month"));   # da reimpostare a -1
            $tmp = explode('-', $data_riferimento);
			

            $corsoLavori =  DB::connection('mysql_old')->table('work_status_heads')->select('total_final')
                ->orderByDesc('created_at')
                ->first();

            $carteggaDriver = GoogleDrive::add_folder([env('ID_GOOGLE_MAGAZZINI')], $tmp[0],'google',true);
            $fileMagazzino = GoogleDrive::search($carteggaDriver, 'google', 'file', $tmp[1], false);
			if(empty($fileMagazzino))
				$fileMagazzino = GoogleSheet::createSheet($tmp[1],$carteggaDriver);


            if(empty($corsoLavori->total_final) || empty($fileMagazzino)){
                $message = 'Messaggi.Magazzino-Importazione-Magazino';

                return response()->json(
                    [
                        'success' => true,
                        'message' => $message,
                        'color' => 'error',
                    ]
                );
            }

            $obj = new PrWarehouseHead();
            $obj->titolo = $titolo;
            $obj->user = Auth::id();
            $obj->anno = $tmp[0];
            $obj->mese = $tmp[1];
            $obj->data_riferimento = $data_riferimento;
            $obj->calcolato = false;
            $obj->corso_lavori = $corsoLavori->total_final;
            $obj->save();

            $t = new TargetController();
            $targets = [
                ['titolo' => 'value_cc', 'target' => 0, 'id' => $obj->id],
                ['titolo' => 'value_ofc', 'target' => 0, 'id' => $obj->id],
                ['titolo' => 'fkm_ofc', 'target' => 0, 'id' => $obj->id],
                ['titolo' => 'ckm_cc', 'target' => 0, 'id' => $obj->id],
                ['titolo' => 'ckm_ofc', 'target' => 0, 'id' => $obj->id],
            ];

            $t->store($targets, 4, $data_riferimento);
            $import = new PrWarehouseImport($obj->id);
            Excel::import($import, $file);

            $obj->totale = round($import->result['valore_cc'] + $import->result['valore_ofc'], 2);
            $obj->fkm_ofc = round($import->result['fkm_ofc'], 3);
            $obj->ckm_ofc = round($import->result['ckm_ofc'], 3);
            $obj->ckm_cc = round($import->result['ckm_cc'], 3);
            $obj->path_drive = $fileMagazzino;
            $obj->save();


            Sheets::spreadsheet($fileMagazzino);
            $titolo = Date('Y-m-d H:i');
            Sheets::addSheet('Details '.$titolo);
            Sheets::addSheet('Summary '.$titolo);
            Sheets::sheet('Details '.$titolo)->update($import->sheet);

            $arr = [];
            foreach ($import->material_class as $class => $values) {
                $arr[] = [$class, (float)$values['valore']];
            }
			$arr[] = ['Corso Lavori', (float)$obj->corso_lavori];
            Sheets::sheet('Summary '.$titolo)->update($arr);
            $targets = [
                'value_cc' => round($import->result['valore_cc'], 2),
                'value_ofc' => round($import->result['valore_ofc'], 2),
                'fkm_ofc' => round($import->result['fkm_ofc'], 3),
                'ckm_cc' => round($import->result['ckm_cc'], 3),
                'ckm_ofc' => round($import->result['ckm_ofc'], 3),
            ];

            $t->update($targets, 4, $data_riferimento);
            unlink($tmpFileObjectPathName); // delete temp file
			
			 // invio email di notifica alla coda
            dispatch(new MagazzinoEmail($obj->id));
        }

        $message = 'Messaggi.Magazzino-Importato';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'objs' => $obj
            ]
        );
    }

    public function get_magazzono(Request $request, $id = null)
    {

        if (!empty($request)) {
            $values = ['valore_ofc' => 0, 'valore_cc' => 0, 'fkm_ofc' => 0, 'ckm_ofc' => 0, 'ckm_cc' => 0];
            $totale = 0;
            $sheet = [];
            $material_class = [];

            if (!empty($id)) {
                $materialeBy = $request->get('materiale');
                $classeBy = $request->get('classe');

                $materials = DB::table('pr_warehouse_rows')->select('materiale as material', 'descrizione as description', 'valore_unitario as unitary_value', 'quantita as total_stock', 'ultimo_movimento as last_gds_mvmt', 'valore_totale as total_value', 'crcy as bun')
                    ->where('warehouse_id', $id)
                    ->Where(function ($query) use ($materialeBy) {
                        if ($materialeBy) {
                            $query->where('materiale', 'LIKE', '%' . $materialeBy . '%');
                        }
                    })
                    ->Where(function ($query) use ($classeBy) {
                        if ($classeBy) {
                            $query->where('classe', $classeBy);
                        }
                    })
                    ->get();

                $head = DB::table('pr_warehouse_heads')->where('id',$id)->first();
            } else {
                # todo Sistemare la colonna total_stock e total_value
                $materials = DB::connection('sqlsrv_gp')->table('AGG_PRODOTTI_TMP')
                    //->select('AGG_PRODOTTI_TMP.cdProdotto as material','AGG_PRODOTTI_TMP.Valore as unitary_value','AGG_PRODOTTI_TMP.Valore as total_stock','AGG_PRODOTTI_TMP.dtUltimoMovimento as last_gds_mvmt','AGG_PRODOTTI_TMP.dsProdotto as description',DB::raw('SUM(Valore*1) as total_value'))
                    ->select('cdProdotto as material', 'Valore as unitary_value', 'Valore as total_stock', 'dtUltimoMovimento as last_gds_mvmt', 'dsProdotto as description', 'cdUM as bun', DB::raw('Valore as total_value'))
                    ->get();
            }


            foreach ($materials as $material) {
                $result = PrWarehouseRows::processing((array)$material);
                //Log::channel('stderr')->info($result['material_class']);
                $values['valore_ofc'] += $result['values']['valore_ofc'];
                $values['valore_cc'] += $result['values']['valore_cc'];
                $values['fkm_ofc'] += $result['values']['fkm_ofc'];
                $values['ckm_ofc'] += $result['values']['ckm_ofc'];
                $values['ckm_cc'] += $result['values']['ckm_cc'];

                if (!empty($material_class[$result['class']])) {
                    $material_class[$result['class']]['ckm'] = round($material_class[$result['class']]['ckm'] + $result['material_class']['ckm'], 3);
                    $material_class[$result['class']]['fkm'] = round($material_class[$result['class']]['fkm'] + $result['material_class']['fkm'], 3);
                    $material_class[$result['class']]['valore'] += round($result['material_class']['valore'], 2);
                } else {
                    $material_class[$result['class']]['ckm'] = round($result['material_class']['ckm'], 3);
                    $material_class[$result['class']]['fkm'] = round($result['material_class']['fkm'], 3);
                    $material_class[$result['class']]['valore'] = round($result['material_class']['valore'], 2);
                }
                $totale = $totale + round($result['material_class']['valore'], 2);
            }

            if(empty($materialeBy) && empty($classeBy))
                $material_class['Magazzino']['valore'] = $totale;

            if(!empty($head) && empty($materialeBy) && empty($classeBy))
                $material_class['Corso Lavori']['valore'] = $head->corso_lavori;
        }
        $message = 'Messaggi.Magazzino-Calcolato';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'objs' => $material_class
            ]
        );
    }
	
	public function deleted($id)
    {
        $obj = PrWarehouseHead::find($id);
        $obj->delete();
        $message = 'Messaggi.Magazzino-Eliminato';
        $color = 'success';
        $success = true;

        $text ='
        <h6 class="font-weight-medium text-sm">Magazzino Del: '.$obj->titolo.'</h6>';
        LogActivity::addToLog('Magazzino Eliminato', ['text'=>$text],'error','deleted');
        return response()->json(
            [
                'success' => $success,
                'message' => $message ,
                'color' => $color,
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

        if ($validation->fails()) {
            return false;
        }

        return $tmpFileObject;
    }
}
