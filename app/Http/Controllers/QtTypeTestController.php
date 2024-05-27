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
        $obj->user = Auth::id();
        $category = $obj->categoriaTipo->categoria;
        $obj->data_prova = $request->data_prova;
        $obj->save();
        if ($category == 'TTM' || $category == 'TTC' || $category == 'TR' || $category == 'TC')
            $name = $obj->standard;
        else
            $name = date('Y');

        $idFolder[0] = GoogleDrive::search($obj->categoriaTipo->id_drive, 'google', 'dir', $name);

        if (!$idFolder[0])
            $idFolder[0] = GoogleDrive::add_folder(array($obj->categoriaTipo->id_drive), $name, 'google', false);

        $name_folder = $obj->ol . '-' . $obj->materiale;

        if (!empty($idFolder[0]['basename']))
            $idFolder[0] = $idFolder[0]['basename'];

        $idFolder[1] = GoogleDrive::search($idFolder[0], 'google', 'dir', $name_folder);

        if (!$idFolder[1])
            $idFolder[1] = GoogleDrive::add_folder(array($idFolder[0]), $name_folder);

        if (!empty($idFolder[1]['basename']))
            $idFolder[1] = $idFolder[1]['basename'];

        foreach ($request->files_upload as $file)
            $this->saveFile($file['file'], $idFolder[1], $file['fileExtension'], $name_folder);

        $obj->path_drive = $idFolder[1];
        $obj->save();

        //if (!empty($obj->fai))
        //GoogleDrive::shortcut('14JT0qf5yT5URuzxSgygmSBUDWengksRx0ndUOjPeuhQ', $idFolder[1], 'ELENCO FAI');
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
        if (!empty($file)) {
            $base64Image = $file;


            if (!$tmpFileObject = $this->validateBase64($base64Image, ['png', 'jpg', 'jpeg', 'pdf'])) {
                return response()->json([
                    'error' => 'Invalid image format.'
                ], 415);
            }

            $count_type_file = ['word' => 1000, 'exls' => 1010, 'img' => 1020, 'all' => 1100];
            $files = GoogleDrive::search($path, 'google', 'files', null);

            $tmpFileObjectPathName = $tmpFileObject->getPathname();
            $t = 1;
            foreach (json_decode($files, true) as $file) {

                $ext = explode(".", $file['name']);
                $tmp = explode("(", $ext[0]);
                $n = substr($tmp[1], 0, -1);
                if($n > $t){ $t = $n; }
                switch ($ext[1]) {
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

            //$exst = $ext_file;
            switch ($ext_file) {
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

            return $fileDrive['id'];

        }
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
