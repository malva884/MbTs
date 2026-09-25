<?php

namespace App\Http\Controllers;

use App\Exports\ConformitaExport;
use App\Jobs\NonConformita;
use App\Models\LogActivity;
use App\Models\QtCheckerReport;
use App\Models\QtConformita;
use App\Models\QtConformitaApp;
use App\Services\GoogleDrive;
use App\Services\SettingService;

//use Google\Service\Storage;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
		$periodo = $request->get('periodo');

        if (empty($sortByName)) {
            $sortByName = 'data_apertura';
            $orderBy = 'desc';
        }
        $objs = DB::table('qt_conformitas')->select('qt_conformitas.*', 'users.full_name', 'machineries.nome as macchina_nome', 'defects.difetto as difetto_nome')
            ->leftJoin('users', 'users.id', 'qt_conformitas.user')
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
			->Where(function ($query) use ($periodo) {
                if ($periodo) {
                    $periodo = explode(' to ', $periodo);
                    if (count($periodo) == 2)
                        $query->whereBetween('data_apertura', [$periodo[0].' 00:00:00', $periodo[1].' 23:59:59']);
                    else
                        $query->whereDate('data_apertura', '=', $periodo[0]);
                        //$query->where('data_apertura', '<=', $periodo[0].' 24:59:59:999');

                }

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
        $obj->physical_l = $request->physical_l;
        $obj->optical_l = $request->optical_l;
        if (!empty($request->tipologia_difetto))
            $obj->tipologia_difetto = $request->tipologia_difetto;
        if (!empty($request->provenienza_fibra))
            $obj->provenienza_fibra = $request->provenienza_fibra;
        $obj->anno = date('Y');
        $obj->numero = $numero;
        // Creo La cartella della Non Conformità su Drive
        $settingService = new SettingService();
        $ncGiornaliereFolderId = $settingService->get('google_drive_nc_giornaliere_folder_id');
        if (empty($ncGiornaliereFolderId)) {
            $obj->google_drive_id = null;
            $this->notifyDriveError($obj, "Setting 'google_drive_nc_giornaliere_folder_id' non configurato");
        } else {
            $folderId = GoogleDrive::add_folder([$ncGiornaliereFolderId], $obj->ol . '-' . $obj->bobina, 'google', false);
            if (!is_string($folderId) || strlen($folderId) < 10) {
                $errore = GoogleDrive::$lastError ?? 'add_folder ritorno anomalo: ' . var_export($folderId, true);
                $obj->google_drive_id = null;
                $this->notifyDriveError($obj, $errore);
            } else {
                $obj->google_drive_id = $folderId;
            }
        }
        // carico il file nella cartella creata precendentemente.
        if (!empty($request->file_upload['file']) && !empty($obj->google_drive_id)) {
            $ext = $request->file_upload['fileExtension'] ?? 'jpg';
            $nomeFile = pathinfo($request->file_upload['fileName'] ?? 'screenshot', PATHINFO_FILENAME);
            $this->saveFile($request->file_upload['file'], $obj->google_drive_id, $ext, $nomeFile);
        }
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
        if (!empty($request->provenienza_fibra))
            $obj->provenienza_fibra = $request->provenienza_fibra;
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
					5 =>    'Scarto',
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
					if ($obj->defect->difetto != 'BDS')
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

    private function saveFile($file, $path, $ext_file, $nomeFile = null)
    {
        if (empty($path)) {
            Log::channel('stderr')->error("QtConformita saveFile: path cartella Google Drive non valido o vuoto!");
            return false;
        }

        if (!empty($file)) {
            $base64Image = $file;

            if (!$tmpFileObject = $this->validateBase64($base64Image, ['png', 'jpg', 'jpeg', 'HEIC', 'pdf'])) {
                Log::channel('stderr')->error("QtConformita saveFile: formato base64 o mime-type non valido per estensione .{$ext_file}");
                return false;
            }

            $count_type_file = ['word' => 1000, 'img' => 1020, 'all' => 1100];
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
                            if ($n >= substr($count_type_file['word'], 0, 1)){
                                if($t == $n)
                                    $count_type_file['word'] = '1' . $n;
                            }
                            break;
                        case 'jpg':
                        case 'jpeg':
                        case 'png':
                        case 'heic':
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

            switch (strtolower($ext_file)) {
                case 'pdf':
                    $count_type_file['word']++;
                    $n = substr($count_type_file['word'], 1, 4);
                    break;
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'heic':
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
                Log::channel('stderr')->error("QtConformita saveFile: GoogleDrive::add_file fallito per '{$filename}' nella cartella '{$path}'");
            } else {
                Log::channel('stderr')->info("QtConformita saveFile: file caricato con successo su Google Drive: '{$filename}' (ID: {$fileDrive})");
            }

            return $fileDrive;

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

    public function export(Request $request)
    {
        $name_file = date('dmY').'.xlsx';

        $materiale = ($request->materiale && $request->materiale !== 'undefined') ? $request->materiale : null;
        $ol = ($request->ol && $request->ol !== 'undefined') ? $request->ol : null;
        $difetto = ($request->difetto && $request->difetto !== 'undefined') ? $request->difetto : null;
        $linea = ($request->linea && $request->linea !== 'undefined') ? $request->linea : null;
        $periodo = ($request->periodo && $request->periodo !== 'undefined') ? $request->periodo : null;

        $export = new ConformitaExport($materiale, $ol, $difetto, $linea, $periodo);
        return Excel::download($export, $name_file);

    }

    public function publicMachine($machine)
    {
        $query = DB::table('machineries')
            ->select('id', 'nome', 'name_gp', 'id_gp', 'categoria')
            ->where('nome', $machine)
            ->orWhere('name_gp', $machine);

        if (is_numeric($machine))
            $query->orWhere('id', $machine);

        $obj = $query->first();

        if (!$obj)
            return response()->json(['success' => false, 'message' => 'Macchina non trovata'], 404);

        return response()->json(['success' => true, 'data' => $obj]);
    }

    public function publicMachineData($machine)
    {
        $machineRecord = DB::table('machineries')
            ->select('id', 'nome', 'id_gp')
            ->where('nome', $machine)
            ->orWhere('name_gp', $machine);

        if (is_numeric($machine))
            $machineRecord->orWhere('id', $machine);

        $machineRecord = $machineRecord->first();

        if (!$machineRecord || empty($machineRecord->id_gp))
            return response()->json(['success' => false, 'message' => 'Macchina senza dati 4.0'], 404);

        $macchinaId = $machineRecord->id_gp;

        $info = DB::connection('sqlsrv_root_gp')
            ->table('STL_Info_Ordine_V')
            ->where('MacchinaId', $macchinaId)
            ->orderBy('DataMisurazione', 'desc')
            ->first();

        if (!$info) {
            $info = DB::connection('sqlsrv_root_gp')
                ->table('STL_Info_Ordine_V2')
                ->where('MacchinaId', $macchinaId)
                ->orderBy('AP_DataOraInizio', 'desc')
                ->first();
        }

        if (!$info)
            return response()->json(['success' => false, 'message' => 'Nessun dato 4.0 trovato'], 404);

        $velocita = DB::connection('sqlsrv_root_gp')
            ->table('STL_Info_Ordine_V')
            ->where('MacchinaId', $macchinaId)
            ->where('Caratteristica', 'Velocità Linea')
            ->orderBy('DataMisurazione', 'desc')
            ->value('ValoreMisurato');

        $metri = DB::connection('sqlsrv_root_gp')
            ->table('STL_Info_Ordine_V')
            ->where('MacchinaId', $macchinaId)
            ->where('Caratteristica', 'Metri Prodotti')
            ->orderBy('DataMisurazione', 'desc')
            ->value('ValoreMisurato');

        $data = [
            'ol' => $info->Ordine ?? '',
            'prodotto' => $info->Prodotto ?? '',
            'operatore' => $info->OperatoreScheda ?? '',
            'macchina' => $info->Macchina ?? $machineRecord->nome,
            'velocita_linea' => $velocita !== null ? round((float) $velocita, 2) : null,
            'metri_prodotti' => $metri !== null ? round((float) $metri, 2) : null,
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function publicDefects()
    {
        $objs = DB::table('defects')
            ->where('attivo', true)
            ->orderBy('difetto')
            ->get();

        return response()->json(['success' => true, 'data' => $objs]);
    }

    private function notifyDriveError(QtConformita $obj, string $errore): void
    {
        Log::channel('stderr')->error("Drive NC folder error ({$obj->ol}-{$obj->bobina}): {$errore}");
        try {
            Mail::raw(
                "Creazione cartella Google Drive fallita per la Non Conformita {$obj->ol}-{$obj->bobina} (anno {$obj->anno}).\n\nErrore: {$errore}",
                function ($message) {
                    $message->to('gregorio.grande@stl.tech')
                            ->subject('Errore creazione cartella Drive - Non Conformita');
                }
            );
        } catch (\Exception $e) {
            Log::error('Invio mail errore Drive fallito: ' . $e->getMessage());
        }
    }

    public function publicStore(Request $request)
    {
        $validated = $request->validate([
            'ol' => 'required|string',
            'macchina' => 'required|string',
            'difetto' => 'required|string',
            'note' => 'nullable|string',
            'materiale' => 'nullable|string',
            'stage' => 'nullable|string',
            'operator' => 'nullable|string',
            'fibre' => 'nullable|string',
            'provenienza_fibra' => 'nullable|string',
        ]);

        $machineQuery = DB::table('machineries')
            ->where('nome', $validated['macchina'])
            ->orWhere('name_gp', $validated['macchina']);

        if (is_numeric($validated['macchina']))
            $machineQuery->orWhere('id', $validated['macchina']);

        $machine = $machineQuery->first();

        if (!$machine)
            return response()->json(['success' => false, 'message' => 'Macchina non trovata'], 404);

        $lastRecord = QtConformita::where('anno', date('Y'))->orderBy('created_at', 'desc')->first();
        if (empty($lastRecord->numero))
            $numero = '00001';
        else {
            $numero = date('Y') . $lastRecord->numero;
            $numero = $numero + 1;
            $numero = substr($numero, -5);
        }

        $materiale = $validated['materiale'] ?? '';

        // Estrai solo la parte prima dello slash dall'OL (es. 90062231/0010 -> 90062231)
        $ol = $validated['ol'];
        if (strpos($ol, '/') !== false) {
            $ol = explode('/', $ol)[0];
        }

        $obj = new QtConformita();
        $obj->user = config('nc.default_user_id', 5);
        $obj->data_apertura = date('Y-m-d H:i:s');
        $obj->ol = $ol;
        $obj->bobina = $validated['bobina'] ?? '';
        $obj->macchina = $machine->id;
        $obj->difetto = $validated['difetto'];
        $obj->note = $validated['note'] ?? '';
        $obj->materiale = $materiale;
        $obj->stage = $validated['stage'] ?? '';
        $obj->operator = $validated['operator'] ?? '';
        $obj->fibre = $validated['fibre'] ?? '';
        $obj->provenienza_fibra = $validated['provenienza_fibra'] ?? '';
        $obj->anno = date('Y');
        $obj->numero = $numero;
        $obj->google_drive_id = '';
        $obj->stato = 3;
        $obj->data_chiusura = date('Y-m-d H:i:s');
        $diff = strtotime($obj->data_apertura . " UTC") - strtotime($obj->data_chiusura . " UTC");
        $obj->time = $diff;

        $tmp = substr($materiale, 1, 2);
        if (is_numeric($tmp) && substr($materiale, 0, 2) == 'F8')
            $obj->rame = true;
        else
            $obj->ottico = true;

        $obj->save();

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Non Conformita Inserita',
            'color' => 'success',
            'objs' => $obj,
        ]);
    }

}
