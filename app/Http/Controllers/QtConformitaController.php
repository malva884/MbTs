<?php

namespace App\Http\Controllers;

use App\Exports\ConformitaExport;
use App\Jobs\NonConformita;
use App\Models\LogActivity;
use App\Models\QtCheckerReport;
use App\Models\QtConformita;
use App\Models\QtConformitaApp;
use App\Services\GoogleDrive;

//use Google\Service\Storage;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class QtConformitaController extends Controller
{
    public function index(Request $request)
    {

        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $ordineBy = $request->get('ordine');
        $materialeBy = $request->get('materiale');
        $difettoBy = $request->get('difetto');
        $macchinaBy = $request->get('macchina');

        if (empty($sortByName)) {
            $sortByName = 'data_apertura';
            $orderBy = 'desc';
        }
        $objs = DB::table('qt_conformitas')->select('qt_conformitas.*', 'users.full_name', 'machineries.nome as macchina_nome', 'defects.difetto as difetto_nome')
            ->join('users', 'users.id', 'qt_conformitas.user')
            ->join('machineries', 'machineries.id', 'qt_conformitas.macchina')
            ->join('defects', 'defects.id', 'qt_conformitas.difetto')
            ->Where(function ($query) use ($ordineBy) {
                if ($ordineBy)
                    $query->Where('ol', 'LIKE', '%' . $ordineBy . '%');
            })
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('materiale', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($difettoBy) {
                if ($difettoBy)
                    $query->Where('defects.id', '=', $difettoBy);
            })
            ->Where(function ($query) use ($macchinaBy) {
                if ($macchinaBy)
                    $query->Where('machineries.id', '=', $macchinaBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function view($id)
    {
        $obj = DB::table('qt_conformitas')
            ->Where(function ($query) use ($id) {
                $query->where('id', $id)->orWhere('report_id', $id);
            })->first();

        return response()->json($obj);
    }

    public function store(Request $request)
    {
        // Recupero l'ultima Non Conformità inserita
        $lastRecord = QtConformita::where('anno', date('Y'))->orderBy('created_at', 'desc')->first();
        if (empty($lastRecord->numero))
            $numero = '00001';
        else {
            $numero = date('Y') . $lastRecord->numero;
            $numero = $numero + 1;
            $numero = substr($numero, -5);
        }
        $obj = new QtConformita();
        if (!empty($request->report_id)) {
            $obj->report_id = $request->report_id;
            $obj->ftr_ottico = true;
        }
        $obj->user = Auth::id();
        $obj->data_apertura = date('Y-m-d H:i:s');
        $obj->ol = $request->ol;
        if (!empty($request->num_fo))
            $obj->num_fo = $request->num_fo;
        $obj->stage = $request->stage;
        $obj->materiale = $request->materiale;
        $obj->bobina = $request->bobina;
        $obj->note = $request->note;
        $obj->macchina = $request->macchina;
        $obj->difetto = $request->difetto;
        $obj->fibre = $request->fibre;
        $obj->soluzione = $request->soluzione;
        $obj->diametro = $request->diametro;
        if (!empty($request->tipologia_fibra))
            $obj->tipologia_fibra = $request->tipologia_fibra;
        if (!empty($request->operator))
            $obj->operator = $request->operator;
       // $obj->physical_l = $request->physical_l;
        $obj->optical_l = $request->optical_l;
        if (!empty($request->tipologia_difetto))
            $obj->tipologia_difetto = $request->tipologia_difetto;
        $obj->anno = date('Y');
        $obj->numero = $numero;
        // Creo La cartella della Non Conformità su Drive
        $obj->google_drive_id = GoogleDrive::add_folder(env('ID_GOOGLE_NC_GIORNALIENRE'), $obj->ol . '-' . $obj->bobina, 'google', false);
        // carico il file nella cartella creata precendentemente.
        if (!empty($request->file_upload['file']))
            $this->saveImage($request->file_upload['file'], $obj->google_drive_id);
        // ottico o rame
        $tmp = substr($obj->materiale, 1, 2);
        if (is_numeric($tmp) && substr($obj->materiale, 0, 2) == 'F8')
            $obj->rame = true;
        else
            $obj->ottico = true;
        $obj->save();
        // se ho l'id del rapportino checker aggiorno l'attibuto not_conformity a 1 che indica che la non conformita è aperta.
        if ($obj->report_id) {
            $reportChecker = QtCheckerReport::find($obj->report_id);
            $reportChecker->not_conformity = 1;
            $reportChecker->save();
        }
        // se il difetto e diverso da BDS metto in coda l'inivio della notifica email
        if ($obj->defect->difetto != 'BDS')
            dispatch(new NonConformita($obj->id, 'Apertura Non Conformità', 1));
        $message = 'Messaggi.Non Conformita Aperta';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'objs' => $obj
            ]
        );
    }

    public function update(Request $request, $id)
    {
        $obj = QtConformita::find($id);
        $obj->user = Auth::id();
        $obj->note = $request->note;
        $obj->macchina = $request->macchina;
        $obj->difetto = $request->difetto;
        $obj->fibre = $request->fibre;
        $obj->soluzione = $request->soluzione;
        $obj->diametro = $request->diametro;
        if (!empty($request->tipologia_fibra))
            $obj->tipologia_fibra = $request->tipologia_fibra;
        if (!empty($request->operator))
            $obj->operator = $request->operator;
        $obj->physical_l = $request->physical_l;
        $obj->optical_l = $request->optical_l;
        if (!empty($request->tipologia_difetto))
            $obj->tipologia_difetto = $request->tipologia_difetto;
        $obj->save();

        $message = 'Messaggi.Non Conformita Modificata';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'objs' => $obj
            ]
        );
    }

    public function closed(Request $request, $id)
    {

        try {
            $obj = QtConformita::find($id);
            DB::transaction(function () use ($request, $id, $obj) {
                $motivazioni = [
                    1 =>    'Prodotto conforme',
                    2 =>    'NC risolta in reworking',
                    3 =>    'Deroga da parte del cliente',
                    4 =>    'Deroga interna',
                ];
                $obj->stato = $request->stato;
                $obj->soluzione = $request->soluzione;
                if($request->stato == 3){
                    $obj->data_chiusura = Date('Y-m-d H:i:s');
                    $diff = strtotime($obj->data_apertura . " UTC") - strtotime($obj->data_chiusura . " UTC");
                    $obj->time = $diff;
                    $obj->motivazione_chiusura = $request->motivazione;
                    $obj->motivazione_chiusura_text = $motivazioni[$request->motivazione];
                }
                if($request->stato == 1)
                    $obj->soluzione = '';
                $obj->save();
                // se ho l'id del rapportino checker aggiorno l'attibuto not_conformity a 2 che indica che la non conformita è chiusa.
                if ($obj->report_id) {
                    $reportChecker = QtCheckerReport::find($obj->report_id);
                    $reportChecker->not_conformity = $request->stato;
                    $reportChecker->save();
                }

                if ($request->stato == 2) {
                    $approvazione = new QtConformitaApp();
                    $approvazione->conformitas_id = $obj->id;
                    $approvazione->user_soluzione = Auth::id();
                    $approvazione->soluzione = $request->soluzione;
                    $approvazione->data_soluzione = date('Y-m-d H:i:s');
                    $approvazione->save();

                } else {
                    $approvazione = QtConformitaApp::where('conformitas_id', $obj->id)->whereNull('esito')->first();
                    $approvazione->nota_approvazione = $request->approvazione;
                    $approvazione->data_approvazione = date('Y-m-d H:i:s');
                    $approvazione->user_approvazione = Auth::id();
                    $approvazione->esito = $request->stato;
                    $approvazione->save();

                }
            });

            if($request->stato == 2){
                // metto in coda l'inivio della notifica email
                dispatch(new NonConformita($obj->id, 'Approvazione Chiusura Non Conformità', $request->stato));
                $message = 'Messaggi.Richiesta-Inviata';
            }else{
                if ($request->stato == 3) {
                    $message = 'Messaggi.Non Conformita Chiusa';
                    // metto in coda l'inivio della notifica email
                    dispatch(new NonConformita($obj->id, 'Chiusura Non Conformità', $request->stato, $request->nota_approvazione));
                } else {
                    // metto in coda l'inivio della notifica email
                    dispatch(new NonConformita($obj->id, 'Riapertura Non Conformità', $request->stato, $request->nota_approvazione));
                    $message = 'Messaggi.Non-Conformita-Riaperta';

                }
            }

        } catch (\Exception $e) {
            $message = 'Messaggi.Errore-Interno';

        }

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'objs' => $obj
            ]
        );
    }

    public function deleted($id)
    {
        $obj = QtConformita::find($id);
        $message = 'Messaggi.Errore-Eliminazione-Non-Conformita';
        $color = 'error';
        $success = false;
        // se la non conformità e stata aperta tramite un rapportino checker aggiorno l'attibuto not_conformity a 0 che indica che non eiste nessuna non conoformità.
        if ($obj->report_id) {
            $reportChecker = QtCheckerReport::find($obj->report_id);
            $reportChecker->not_conformity = 0;
            $reportChecker->save();
        }
        // Rinomino La Cartella Driver Aggiungendo (ELIMINATO)
        GoogleDrive::rename_dir($obj->google_drive_id, $obj->ol . '-' . $obj->bobina . ' ( ELIMINATO )');
        $obj->delete();
        $message = 'Messaggi.Non-Conformita-Eliminata';
        $color = 'success';
        $success = true;

        $text = '
        <h6 class="font-weight-medium text-sm">Ol: ' . $obj->ol . '</h6>
        <h6 class="font-weight-medium text-sm">Bobina: ' . $obj->bobina . '</h6>
        <h6 class="font-weight-medium text-sm">Difetto: ' . $obj->defect->difetto . '</h6>
        <h6 class="font-weight-medium text-sm">Linea: ' . $obj->macchinary->nome . '</h6>
        <h6 class="font-weight-medium text-sm">Data: ' . $obj->data_apertura . '</h6>
        <h6 class="font-weight-medium text-sm">Numero: ' . $obj->numero . '</h6>
        ';
        LogActivity::addToLog('Non Conformità Eliminato', ['text' => $text], 'error', 'deleted');
        return response()->json(
            [
                'success' => $success,
                'message' => $message,
                'color' => $color,
            ]
        );

    }

    private function saveImage($file, $path, $nomeFile = 'screenshot')
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

    public function get_attivita($id)
    {

        $obj = DB::table('qt_conformita_apps')->select('qt_conformita_apps.*','users.full_name as user_s','b.full_name as user_a')
            ->join('users', 'users.id','qt_conformita_apps.user_soluzione')
            ->join('users as b', 'b.id','qt_conformita_apps.user_approvazione')
            ->where('conformitas_id',$id)
            ->orderBy('created_at')
            ->get();

        return response()->json($obj);
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

    public function export(Request $request)
    {
        $name_file = date('dmY').'.xlsx';

        return Excel::download(new ConformitaExport, $name_file);

    }

}
