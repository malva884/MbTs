<?php

namespace App\Http\Controllers;

use App\Jobs\WfLogProcedure;
use App\Models\WfCategory;
use App\Models\WfDocument;
use App\Models\WfProcedure;
use App\Models\WfProcedureCertification;
use App\Models\WfProcedureOffice;
use App\Models\WfUser;
use App\Models\WfUserApproval;
use App\Services\GoogleDrive;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class WfProcedureController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $certificatiBy = json_decode($request->get('certificati'), true);
        $ufficiBy = json_decode($request->get('uffici'), true);

        $is_approver = WfUser::select('id','approval_start_date')->where('model',WfProcedure::$modelName)->where('user_id',Auth::id())->where('disabled',false)->first();

        if (empty($sortByName)) {
            $sortByName = 'wf_procedures.created_at';
            $orderBy = 'asc';
        }


        $objs = DB::table('wf_procedures')
            ->join('wf_categories','wf_procedures.processo_id','wf_categories.id',)
            ->select('wf_categories.categoria','wf_procedures.descrizione','wf_procedures.id','wf_procedures.revisione_anno','wf_procedures.revisione','wf_procedures.folder_drive_padre')
            ->where('tipologia',1)
			->Where(function ($query) use ($certificatiBy) {
                if ($certificatiBy)
                    $query->whereIn('wf_procedures.id', function($query) use ($certificatiBy){
                        if($certificatiBy)
                            $query->select('procedura_id')
                                ->from('wf_procedure_certifications')
                                ->whereIn('cartificazione_id', array_values($certificatiBy));
                    });
            })
            ->Where(function ($query) use ($ufficiBy) {
                if ($ufficiBy)
                    $query->whereIn('wf_procedures.id', function($query) use ($ufficiBy){
                        if($ufficiBy)
                            $query->select('procedura_id')
                                ->from('wf_procedure_offices')
                                ->whereIn('ufficio_id', array_values($ufficiBy));
                    });
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        return response()->json(['objs' => $objs, 'is_approver' => !empty($is_approver->id) ]);
    }

    public function view($id)
    {

        $is_approver = WfUser::select('id','approval_start_date')->where('model',WfProcedure::$modelName)->where('user_id',Auth::id())->where('disabled',false)->first();

        $obj = DB::table('wf_procedures')
            ->join('wf_categories','wf_procedures.processo_id','wf_categories.id')
            ->leftJoin('wf_user_approvals', function($join)
            {
                $join->on('wf_procedures.id', '=', 'wf_user_approvals.model_id');
                $join->where('wf_user_approvals.user_id','=',Auth::id());
            })
            ->select('wf_procedures.*','wf_categories.categoria as processo','wf_user_approvals.approval_action')
            ->where('wf_procedures.id',$id)
            ->first();

        $uffici = DB::table('wf_procedure_offices')
            ->join('wf_offices','wf_procedure_offices.ufficio_id','wf_offices.id')
            ->select('ufficio')
            ->where('procedura_id',$id)
            ->get();

        $certificati = DB::table('wf_procedure_certifications')
            ->join('wf_certifications','wf_procedure_certifications.cartificazione_id','wf_certifications.id')
            ->select('certificazione')
            ->where('procedura_id',$id)
            ->get();

        return response()->json(['obj' => $obj, 'uffici' => $uffici, 'certificati' => $certificati, 'is_approver' => !empty($is_approver->id)]);

    }

    public function allegati(Request $request, $id)
    {
		$certificatiBy = json_decode($request->get('certificati'), true);
        $ufficiBy = json_decode($request->get('uffici'), true);
		
        $is_approver = WfUser::select('id','approval_start_date')->where('model',WfProcedure::$modelName)->where('user_id',Auth::id())->where('disabled',false)->first();

        $moduli = DB::table('wf_procedures');
        if(!empty($is_approver->id))
            $moduli = $moduli->select('wf_procedures.*','wf_user_approvals.approval_action');
        else
            $moduli = $moduli->select('wf_procedures.*');
		
		$moduli = $moduli->Where(function ($query) use ($certificatiBy, $id) {
            if ($certificatiBy){
                $query->whereIn('wf_procedures.id', function($query) use ($certificatiBy, $id){
                    if($certificatiBy)
                        $query->select('procedura_id')
                            ->from('wf_procedure_certifications')
                            ->leftJoin('wf_procedures','wf_procedure_certifications.procedura_id','wf_procedures.id')
                            ->where('wf_procedures.padre_id',$id)
                            ->whereIn('cartificazione_id', array_values($certificatiBy));
                });
            }
        });

        $moduli = $moduli->Where(function ($query) use ($ufficiBy, $id) {
            if ($ufficiBy){
                $query->whereIn('wf_procedures.id', function($query) use ($ufficiBy, $id){
                    if($ufficiBy)
                        $query->select('procedura_id')
                            ->from('wf_procedure_offices')
                            ->leftJoin('wf_procedures','wf_procedure_offices.procedura_id','wf_procedures.id')
                            ->where('wf_procedures.padre_id',$id)
                            ->whereIn('ufficio_id', array_values($ufficiBy));
                });
            }
        });
		
        $moduli = $moduli->where('padre_id',$id);
        $moduli = $moduli->where('tipologia',2)->whereNull('sup');
        if(!empty($is_approver->id)){
            $moduli = $moduli->leftJoin('wf_user_approvals', function($join)
            {
                $join->on('wf_procedures.id', '=', 'wf_user_approvals.model_id');
                $join->where('wf_user_approvals.user_id','=',Auth::id());
            })
                ->whereDate('wf_procedures.created_at','>=', $is_approver->approval_start_date);
        }
        $moduli = $moduli->orderBy('procedura')->get();

        $istruzioni = DB::table('wf_procedures');
        if(!empty($is_approver->id))
            $istruzioni = $istruzioni->select('wf_procedures.*','wf_user_approvals.approval_action');
        else
            $istruzioni = $istruzioni->select('wf_procedures.*');
		
		$istruzioni = $istruzioni->Where(function ($query) use ($certificatiBy, $id) {
            if ($certificatiBy){
                $query->whereIn('wf_procedures.id', function($query) use ($certificatiBy, $id){
                    if($certificatiBy)
                        $query->select('procedura_id')
                            ->from('wf_procedure_certifications')
                            ->leftJoin('wf_procedures','wf_procedure_certifications.procedura_id','wf_procedures.id')
                            ->where('wf_procedures.padre_id',$id)
                            ->whereIn('cartificazione_id', array_values($certificatiBy));
                });
            }
        });

        $istruzioni = $istruzioni->Where(function ($query) use ($ufficiBy, $id) {
            if ($ufficiBy){
                $query->whereIn('wf_procedures.id', function($query) use ($ufficiBy, $id){
                    if($ufficiBy)
                        $query->select('procedura_id')
                            ->from('wf_procedure_offices')
                            ->leftJoin('wf_procedures','wf_procedure_offices.procedura_id','wf_procedures.id')
                            ->where('wf_procedures.padre_id',$id)
                            ->whereIn('ufficio_id', array_values($ufficiBy));
                });
            }
        });

        $istruzioni = $istruzioni->where('padre_id',$id);
        $istruzioni = $istruzioni->whereIN('tipologia',[3,4])->whereNull('sup');
        if(!empty($is_approver->id)){
            $istruzioni = $istruzioni->leftJoin('wf_user_approvals', function($join)
            {
                $join->on('wf_procedures.id', '=', 'wf_user_approvals.model_id');
                $join->where('wf_user_approvals.user_id','=',Auth::id());
            })
                ->whereDate('wf_procedures.created_at','>=', $is_approver->approval_start_date);
        }
        $istruzioni = $istruzioni->orderBy('procedura')->get();


        return response()->json(['moduli'=> $moduli, 'istruzioni'=>$istruzioni, 'is_approver' => !empty($is_approver->id)]);

    }
	
	public function stored(Request $request)
    {

        $base64File = $request->file_upload['file'] ?? null;

        // Validazione del file base64 con estensioni consentite
        if (!$tmpFileObject = $this->validateBase64($base64File, ['xls', 'xlsx', 'pdf'])) {
            return response()->json([
                'error' => 'Formato file non valido (Ammessi: PDF, XLS, XLSX).'
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

        // Recupero della categoria
        $category = WfCategory::find($request['categoria_id']);

        // SICUREZZA 1: Verifichiamo che la categoria abbia effettivamente una cartella Drive configurata
        if (!$category || empty($category->folder_drive)) {
            if (file_exists($tmpFileObjectPathName)) {
                unlink($tmpFileObjectPathName);
            }
            return response()->json([
                'error' => 'La categoria selezionata non ha una cartella Google Drive valida associata nel database.'
            ], 422);
        }

        try {
            // SICUREZZA 2: Sanificazione dei testi contro i caratteri speciali (es. l'apice singolo ') 
            // che spezzano la query "q" delle API di Google Drive generando l'errore 400 Bad Request.
            $nomeProcedura = strtoupper($request['procedura']);
            $descrizionePulita = str_replace("'", "\\'", $request['descrizione']); 
            $nomeProceduraPulito = str_replace("'", "\\'", $nomeProcedura);

            $folderNamePadre = $nomeProceduraPulito . ' - ' . $descrizionePulita;

            // 2. OPERAZIONI SU GOOGLE DRIVE (Prima del DB, per non bloccare le tabelle durante le chiamate API esterne)
            
            // Creazione Cartella Padre
            $folderId = GoogleDrive::add_folder([$category->folder_drive], $folderNamePadre, null, true);
            
            // Creazione Sottocartella 'proc'
            $procId = GoogleDrive::add_folder([$folderId], 'proc', null, true);
            
            // Caricamento del File su Drive
            $nomeFileDrive = $nomeProceduraPulito . ' - ' . $request['revisione'] . ' - ' . $request['revisione_anno'];
            $id_file_drive = GoogleDrive::add_file($procId, $nomeFileDrive, $file, true);

            if (!isset($id_file_drive['id'])) {
                throw new \Exception("Impossibile ottenere l'ID univoco del file caricato su Google Drive.");
            }

            // Log di controllo per debug interno
            Log::info("Drive Upload Success - Folder: {$procId}, File: {$id_file_drive['id']}");

            // 3. TRANSAZIONE DATABASE (Garantisce che tutto venga salvato insieme o nulla)
            DB::beginTransaction();

            $procedura = new WfProcedure();
            $procedura->processo_id = $request['processo_id'];
            $procedura->procedura = $nomeProcedura;
            $procedura->descrizione = $request['descrizione'];
            $procedura->revisione = $request['revisione'];
            $procedura->revisione_anno = $request['revisione_anno'];
            $procedura->categoria_id = $request['categoria_id'];
            $procedura->user_id = Auth::id();
            $procedura->tipologia = 1;
            $procedura->stato = 'In-Approval';
            $procedura->folder_drive_padre = $folderId;
            $procedura->folder_drive = $procId;
            $procedura->id_file_drive = $id_file_drive['id'];
            $procedura->notification = true;
            $procedura->save();

            // Salvataggio relazioni Certificati
            if ($request->has('certificati')) {
                foreach ($request['certificati'] as $certificato) {
                    $cert = new WfProcedureCertification();
                    $cert->procedura_id = $procedura->id;
                    $cert->cartificazione_id = $certificato; 
                    $cert->save();
                }
            }

            // Salvataggio relazioni Uffici
            if ($request->has('uffici')) {
                foreach ($request['uffici'] as $ufficio) {
                    $office = new WfProcedureOffice();
                    $office->procedura_id = $procedura->id;
                    $office->ufficio_id = $ufficio;
                    $office->save();
                }
            }

            // Registrazione nel sistema documentale WfDocument
            WfDocument::addDocument(
                $procedura::$modelName, 
                $procedura->id, 
                $procedura->procedura, 
                $procedura->procedura, 
                1, 
                $id_file_drive['id'], 
                $procedura->id
            );

            // Chiudiamo la transazione con successo
            DB::commit();

            // Pulizia finale del file temporaneo locale
            if (file_exists($tmpFileObjectPathName)) {
                unlink($tmpFileObjectPathName);
            }

            return response()->json([
                'success' => true,
                'message' => 'Messaggi.Procedura-Salvata',
                'color'   => 'success',
            ]);

        } catch (\Exception $e) {
            // In caso di errore annulliamo qualsiasi scrittura sul DB
            DB::rollBack();
            
            Log::error("Errore critico nel metodo stored di WfProcedureController: " . $e->getMessage());

            // Garantiamo la pulizia del file temporaneo anche in caso di crash
            if (file_exists($tmpFileObjectPathName)) {
                unlink($tmpFileObjectPathName);
            }

            return response()->json([
                'error' => 'Si è verificato un errore durante il salvataggio o l\'interfacciamento con Google Drive.'
            ], 500);
        }
    }

    public function stored1(Request $request)
    {

        $base64Image = $request->file_upload['file'];

        if (!$tmpFileObject = $this->validateBase64($base64Image, ['xls','xlsx','pdf'])) {
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


        $category = WfCategory::find($request['categoria_id']);

        $procedura = new WfProcedure();
        $procedura->processo_id = $request['processo_id'];
        $procedura->procedura = strtoupper($request['procedura']);
        $procedura->descrizione = $request['descrizione'];
        $procedura->revisione = $request['revisione'];
        $procedura->revisione_anno = $request['revisione_anno'];
        $procedura->categoria_id = $request['categoria_id'];
        $procedura->user_id = Auth::id();
        $procedura->tipologia = 1;
        $procedura->stato = 'In-Approval';
        $folderId = GoogleDrive::add_folder([$category->folder_drive],$procedura->procedura.' - '.$procedura->descrizione ,null,true);
        $procedura->folder_drive_padre = $folderId;
        $procId = GoogleDrive::add_folder([$folderId],'proc',null,true);
        $procedura->folder_drive = $procId;
		Log::error($procId.' '. $procedura->procedura.' - '.$procedura->revisione. ' - '.$procedura->revisione_anno);
        $id_file_drive = GoogleDrive::add_file($procId, $procedura->procedura.' - '.$procedura->revisione. ' - '.$procedura->revisione_anno, $file, true);
		Log::error($id_file_drive);
        $procedura->id_file_drive = $id_file_drive['id'];
        $procedura->notification = true;
        $procedura->save();

        foreach ($request['certificati'] as $certificato){
            $cert = new WfProcedureCertification();
            $cert->procedura_id = $procedura->id;
            $cert->cartificazione_id = $certificato;
            $cert->save();
        }

        foreach ($request['uffici'] as $ufficio){
            $cert = new WfProcedureOffice();
            $cert->procedura_id = $procedura->id;
            $cert->ufficio_id = $ufficio;
            $cert->save();
        }

        WfDocument::addDocument($procedura::$modelName, $procedura->id, $procedura->procedura, $procedura->procedura, 1, $id_file_drive['id'], $procedura->id);

        unlink($tmpFileObjectPathName); // delete temp file

        $message = 'Messaggi.Procedura-Salvata';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
            ]
        );
    }
	
	public function storedAllegati(Request $request, $id)
	{
	   

		$base64File = $request->file_upload['file'] ?? null;

		// Validazione del file base64 con estensioni consentite
		if (!$tmpFileObject = $this->validateBase64($base64File, ['xls', 'xlsx', 'pdf'])) {
			return response()->json([
				'error' => 'Formato file non valido (Ammessi: PDF, XLS, XLSX).'
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

		// Recupero del record Padre
		$padre = WfProcedure::find($id);

		// SICUREZZA 1: Verifichiamo che il padre esista e abbia una cartella Drive configurata
		if (!$padre || empty($padre->folder_drive_padre)) {
			if (file_exists($tmpFileObjectPathName)) {
				unlink($tmpFileObjectPathName);
			}
			return response()->json([
				'error' => 'La procedura padre non esiste o non ha una cartella Google Drive valida associata.'
			], 422);
		}

		try {
			// SICUREZZA 2: Sanificazione dei testi contro i caratteri speciali (es. l'apice singolo ')
			// che spezzano la query "q" delle API di Google Drive generando l'errore 400 Bad Request.
			$nomeProcedura = strtoupper($request['procedura']);
			$descrizionePulita = str_replace("'", "\\'", $request['descrizione']);
			$nomeProceduraPulito = str_replace("'", "\\'", $nomeProcedura);

			$folderNameAllegato = $nomeProceduraPulito . ' - ' . $descrizionePulita;

			// 2. OPERAZIONI SU GOOGLE DRIVE (Prima del DB, per non bloccare le tabelle durante le chiamate di rete)
			
			// Creazione Cartella dell'allegato dentro la cartella padre
			$folderId = GoogleDrive::add_folder([$padre->folder_drive_padre], $folderNameAllegato, null, true);
			
			// Caricamento del File dell'allegato nella cartella appena creata
			$nomeFileDrive = $nomeProceduraPulito . ' - ' . $request['revisione'] . ' - ' . $request['revisione_anno'];
			$id_file_drive = GoogleDrive::add_file($folderId, $nomeFileDrive, $file, true);

			if (!isset($id_file_drive['id'])) {
				throw new \Exception("Impossibile ottenere l'ID univoco dell'allegato caricato su Google Drive.");
			}

			// 3. TRANSAZIONE DATABASE (Garantisce l'atomicità dell'operazione)
			DB::beginTransaction();

			$procedura = new WfProcedure();
			$procedura->processo_id = $padre->processo_id;
			$procedura->procedura = $nomeProcedura;
			$procedura->padre_id = $padre->id;
			$procedura->descrizione = $request['descrizione'];
			$procedura->revisione = $request['revisione'];
			$procedura->revisione_anno = $request['revisione_anno'];
			$procedura->categoria_id = $padre->categoria_id;
			$procedura->user_id = Auth::id();
			$procedura->tipologia = $request['tipologia'];
			$procedura->stato = 'In-Approval';
			$procedura->folder_drive = $folderId;
			$procedura->id_file_drive = $id_file_drive['id'];
			$procedura->save();
			
			// Salvataggio relazioni Certificati
			if ($request->has('certificati')) {
				foreach ($request['certificati'] as $certificato) {
					$cert = new WfProcedureCertification();
					$cert->procedura_id = $procedura->id;
					$cert->cartificazione_id = $certificato;
					$cert->save();
				}
			}

			// Salvataggio relazioni Uffici
			if ($request->has('uffici')) {
				foreach ($request['uffici'] as $ufficio) {
					$office = new WfProcedureOffice();
					$office->procedura_id = $procedura->id;
					$office->ufficio_id = $ufficio;
					$office->save();
				}
			}

			// Aggiornamento notifica sul record padre
			$padre->notification = true;
			$padre->save();

			// Registrazione nel sistema documentale WfDocument
			WfDocument::addDocument(
				$procedura::$modelName, 
				$procedura->id, 
				$procedura->procedura, 
				$procedura->procedura, 
				$procedura->tipologia, 
				$id_file_drive['id'], 
				$padre->id
			);

			// Chiudiamo la transazione con successo
			DB::commit();

			// Pulizia finale del file temporaneo locale
			if (file_exists($tmpFileObjectPathName)) {
				unlink($tmpFileObjectPathName);
			}

			return response()->json([
				'success' => true,
				'message' => 'Messaggi.Allegato-Salvata',
				'color'   => 'success',
			]);

		} catch (\Exception $e) {
			// In caso di errore annulliamo qualsiasi scrittura sul DB
			DB::rollBack();
			
			Log::error("Errore critico nel metodo storedAllegati: " . $e->getMessage());

			// Garantiamo la pulizia del file temporaneo anche in caso di crash
			if (file_exists($tmpFileObjectPathName)) {
				unlink($tmpFileObjectPathName);
			}

			return response()->json([
				'error' => 'Si è verificato un errore durante il salvataggio dell\'allegato.'
			], 500);
		}
	}

    public function storedAllegati1(Request $request, $id)
    {

        $base64Image = $request->file_upload['file'];

        if (!$tmpFileObject = $this->validateBase64($base64Image, ['xls','xlsx','pdf'])) {
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

        $padre = WfProcedure::find($id);

        $procedura = new WfProcedure();
        $procedura->processo_id = $padre->processo_id;
        $procedura->procedura = strtoupper($request['procedura']);
        $procedura->padre_id = $padre->id;
        $procedura->descrizione = $request['descrizione'];
        $procedura->revisione = $request['revisione'];
        $procedura->revisione_anno = $request['revisione_anno'];
        $procedura->categoria_id = $padre->categoria_id;
        $procedura->user_id = Auth::id();
        $procedura->tipologia = $request['tipologia'];
        $procedura->stato = 'In-Approval';
        $folderId = GoogleDrive::add_folder([$padre->folder_drive_padre],$procedura->procedura.' - '.$request['descrizione'],null,true);
        $procedura->folder_drive = $folderId;
        $id_file_drive = GoogleDrive::add_file($procedura->folder_drive, $procedura->procedura.' - '.$procedura->revisione. ' - '.$procedura->revisione_anno, $file, true);
        $procedura->id_file_drive = $id_file_drive['id'];
        $procedura->save();
		
		foreach ($request['certificati'] as $certificato){
            $cert = new WfProcedureCertification();
            $cert->procedura_id = $procedura->id;
            $cert->cartificazione_id = $certificato;
            $cert->save();
        }

        foreach ($request['uffici'] as $ufficio){
            $cert = new WfProcedureOffice();
            $cert->procedura_id = $procedura->id;
            $cert->ufficio_id = $ufficio;
            $cert->save();
        }

        $padre->notification = true;
        $padre->save();

        WfDocument::addDocument($procedura::$modelName, $procedura->id, $procedura->procedura, $procedura->procedura, $procedura->tipologia, $id_file_drive['id'], $padre->id);

        unlink($tmpFileObjectPathName); // delete temp file

        $message = 'Messaggi.Allegato-Salvata';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
            ]
        );
    }
	
	public function edit(Request $request, $id)
    {
        $procedura = WfProcedure::find($id);
        $procedura->processo_id = $request['processo_id'];
        $procedura->procedura = strtoupper($request['procedura']);
        $procedura->padre_id = $request['padre_id'];
        $procedura->descrizione = $request['descrizione'];
        $procedura->revisione = $request['revisione'];
        $procedura->revisione_anno = $request['revisione_anno'];
        $procedura->categoria_id = $request['categoria_id'];
        $procedura->user_id = Auth::id();
        $procedura->tipologia = $request['tipologia'];
        $procedura->stato = 'In-Approval';
        GoogleDrive::rename_dir($procedura->folder_drive, $procedura->procedura.' - '.$request['descrizione'], 'google');
        GoogleDrive::rename_dir($procedura->id_file_drive, $procedura->procedura.' - '.$procedura->revisione. ' - '.$procedura->revisione_anno, 'google');

        if($procedura->id_file_drive){
            //GoogleDrive::delated($procedura->id_file_drive,'google');
            //$id_file_drive = GoogleDrive::add_file($procedura->folder_drive, $procedura->procedura.' - '.$procedura->revisione. ' - '.$procedura->revisione_anno, $file, true);
            //$procedura->id_file_drive = $id_file_drive;
        }

        $procedura->save();

        $message = 'Messaggi.Ducumento-Modificato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
            ]
        );
    }

    public function getItem($id)
    {
        $cf = [];
        $uf = [];
        $obj = WfProcedure::find($id);
        $uffici = WfProcedureOffice::select('ufficio_id')->where('procedura_id',$id)->get();
        foreach ($uffici as $ufficio)
            $uf[]= $ufficio->ufficio_id;
        $certificati = WfProcedureCertification::select('cartificazione_id')->where('procedura_id',$id)->get();
        foreach ($certificati as $certificato)
            $cf[]= $certificato->cartificazione_id;

        return response()->json(['item' => $obj, 'certificati' => array_values($cf), 'uffici' => array_values($uf)]);
    }

    public function getDocument($id)
    {
        $objs = DB::table('wf_documents')->where('model_id', $id)->orWhere('model_head_id',$id)
            ->distinct('model_id')
            ->orderBy('created_at','asc')
            ->get();

        return response()->json($objs);
    }


    public function get_processi(Request $request)
    {
        $objs = DB::table('wf_categories')->where('model','WfProcessi')->get();

        return response()->json($objs);
    }

    public function get_categorie(Request $request)
    {
        $objs = DB::table('wf_categories')->where('model','WfProcedure')->get();

        return response()->json($objs);
    }

    public function approval(Request $request)
    {

        $obj = WfProcedure::find($request->id);
        $completed = WfUserApproval::approval($request->id, $obj::$modelName, Auth::id(), $request->role_id, 'Approved', null);

        if($completed)
            DB::table("wf_procedures")
                ->where('id',$request->id)
                ->update(['stato' => 'Approved', 'data_approvazione' => date('Y-m-d')]);


        if(!is_null($completed))
            Dispatch(new WfLogProcedure($obj->id));

		$id_next = $obj->id;
        if($obj->padre_id)
            $id_next = $obj->padre_id;

        $is_approver = WfUser::select('id','approval_start_date')->where('model',WfProcedure::$modelName)->where('user_id',Auth::id())->where('disabled',false)->first();

        $next = WfProcedure::select('wf_procedures.*')
            ->select('wf_procedures.*','wf_user_approvals.approval_action')
            ->leftJoin('wf_user_approvals', function($join)
            {
                $join->on('wf_procedures.id', '=', 'wf_user_approvals.model_id');
                $join->where('wf_user_approvals.user_id','=',Auth::id());
            })
			->where('wf_procedures.stato','In-Approval')
            ->whereDate('wf_procedures.created_at','>=', $is_approver->approval_start_date)
            //->WhereNotIn('user_id',[Auth::id()])
            ->WhereNull('model_id')
			->where('wf_procedures.padre_id','=',$id_next)
            ->orderBy('created_at', 'asc')
            ->first();

        if(empty($next->id))
            $next = '0';

		sleep(5);
        return response()->json(
            [
                'success' => true,
                'message' => 'Procedura Approvata' ,
                'color' => 'success',
                'obj' => $next
            ]
        );
    }
	
	public function export()
    {
        $result = DB::table("wf_procedures")
            ->whereNull('sup')
            ->orderBy('procedura','asc')
            ->get();


        $spreadsheet  = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();

        $certificati = DB::table("wf_certifications")
            ->select('certificazione')
            ->orderBy('certificazione','asc')
            ->get();

        $activeWorksheet->setTitle('Report Procedure');
        $activeWorksheet->getColumnDimension('A')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('B')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('C')->setAutoSize(true);
        $activeWorksheet->getColumnDimension('D')->setAutoSize(true);
        $activeWorksheet->setCellValue('A1', 'Documento');
        $activeWorksheet->setCellValue('B1', 'Revisione');
        $activeWorksheet->setCellValue('C1', 'Descrizione');
        $activeWorksheet->setCellValue('D1', 'Competenze');
        $letter = 'D';
        $cert = [];
        foreach ($certificati as $certificato){
            $letterAscii = ord($letter);
            $letterAscii++;
            $letter = chr($letterAscii);
            $cert[$letter] = $certificato->certificazione;
            $activeWorksheet->getColumnDimension($letter)->setAutoSize(true);
            $activeWorksheet->getStyle($letter)->getFill()->applyFromArray(['fillType' => 'solid','rotation' => 0, 'color' => ['rgb' => '70F534'],]);
            $activeWorksheet->getStyle($letter)->getAlignment()->setHorizontal('center');
            $activeWorksheet->setCellValue($letter.'1', $certificato->certificazione);
        }

        $i = 2;
        foreach ($result as $row){
            $compenteze = DB::table("wf_procedure_offices")
                ->join('wf_offices','wf_procedure_offices.ufficio_id','wf_offices.id')
                ->select('ufficio')
                ->where('procedura_id',$row->id)
                ->orderBy('ufficio','asc')
                ->pluck('ufficio')->toArray();

            $certificati = DB::table("wf_procedure_certifications")
                ->join('wf_certifications','wf_procedure_certifications.cartificazione_id','wf_certifications.id')
                ->select('certificazione')
                ->where('procedura_id',$row->id)
                ->orderBy('certificazione','asc')
                ->pluck('certificazione','certificazione')->toArray();

            $activeWorksheet->setCellValue('A'.$i, $row->procedura);
            $activeWorksheet->setCellValue('B'.$i, $row->revisione);
            $activeWorksheet->setCellValue('C'.$i, $row->descrizione);
            $activeWorksheet->setCellValue('D'.$i, implode(",",$compenteze));
            foreach ($cert as $lettera => $certificato)
                $activeWorksheet->setCellValue($lettera.''.$i, (!empty($certificati[$certificato]) ? 'x':''));

            $i++;
        }

        $utenti = DB::table("wf_users")
            ->join('users','wf_users.user_id','users.id')
            ->select('full_name','user_id')
            ->where('model','WfProcedure')
            ->where('disabled',false)
            ->orderBy('full_name')
            ->get();

        $result = DB::table("wf_procedures")
            ->whereNull('sup')
            ->whereNull('padre_id')
            ->orderBy('procedura','asc')
            ->get();

        $myWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Utenti');
        $myWorkSheet->getColumnDimension('A')->setAutoSize(true);

        $myWorkSheet->setCellValue('A1', 'Utenti');

        $u = 1;
        foreach ($utenti as $utente){
            $u++;
            $myWorkSheet->setCellValue('A'.$u, $utente->full_name);
            $letter = 'A';
            foreach ($result as $proc){
                $letterAscii = ord($letter);
                $letterAscii++;
                $letter = chr($letterAscii);
                $myWorkSheet->setCellValue($letter.'1', $proc->procedura);

                $c_procedure = DB::table("wf_procedures")
                    ->where('padre_id',$proc->id)->orWhere('id',$proc->id)->count();

                $t_procedure_a = DB::table("wf_user_approvals")
                    ->whereIn('model_id', function($query) use ($proc){
                        $query->select('id')
                            ->from('wf_procedures')
                            ->where('padre_id',$proc->id)->orWhere('id',$proc->id);
                    })
                    ->where('model','WfProcedure')
                    ->where('user_id',$utente->user_id)
                    ->count();

                $myWorkSheet->setCellValue($letter.''.$u, $c_procedure.' / '.$t_procedure_a);
            }
        }

        $spreadsheet->addSheet($myWorkSheet);
        $writer = new Xlsx($spreadsheet);

        $writer->save('Procedure.xlsx');

        return  response()->download( public_path('Procedure.xlsx'));
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
