<?php

namespace App\Jobs;

use App\Models\WfDocument;
use App\Models\WfProcedure;
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

class WfLogProcedure implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    //public $tries = 2;

    protected $id_procedura;


    /**
     * Create a new job instance.
     */
    public function __construct($id)
    {
        $this->id_procedura = $id;
		//$this->runBoj();
		
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Recupero il Documento
        $obj = WfProcedure::where('id',$this->id_procedura)->first();
        $padre = $obj;
        if(!empty($obj->padre_id))
            $padre = WfProcedure::where('id',$obj->padre_id)->first();
        // recupero il documento in approvazione
        $document = WfDocument::where('model_id', $this->id_procedura)->where('tipologia',$obj->tipologia)->first();
        // lista di utenti che hanno approvato
        $users = WfUserApproval::join('users','user_id','users.id')
            ->select('wf_user_approvals.*','users.full_name')
            ->where('model_id', $this->id_procedura)
            ->where('model',$obj::$modelName)
            ->get();

        $nomeFile = 'Log '.$obj->procedura.'.pdf';

        if($obj->id_log_drive){
            // se il file log è già presente elimino il file
            GoogleDrive::delated($obj->id_log_drive,'google');
            DB::table("wf_documents")
                ->where('id_file_drive',$obj->id_log_drive)
                ->delete();
        }

        $data = [
            'processo' => $padre->Processo->categoria,
            'categoria' => $padre->Categoria->categoria,
            'procedura' => $padre->procedura,
            'documento' => $obj->procedura,
            'data_creazione' => $obj->created_at,
            'tipologia' => WfProcedure::$tipologie[$obj->tipologia],
            'stato' => ($obj->stato == 'In-Approval' ? 'In Approvazione':'Approvato'),
            'data_approvazione' => $obj->data_approvazione,
            'file' => $document->nome_file,
            'users' => $users,
            'logo' => public_path('images/custom/logo_mb.png'),
            'check' => ($obj->data_approvazione ? public_path('images/custom/ceck.png'):''),
        ];

        $path =  storage_path('app/pdf/');
        $pdf = PDF::loadView('pdf/wfLogProcedure', ['data' => $data]);
        $pdf->save($path.$nomeFile)->stream('Log '.$nomeFile);
        $id_file = GoogleDrive::add_file($obj->folder_drive, $nomeFile, $path . $nomeFile, true);

        WfDocument::addDocument($obj::$modelName, $obj->id, $obj->procedura, $nomeFile, 100, $id_file, $obj->id);

        $obj->id_log_drive = $id_file;
        $obj->save();

        @unlink($path . 'Log '.$document->nome_file);
    }
}
