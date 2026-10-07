<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\WfDocument;
use App\Models\WfUser;
use App\Models\WfUserApproval;
use App\Models\WfVariations;
use App\Services\GoogleDrive;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WfLogVariation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id_variation;

    /**
     * Create a new job instance.
     */
    public function __construct($id)
    {
        $this->id_variation = $id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->runJob();
    }

    public function runJob()
    {
        // Recupero la variazione
        $obj = WfVariations::where('id', $this->id_variation)->first();
        if (empty($obj->id))
            return;

        // documento in approvazione
        $document = WfDocument::where('model_id', $this->id_variation)
            ->where('model', WfVariations::$modelName)
            ->where('tipologia', '!=', 100)
            ->first();

        // lista di utenti che hanno approvato (escludo le righe 'Viewed')
        $users = WfUserApproval::join('users', 'user_id', 'users.id')
            ->select('wf_user_approvals.*', 'users.full_name')
            ->where('model_id', $this->id_variation)
            ->where('model', WfVariations::$modelName)
            ->where('wf_user_approvals.approval_action', '!=', 'Viewed')
            ->get();

        // lista visualizzatori abilitati (ruolo 'Visualizzatore' in wf_users) con stato lettura
        $viewedMap = DB::table('wf_user_approvals')
            ->where('model', WfVariations::$modelName)
            ->where('model_id', $this->id_variation)
            ->where('approval_action', 'Viewed')
            ->pluck('created_at', 'user_id');

        $viewers = WfUser::join('users', 'wf_users.user_id', '=', 'users.id')
            ->join('wf_roles', 'wf_users.role_id', '=', 'wf_roles.id')
            ->select('users.id as user_id', 'users.full_name')
            ->where('wf_users.model', WfVariations::$modelName)
            ->where('wf_users.disabled', false)
            ->where('wf_roles.role', 'Visualizzatore')
            ->orderBy('users.full_name')
            ->get()
            ->map(function ($u) use ($viewedMap) {
                $u->viewed = $viewedMap->has($u->user_id);
                $u->data_view = $viewedMap->get($u->user_id);
                return $u;
            });

        $nomeFile = 'Log ' . $obj->ol . ' r' . $obj->revisione . '.pdf';

        if ($obj->id_log_drive) {
            // se il file log è già presente elimino il file
            GoogleDrive::delated($obj->id_log_drive, 'google');
            DB::table('wf_documents')
                ->where('id_file_drive', $obj->id_log_drive)
                ->delete();
        }

        $data = [
            'ol' => $obj->ol,
            'revisione' => $obj->revisione,
            'data_creazione' => $obj->created_at,
            'creator' => optional(User::find($obj->creator))->full_name,
            'stato' => $obj->stato,
            'data_approvazione' => $obj->data_approvazione,
            'end_date' => $obj->end_date,
            'file' => optional($document)->nome_file,
            'users' => $users,
            'viewers' => $viewers,
            'logo' => public_path('images/custom/logo_mb.png'),
            'check' => ($obj->data_approvazione ? public_path('images/custom/ceck.png') : ''),
        ];

        $path = storage_path('app/pdf/');
        if (!is_dir($path))
            mkdir($path, 0777, true);

        $pdf = Pdf::loadView('pdf/wfLogVariazioni', ['data' => $data]);
        $pdf->save($path . $nomeFile)->stream($nomeFile);

        if (empty($obj->folder_drive)) {
            Log::error('WfLogVariation: folder_drive mancante per variazione id=' . $this->id_variation);
            return;
        }

        $id_file = GoogleDrive::add_file($obj->folder_drive, $nomeFile, $path . $nomeFile, true);

        if (!$id_file) {
            Log::error('GoogleDrive::add_file failed for WfLogVariation id=' . $this->id_variation);
            throw new \Exception('Caricamento log su Google Drive fallito.');
        }

        WfDocument::addDocument(WfVariations::$modelName, $obj->id, $obj->ol, $nomeFile, 100, $id_file, $obj->id);

        $obj->id_log_drive = $id_file;
        $obj->save();

        @unlink($path . $nomeFile);
    }
}
