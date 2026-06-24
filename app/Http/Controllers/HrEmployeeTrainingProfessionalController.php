<?php

namespace App\Http\Controllers;

use App\Models\HrEmployee;
use App\Models\HrEmployeeTrainingProfessional;
use App\Models\HrTraining;
use App\Models\LogActivity;
use App\Services\GoogleDrive;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class HrEmployeeTrainingProfessionalController extends Controller
{
    public function list(Request $request)
    {

        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $dipendneteBy = $request->get('dipendente');
        $formazioneBy = $request->get('formazione');


        if (empty($sortByName)) {
            $sortByName = 'created_at';
            $orderBy = 'desc';
        }

        $objs = HrEmployeeTrainingProfessional::with('training')
            ->where(function ($query) use ($dipendneteBy) {
                if ($dipendneteBy)
                    $query->where('employee_id', $dipendneteBy);
            })
            ->where(function ($query) use ($formazioneBy) {
                if ($formazioneBy) {
                    $query->where('formazione', 'LIKE', '%' . $formazioneBy . '%')
                          ->orWhereHas('training', function ($q) use ($formazioneBy) {
                              $q->where('formazione', 'LIKE', '%' . $formazioneBy . '%');
                          });
                }
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function store(Request $request)
    {
        $employee = HrEmployee::find($request->employee_id);
        $obj = new HrEmployeeTrainingProfessional();
        $obj->employee_id = $request->employee_id;

        if ($request->formazione_id) {
            $obj->formazione_id = $request->formazione_id;
            if (empty($request->formazione)) {
                $training = HrTraining::find($request->formazione_id);
                $obj->formazione = $training ? $training->formazione : null;
            } else {
                $obj->formazione = ucwords(strtolower($request->formazione));
            }
        } else {
            $obj->formazione = ucwords(strtolower($request->formazione));
        }

        $obj->data_formazione = $request->data_formazione;
        $obj->tipologia =  $request->tipologia;
        $obj->path_drive = GoogleDrive::add_folder([$employee->path_drive], $obj->formazione, 'google', true);

        if (!empty($obj->path_drive) && !empty($request->file['file'])){
            $name_file = '';
            switch ($request->tipologia) {
                case 1:
                    $name_file = 'Richiesta';
                    break;
                case 2:
                    $name_file = 'Attestato';
                    break;
                case 3:
                    $name_file = 'Valutazione';
                    break;
                case 4:
                    $name_file = 'Verbale';
                    break;
            }
            $this->saveFile($request->file['file'], $obj->path_drive,$name_file);
        }

        $obj->utente_id = Auth::id();
        $obj->save();

        $message = 'Messaggi.Formazione-Agginta';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function upload(Request $request)
    {
        $obj = HrEmployeeTrainingProfessional::find($request->id);
        if (!empty($obj->path_drive) && !empty($request->file_upload['file'])){
            $name_file = '';
            switch ($request->type) {
                case 1:
                    $name_file = 'Richiesta';
                    break;
                case 2:
                    $name_file = 'Attestato';
                    break;
                case 3:
                    $name_file = 'Valutazione';
                    break;
                case 4:
                    $name_file = 'Verbale';
                    break;
            }
            $this->saveFile($request->file_upload['file'], $obj->path_drive,$name_file);
        }

        $message = 'Messaggi.Documento-Caricato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );

    }

    public function deleted($id)
    {
        $obj =  HrEmployeeTrainingProfessional::find($id)->delete();

        $message = 'Messaggi.Formazione-Eliminata';
        $color = 'success';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => $color,
            ]);
    }

    private function saveFile($file, $path, $nomeFile = 'screenshot')
    {
        if (!empty($file)) {
            $base64Image = $file;

            if (!$tmpFileObject = $this->validateBase64($base64Image, ['png', 'jpg', 'jpeg', 'pdf'])) {
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

            $fileDrive = GoogleDrive::add_file($path, $nomeFile, $file, true, null);

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
}
