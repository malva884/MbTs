<?php

namespace App\Http\Controllers;

use App\Models\QtTypeTest;
use App\Services\GoogleDrive;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class QtTypeTestController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $materialeBy = $request->get('materiale');
        $olBy = $request->get('ol');
        $tipologiaBy = $request->get('tipologia');
        $esitoBy = $request->get('esito');
        $standardaBy = $request->get('standard');
        $specificaBy = $request->get('specifica');
        $dataBy = $request->get('data');


        if (empty($sortByName)) {
            $sortByName = 'data_prova';
            $orderBy = 'desc';
        }
        $objs = DB::table('qt_type_tests')->select('qt_type_tests.*','qt_categories.categoria')
            ->leftJoin('qt_categories','qt_categories.id','qt_type_tests.tipo')
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('materiale', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($olBy) {
                if ($olBy)
                    $query->Where('ol', 'LIKE', '%' . $olBy . '%');
            })
            ->Where(function ($query) use ($esitoBy) {
                if ($esitoBy)
                    $query->Where('esito', $esitoBy);
            })
            ->Where(function ($query) use ($tipologiaBy) {
                if ($tipologiaBy)
                    $query->Where('tipo', $tipologiaBy);
            })
            ->Where(function ($query) use ($standardaBy) {
                if ($standardaBy)
                    $query->Where('standard', $standardaBy);
            })
            ->Where(function ($query) use ($specificaBy) {
                if ($specificaBy)
                    $query->Where('specifica', $specificaBy);
            })
            ->Where(function ($query) use ($dataBy) {
                if (is_string($dataBy)) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2){
                        $query->whereBetween('data_prova', $dataBy);
                    }
                    else{
                        $query->whereDate('data_prova', $dataBy[0]);
                    }
                }
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function get_prove(Request $request, $ol)
    {

        $objs = DB::table('qt_type_tests')->select('qt_type_tests.*','qt_categories.categoria')
            ->join('qt_categories','qt_categories.id','qt_type_tests.tipo')
            ->where('ol', $ol)
            ->get();

        return response()->json($objs);
    }

    public function stored(Request $request)
    {

        ini_set('memory_limit', -1);
        ini_set('max_execution_time', 900);
        $obj = new QtTypeTest();
        $obj->ol = $request->ol;
        if (!empty($request->fai))
            $obj->fai = $request->fai;
        $obj->materiale = $request->materiale;
        $obj->descrizione = $request->descrizione;
        $obj->esito = $request->esito;
        $obj->standard = $request->standard;
        $obj->specifica = strtoupper($request->specifica);
        $obj->cliente = $request->cliente;
        $obj->note = $request->note;
        $obj->tipo = $request->tipo;
		$obj->versione = $request->versione;
        $obj->user = Auth::id();
        $category = $obj->categoriaTipo->categoria ?? null;
        $obj->data_prova = $request->data_prova;
        $obj->save();

        Log::channel('stderr')->info("QtTypeTest stored: inizio", [
            'id' => $obj->id,
            'ol' => $obj->ol,
            'categoria' => $category,
            'id_drive_categoria' => $obj->categoriaTipo->id_drive ?? null,
            'files_ricevuti' => is_array($request->files_upload) ? count($request->files_upload) : 0,
        ]);

        if ($category == 'TTM' || $category == 'TTC' || $category == 'TR' || $category == 'TC')
            $name = $obj->standard;
        else
            $name = date('Y');

        $parentDriveId = $obj->categoriaTipo->id_drive ?? null;
        if (empty($parentDriveId)) {
            Log::channel('stderr')->error("QtTypeTest stored: id_drive mancante per la categoria {$category} (tipo ID: {$obj->tipo})");
        }

        $idFolder[0] = GoogleDrive::search($parentDriveId, 'google', 'dir', $name);

        if (!$idFolder[0])
            $idFolder[0] = GoogleDrive::add_folder(array($parentDriveId), $name, 'google', false);

        $name_folder = $obj->ol . '-' . $obj->materiale;

        if (!empty($idFolder[0]['basename']))
            $idFolder[0] = $idFolder[0]['basename'];

        if (empty($idFolder[0])) {
            Log::channel('stderr')->error("QtTypeTest stored: Impossibile trovare o creare la cartella anno/standard '{$name}' su Drive");
        }

        $idFolder[1] = GoogleDrive::search($idFolder[0], 'google', 'dir', $name_folder);

        if (!$idFolder[1])
            $idFolder[1] = GoogleDrive::add_folder(array($idFolder[0]), $name_folder);

        if (!empty($idFolder[1]['basename']))
            $idFolder[1] = $idFolder[1]['basename'];

        Log::channel('stderr')->info("QtTypeTest stored: cartelle Drive", [
            'folder_parent' => $parentDriveId,
            'folder_0' => $idFolder[0] ?? null,
            'folder_1' => $idFolder[1] ?? null,
        ]);

        if (!empty($idFolder[1])) {
            if (!empty($request->files_upload) && is_array($request->files_upload)) {
                foreach ($request->files_upload as $index => $file) {
                    if (isset($file['file'], $file['fileExtension'])) {
                        $this->saveFile($file['file'], $idFolder[1], $file['fileExtension'], $name_folder);
                    } else {
                        Log::channel('stderr')->warning("QtTypeTest stored: file all'indice {$index} privo di 'file' o 'fileExtension'");
                    }
                }
            }
        } else {
            Log::channel('stderr')->error("QtTypeTest stored: cartella finale non valida, impossibile caricare i file su Drive!");
        }

        $obj->path_drive = !empty($idFolder[1]) ? $idFolder[1] : null;
        $obj->save();

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Record-Inserito',
            'color' => 'success',
            'obj' => $obj,
        ]);
    }
	
	 public function upload(Request $request,$id)
    {
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', 900);
        $obj = QtTypeTest::find($id);

        $idFolder = $obj->path_drive;
        $name_folder = $obj->ol . '-' . $obj->materiale;
        if (empty($idFolder)) {
            Log::channel('stderr')->error("QtTypeTest upload: record #{$id} privo di path_drive!");
        }
        if (!empty($request->files_upload) && is_array($request->files_upload)) {
            foreach ($request->files_upload as $file) {
                if (isset($file['file'], $file['fileExtension']))
                    $this->saveFile($file['file'], $idFolder, $file['fileExtension'], $name_folder);
            }
        }



        $message = 'Messaggi.File-Importati';

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
        $obj =  DB::table('qt_type_tests')->select('qt_type_tests.*','qt_categories.categoria')
            ->join('qt_categories','qt_categories.id','qt_type_tests.tipo')
            ->where('qt_type_tests.id',$id)->first();

        return response()->json($obj);
    }

    private function saveFile($file, $path, $ext_file, $nomeFile = null)
    {
        if (empty($path)) {
            Log::channel('stderr')->error("QtTypeTest saveFile: path cartella Google Drive non valido o vuoto!");
            return false;
        }

        if (!empty($file)) {
            $base64Image = $file;

            if (!$tmpFileObject = $this->validateBase64($base64Image, ['png', 'jpg', 'jpeg', 'HEIC', 'pdf', 'docx', 'xls', 'xlsx'])) {
                Log::channel('stderr')->error("QtTypeTest saveFile: formato base64 o mime-type non valido per estensione .{$ext_file}");
                return false;
            }

            $count_type_file = ['word' => 1000, 'exls' => 1010, 'img' => 1020, 'all' => 1100];
            $files = GoogleDrive::search($path, 'google', 'files', null);

            $tmpFileObjectPathName = $tmpFileObject->getPathname();
            $t = 1;
            $decodedFiles = is_iterable($files) ? $files : (json_decode($files, true) ?? []);
            if (is_iterable($decodedFiles)) {
                foreach ($decodedFiles as $existingFile) {
                    if (empty($existingFile['name'])) continue;
                    $ext = explode(".", $existingFile['name']);
                    if (count($ext) < 2) continue;
                    $tmp = explode("(", $ext[0]);
                    $n = (isset($tmp[1]) && is_numeric(substr($tmp[1], 0, -1))) ? (int)substr($tmp[1], 0, -1) : 0;
                    if ($n > $t) { $t = $n; }
                    switch (strtolower($ext[1])) {
                        case 'pdf':
                        case 'docx':
                            if ($n >= substr($count_type_file['word'], 0, 1)){
                                if($t == $n)
                                    $count_type_file['word'] = '1' . $n;
                            }
                            break;
                        case 'xls':
                        case 'xlsx':
                            if ($n >= substr($count_type_file['exls'], 0, 1))
                                $count_type_file['exls'] = '1' . $n;
                            break;
                        case 'jpg':
                        case 'jpeg':
                        case 'png':
                        case 'HEIC':
                            if ($n >= substr($count_type_file['img'], 0, 1)){
                                if($t == $n)
                                    $count_type_file['img'] = '1' . $n;
                            }
                            break;
                        default:
                            if ($n >= substr($count_type_file['all'], 0, 1))
                                $count_type_file['all'] = '1' . $n;
                    }
                }
            }

            //$exst = $ext_file;
            switch (strtolower($ext_file)) {
                case 'pdf':
                case 'docx':
                    $count_type_file['word']++;
                    $n = substr($count_type_file['word'], 1, 4);
                    break;
                case 'xls':
                case 'xlsx':
                    $count_type_file['exls']++;
                    $n = substr($count_type_file['exls'], 1, 4);
                    break;
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'HEIC':
                    $count_type_file['img']++;
                    $n = substr($count_type_file['img'], 1, 4);
                    break;
                default:
                    $count_type_file['all']++;
                    $n = substr($count_type_file['all'], 1, 4);
            }
            $filename = $nomeFile . '(' . $n . ').' . $ext_file;

            $file = new UploadedFile(
                $tmpFileObjectPathName,
                $tmpFileObject->getFilename(),
                $tmpFileObject->getMimeType(),
                0,
                true
            );

            $fileDrive = GoogleDrive::add_file($path, $filename, $file, true, 'google');
            unlink($tmpFileObjectPathName); // delete temp file

            if (!$fileDrive) {
                Log::channel('stderr')->error("QtTypeTest saveFile: GoogleDrive::add_file fallito per '{$filename}' nella cartella '{$path}'");
            } else {
                Log::channel('stderr')->info("QtTypeTest saveFile: file caricato con successo su Google Drive: '{$filename}' (ID: {$fileDrive})");
            }

            return $fileDrive;

        }
    }


    private function validateBase64(string $base64data, array $allowedMimeTypes)
    {
        // strip out data URI scheme information (see RFC 2397)
        if (str_contains($base64data, ';base64')) {
            list(, $base64data) = explode(';', $base64data);
            list(, $base64data) = explode(',', $base64data);
        }

        // strip whitespace/newlines
        $base64data = preg_replace('/\s+/', '', $base64data);

        // strict mode filters for non-base64 alphabet characters
        if (base64_decode($base64data, true) === false) {
            return false;
        }

        $fileBinaryData = base64_decode($base64data);
        if ($fileBinaryData === false) {
            return false;
        }

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
	
	public function report_tipo(Request $request)
    {

        $dataBy = $request->get('data');
        $materialeBy = $request->get('materiale');
        $olBy = $request->get('ol');
        $tipologiaBy = $request->get('tipologia');
        $esitoBy = $request->get('esito');
        $standardaBy = $request->get('standard');
        $specificaBy = $request->get('specifica');

        $objs = DB::table('qt_type_tests')
            ->select(DB::raw('count(*) as totale'),'qt_categories.categoria')
            ->leftJoin('qt_categories','qt_categories.id','qt_type_tests.tipo')
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('materiale', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($olBy) {
                if ($olBy)
                    $query->Where('ol', 'LIKE', '%' . $olBy . '%');
            })
            ->Where(function ($query) use ($esitoBy) {
                if ($esitoBy)
                    $query->Where('esito', $esitoBy);
            })
            ->Where(function ($query) use ($tipologiaBy) {
                if ($tipologiaBy)
                    $query->Where('tipo', $tipologiaBy);
            })
            ->Where(function ($query) use ($standardaBy) {
                if ($standardaBy)
                    $query->Where('standard', $standardaBy);
            })
            ->Where(function ($query) use ($specificaBy) {
                if ($specificaBy)
                    $query->Where('specifica', $specificaBy);
            })
            ->Where(function ($query) use ($dataBy) {
                if (is_string($dataBy)) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2){
                        $query->whereBetween('data_prova', $dataBy);
                    }
                    else{
                        $query->whereDate('data_prova', $dataBy[0]);
                    }
                }
            })
            ->groupBy('categoria')
            ->get();

        return response()->json($objs);
    }
}
