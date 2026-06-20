<?php

namespace App\Models;

use App\Services\GoogleDrive;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WfOrder extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','creator','commessa','commessa_sistema','revisione','categoria_id','data_approvazione',
        'tipologia','visibile','stato','id_commessa_padre','id_file_drive','folder_drive','id_log_drive'];
    public static $modelName = 'WfOrder';
    public static $WfMode = 'standard';
    public static $roleIdApproved = ['Approvatore'];
    public static $typologies = [1 => 'Commessa', 3 => 'Revisione'];

    public static $typologieDocuments = ['Commessa' => 1, 'Revisione' => 3, 'DDT' => 20, 'DDC' => 25, 'Conferma D\'ordine' => 50, 'Distinte' => 51, 'Log Approvazioni' => 100];
    public static $notificationUserTypology = [1 => false, 3 => true];

    public static function addWorkflow($commessa, $tipologia, $categoria, $commessa_sistema, $revisione, $folderMonthId, $id_padre, $visibile = false,  $folder_Order = null, $stato = 'In-Approval', $dataCreazione = null, $dataApprovazione = null)
    {

        $workflow = new WfOrder();
        $workflow->creator = 5;
        $workflow->stato = $stato;
        $workflow->categoria_id = $categoria->id;
        $workflow->commessa_sistema = $commessa_sistema;
        $workflow->tipologia = $tipologia;
        $workflow->revisione = $revisione;
        $workflow->commessa = $commessa;
        $workflow->folder_drive = $folder_Order;
        //if($tipologia == 1 && empty($folder_Order))
            //$workflow->folder_drive = GoogleDrive::add_folder([$folderMonthId],$commessa,null,true);

        $workflow->visibile = $visibile;
        if($workflow->tipologia == 1)
            $workflow->id_commessa_padre = $id_padre;
        if($dataApprovazione)
            $workflow->data_approvazione = $dataApprovazione;
        if($dataCreazione)
            $workflow->created_at = $dataCreazione;
        $workflow->save();

        return $workflow;
    }

    public static function checkFlow($commessa, $tipologia, $revisione = null){

        $workflow = WfOrder::select('id','folder_drive','id_file_drive')
            ->where('commessa', '=', $commessa)
            ->where('tipologia', '=', $tipologia)
            ->Where(function ($query) use ($revisione) {
                if ($revisione)
                    $query->Where('revisione', $revisione);
            })
            ->first();

        return $workflow;
    }

    public static function checkFlowOld($commessa){

        $workflow = DB::connection('mysql_old')->table('workflows')
            ->join('workflow_files','workflow_files.Workflow','workflows.id')
            ->select('workflows.id','path_folder_drive','commessa','path_drive as id_file_drive','workflow_files.path_folder_drive','nomeFile','workflows.created_at','workflows.status','end_date')
            ->where('workflows.commessa_name', '=', $commessa)
            ->where('workflows.type', '=', 1)
            ->first();

        return $workflow;
    }

    public static function Log($id_commessa)
    {
        // Recupero la commessa
        $obj = WfOrder::where('id',$id_commessa)->first();
        // recupero il documento in approvazione
        $document = WfDocument::where('model_id', $id_commessa)->where('tipologia',$obj->tipologia)->first();
        // lista di utenti che hanno approvato
        $users = WfUserApproval::join('users','user_id','users.id')
            ->select('wf_user_approvals.*','users.full_name')
            ->where('model_id', $id_commessa)
            ->where('model','WfOrder')
            ->get();

        if($obj->id_log_drive){
            // se il file log è già presente elimino il file
            GoogleDrive::delated($obj->id_log_drive,'google');
            DB::table("wf_documents")
                ->where('id_file_drive',$obj->id_log_drive)
                //->where('nome_file','Log '.$document->nome_file)
                ->delete();
        }

        $data = [
            'commessa' => $obj->commessa,
            'data_creazione' => $obj->created_at,
            'tipologia' => ($obj->tipologia == 1 ? 'Commessa' : 'Revisione'),
            'stato' => ($obj->stato == 'In-Approval' ? 'In Approvazione':'Approvato'),
            'data_approvazione' => $obj->data_approvazione,
            'file' => $document->nome_file,
            'users' => $users,
            'logo' => public_path('images/custom/logo_mb.png'),
            'check' => ($obj->data_approvazione ? public_path('images/custom/ceck.png'):''),
        ];

        $path =  storage_path('app/pdf/');
        $pdf = PDF::loadView('pdf/wfLogCommesse', ['data' => $data]);
        $pdf->save($path.'Log '.$document->nome_file)->stream('Log '.$document->nome_file);
        $id_file = GoogleDrive::add_file($obj->folder_drive, 'Log '.$document->nome_file, $path . 'Log '.$document->nome_file, true);
        Log::info($id_file);
        WfDocument::addDocument($obj::$modelName, $obj->id, $obj->commessa, 'Log '.$document->nome_file, 100, $id_file, $obj->id);

        $obj->id_log_drive = $id_file;
        $obj->save();

        @unlink($path . 'Log '.$document->nome_file);
    }

}
