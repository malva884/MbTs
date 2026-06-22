<?php

namespace App\Http\Controllers;

use App\Models\LogActivitySupllier;
use App\Models\QtCertification;
use App\Models\QtSupplierCertification;
use App\Models\QtSupplierNotice;
use App\Models\QtSupplierQuestionnaire;
use App\Services\GoogleDrive;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class QtSupplierCertificationController
{
    public function list(Request $request)
    {

        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $cartificatoBy = $request->get('cartificato');

        if (empty($sortByName)) {
            $sortByName = 'titolo';
            $orderBy = 'asc';
        }

        $objs = DB::connection('sqlsrv_fornitori')->table('certifications')
            ->select('certifications.*')
            ->Where(function ($query) use ($cartificatoBy) {
                if ($cartificatoBy)
                    $query->Where('titolo', 'LIKE', '%' . $cartificatoBy . '%');
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function stored(Request $request)
    {
        $obj = new QtCertification();
        $obj->titolo = ucfirst(strtolower($request->titolo));
        $obj->descrizione = $request->descrizione;
        $obj->disattivo = ($request->disattivo ? true:false);
        $obj->save();

        $message = 'Messaggi.Certificazione-Aggiunta';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function update(Request $request, $id)
    {
        $obj = QtCertification::find($id);
        $obj->descrizione = $request->descrizione;
        $obj->disattivo = ($request->disattivo ? true:false);
        $obj->save();
        $message = 'Messaggi.Certificazione-Modificata';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function list_certifications(Request $request, $id)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $cartificatoBy = $request->get('cartificato');

        if (empty($sortByName)) {
            $sortByName = 'id';
            $orderBy = 'asc';
        }

        $objs = DB::connection('sqlsrv_fornitori')->table('certifications')
            ->leftJoin('supplier_certifications','certifications.id','supplier_certifications.certificato_id')
            ->join('suppliers','supplier_certifications.fornitore_id','suppliers.id')
            ->select('supplier_certifications.*','certifications.titolo','suppliers.folderID','supplier_certifications.file_id')
            ->where('fornitore_id',$id)
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function all()
    {
        $objs = DB::connection('sqlsrv_fornitori')->table('certifications')
            ->get();
        $result = [];
        foreach ($objs as $obj)
            $result[] = ['value'=> $obj->id, 'text'=> $obj->titolo];


        return response()->json(array_values($result));
    }

    public function notSupplier($id)
    {
        $objs = DB::connection('sqlsrv_fornitori')->table('certifications')
            ->where('disattivo','<>','1')
            ->whereNotIn('id', function($query) use ($id){
                $query->select( 'certificato_id')
                    ->from('supplier_certifications')
                    ->where('fornitore_id',$id);
            })
            ->get();
        $result = [];
        foreach ($objs as $obj)
            $result[] = ['value'=> $obj->id, 'text'=> $obj->titolo];


        return response()->json(array_values($result));
    }

    public function storedSupplierCertification(Request $request)
    {

        if(!empty($request->file['file']))
            $base64Image = $request->file['file'];

        if (!empty($base64Image) && !$tmpFileObject = $this->validateBase64($base64Image, ['pdf'])) {
            return response()->json([
                'error' => 'Invalid image format.'
            ], 415);
        }

        // verifico se il fornitore ha un vecchi certificato
        $certificato = new QtSupplierCertification();
        $certificato->fornitore_id = $request->fornitore_id;
        $certificato->certificato_id = $request->certificato_id;
        $certificato->livello = $request->livello;
        $certificato->valutazione = $request->valutazione;
        $certificato->scadenza = $request->scadenza;
        $certificato->data_acquisizione = date('Y-m-d');
        $certificato->approvato = $request->approvato;
        $titolo = $certificato->certificato->titolo;

        if(!empty($base64Image)){
            $folder_id = GoogleDrive::add_folder([$certificato->supplier->folderID], $titolo, 'google', true);

            $tmpFileObjectPathName = $tmpFileObject->getPathname();

            $file = new UploadedFile(
                $tmpFileObjectPathName,
                $tmpFileObject->getFilename(),
                $tmpFileObject->getMimeType(),
                0,
                true
            );

            if($certificato->file_id)
                GoogleDrive::delated($certificato->file_id,'google');

            $fileDrive = GoogleDrive::add_file($folder_id, $titolo.'.pdf', $file, true, null);

            unlink($tmpFileObjectPathName); // delete temp file
            $certificato->file_id = $fileDrive['id'];

        }
        $certificato->save();
        // Log Attività
        LogActivitySupllier::addToLog('Inserimento', $request->fornitore_id, ['titolo'=>'Inserita Nuova Certificazione','descrizione'=> $titolo, 'nome' => Auth::user()->full_name],'success','edit_generic');

        $message = 'Messaggio.Certificazione-Caricato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
            ]
        );
    }

    public function updateSupplierCertification(Request $request, $id)
    {
        if(!empty($request->file['file']))
            $base64Image = $request->file['file'];

        if (!empty($base64Image) && !$tmpFileObject = $this->validateBase64($base64Image, ['pdf'])) {
            return response()->json([
                'error' => 'Invalid image format.'
            ], 415);
        }

        // verifico se il fornitore ha un vecchi certificato
        $certificato = QtSupplierCertification::find($id);
        $certificato->livello = $request->livello;
        $certificato->valutazione = $request->valutazione;
        $certificato->scadenza = $request->scadenza;
        $certificato->data_acquisizione = date('Y-m-d');
        $certificato->approvato = $request->approvato;
        //$certificato->file_id = null;						#TODO
        $titolo = $certificato->certificato->titolo;

        if(!empty($base64Image)){
            $folder_id = GoogleDrive::add_folder([$certificato->supplier->folderID], $titolo, 'google', true);

            $tmpFileObjectPathName = $tmpFileObject->getPathname();

            $file = new UploadedFile(
                $tmpFileObjectPathName,
                $tmpFileObject->getFilename(),
                $tmpFileObject->getMimeType(),
                0,
                true
            );

            if($certificato->file_id)
                GoogleDrive::delated($certificato->file_id,'google');

            $fileDrive = GoogleDrive::add_file($folder_id, $titolo.'.pdf', $file, true, null);
            // Log Attività
            LogActivitySupllier::addToLog('Update', $request->fornitore_id, ['titolo'=>' Nuova Certificazione','descrizione'=> $titolo, 'nome' => Auth::user()->full_name],'success','edit_generic');

            unlink($tmpFileObjectPathName); // delete temp file
            $certificato->file_id = $fileDrive['id'];

        }

        $certificato->save();

        $message = 'Messaggio.Certificazione-Modificata';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
            ]
        );
    }

    public function approveCertification(Request $request, $id)
    {

        $certificato = QtSupplierCertification::find($id);
        $certificato->approvato = ($request->approvato ? true:false);
        $certificato->save();

        $tipo = 'Certificato Approvato';
        $titolo = $certificato->certificato->titolo;
        $descrizione = '';
        $colore = 'success';

        if(!$certificato->approvato){
            QtSupplierNotice::stored($request->avviso, $certificato->fornitore_id);
            $tipo = 'Certificato Non Approvato';
            $titolo = $certificato->certificato->titolo;
            $descrizione = $request->avviso['titolo'];
            $colore = 'success';
        }

        LogActivitySupllier::addToLog($tipo, $certificato->fornitore_id, ['titolo'=> $titolo,'descrizione'=> $descrizione, 'nome' => Auth::user()->full_name],$colore,'edit_generic');

        $message = 'Messaggio.Salvato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
            ]
        );

    }
	
	public function getQuestionario(Request $request, $id)
    {
        $obj = QtSupplierQuestionnaire::where('certificato_id',$id)
            ->where('supplier_id',$request->fornitore_id)->first();

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
}
