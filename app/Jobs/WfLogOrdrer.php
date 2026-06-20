<?php

namespace App\Jobs;

use App\Models\WfDocument;
use App\Models\WfOrder;
use App\Models\WfUserApproval;
use App\Services\GoogleDrive;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WfLogOrdrer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    //public $tries = 2;

    protected $id_commessa;


    /**
     * Create a new job instance.
     */
    public function __construct($id)
    {
        $this->id_commessa = $id;
		$this->runBoj();
		
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
		/*
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
            DDB::table("wf_documents")
                ->where('nome_file','Log '.$document->nome_file)
                ->delete();
        }
		
		$nomeFile = 'Log '.$obj->commessa.'.pdf';
	
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
        $pdf->save($path.$nomeFile)->stream('Log '.$nomeFile);
        $id_file = GoogleDrive::add_file($obj->folder_drive, $nomeFile, $path . $nomeFile, true);
	
        WfDocument::addDocument($obj::$modelName, $obj->id, $obj->commessa, $nomeFile, 100, $id_file['id'], $obj->id);

        $obj->id_log_drive = $id_file['id'];
        $obj->save();

        @unlink($path . 'Log '.$document->nome_file);
		*/
    }
	
	public function runBoj(){
		//WfOrder::LogOrder($this->id_commessa);
		// Recupero la commessa
        $obj = WfOrder::where('id',$this->id_commessa)->first();
        // recupero il documento in approvazione
        $document = WfDocument::where('model_id', $this->id_commessa)->where('tipologia',$obj->tipologia)->first();
        // lista di utenti che hanno approvato
        $users = WfUserApproval::join('users','user_id','users.id')
            ->select('wf_user_approvals.*','users.full_name')
            ->where('model_id', $this->id_commessa)
            ->where('model','WfOrder')
            ->get();
		
		$nomeFile = 'Log '.$obj->commessa.'.pdf';
		if($obj->tipologia == 3)	
			$nomeFile = 'Log '.$obj->commessa.' r '.$obj->revisione.'.pdf';

        if($obj->id_log_drive){
            // se il file log è già presente elimino il file
			GoogleDrive::delated($obj->id_log_drive,'google');
			DB::table("wf_documents")
                ->where('id_file_drive',$obj->id_log_drive)
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
        $pdf->save($path.$nomeFile)->stream($nomeFile);
        $id_file = GoogleDrive::add_file($obj->folder_drive, $nomeFile, $path . $nomeFile, true);
	
        WfDocument::addDocument($obj::$modelName, $obj->id, $obj->commessa, $nomeFile, 100, $id_file['id'], $obj->id);

        $obj->id_log_drive = $id_file['id'];
        $obj->save();

        @unlink($path . 'Log '.$document->nome_file);
	}
}
