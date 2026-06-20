<?php

namespace App\Jobs;

use App\Models\QtFai;
use App\Models\Utility;
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
use Illuminate\Support\Facades\Mail;

class WfLogCommessa implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    protected $id_commessa;


    /**
     * Create a new job instance.
     */
    public function __construct($id)
    {
        $this->id_commessa = $id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        WfOrder::Log($this->id_commessa);
        /*
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

        if($obj->id_log_drive){
            // se il file log è già presente elimino il file
            GoogleDrive::delated($obj->id_log_drive,'google');
            DB::table("wf_documents")
                ->where('nome_file','Log '.$document->nome_file)
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

        @unlink($path . 'Log '.$document->nome_file)
        */
    }
}
