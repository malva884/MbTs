<?php

namespace App\Jobs;

use App\Models\HrEmployee;
use App\Models\HrEmployeeTrainingMandatory;
use App\Models\HrEmployeeTrainingProfessional;
use App\Models\QtFai;
use App\Models\Utility;
use App\Services\GoogleDrive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class HrCreazioneFormazioniAutomatiche implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $idDipendnete;
    protected $utenteId;

    /**
     * Create a new job instance.
     */
    public function __construct($id,$utente_id)
    {
        $this->idDipendnete = $id;
        $this->utenteId = $utente_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $objs =  DB::table('hr_trainings')
            ->select('id','formazione','tipologia')
            ->where('auto_creazione', true)
            ->get();

        $dipendnete = HrEmployee::find($this->idDipendnete);
        $trainings = [];
        foreach ($objs as $obj){
            $pathDrive = null;
            if (!empty($dipendnete->path_drive)) {
                try {
                    $pathDrive = GoogleDrive::add_folder([$dipendnete->path_drive], $obj->formazione, 'google', true);
                } catch (\Exception $e) {
                    Log::error("Errore creazione cartella Drive per formazione {$obj->formazione}: " . $e->getMessage());
                }
            }

            if ($obj->tipologia === 'professionale') {
                $traning = new HrEmployeeTrainingProfessional();
                $traning->formazione = $obj->formazione;
                $traning->tipologia = 1;
            } else {
                $traning = new HrEmployeeTrainingMandatory();
                $traning->data_scadenza = null;
            }

            $traning->employee_id = $this->idDipendnete;
            $traning->formazione_id = $obj->id;
            $traning->data_formazione = date('Y-m-d');
            $traning->utente_id = $this->utenteId;
            $traning->path_drive = $pathDrive;
            $traning->save();
            $trainings[] = ['titolo' => $obj->formazione, 'scadenza' => '-'];
        }

        $users = Utility::users_notify(['hr_scadenza_formazioni']);
        $subject = $dipendnete->nome_completo .' Formazioni create e in attesa di documentazioni';
        $info = [
            'testo' => '',
            'dipendente' => $dipendnete->nome_completo,
            'nome' => $dipendnete->nome,
            'cognome' => $dipendnete->cognome,
            'matricola' => $dipendnete->matricola,
            'idDipendnete' => $dipendnete->id,
            'formazioni' => $trainings
        ];

        Mail::send('emails/email_formazioni_dipendneti', compact('info'), function ($message) use ($users,$subject) {
            $message
                ->to($users)
                ->subject($subject);
        });
    }
}
