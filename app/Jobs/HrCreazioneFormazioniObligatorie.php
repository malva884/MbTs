<?php

namespace App\Jobs;

use App\Models\HrEmployee;
use App\Models\HrEmployeeTrainingMandatory;
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

class HrCreazioneFormazioniObligatorie implements ShouldQueue
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
            ->select('id','formazione')
            ->where('auto_creazione', 1)
            ->get();

        $dipendnete = HrEmployee::find($this->idDipendnete);
        $trainings = [];
        foreach ($objs as $obj){
            $traning =  new HrEmployeeTrainingMandatory();
            $traning->employee_id = $this->idDipendnete;
            $traning->formazione_id = $obj->id;
            $traning->data_formazione = date('Y-m-d');
            $traning->data_scadenza = date('Y-m-d');
            $traning->utente_id = $this->utenteId;
            $traning->path_drive = GoogleDrive::add_folder([$dipendnete->path_drive], $obj->formazione, 'google', true);
            $traning->save();
            $trainings[] = ['titolo' => $obj->formazione, 'scadenza' => '-'];
        }

        $users = Utility::users_notify(['hr_scadenza_formazioni']);
        $subject = $dipendnete->nome_completo .' Formazioni create e in attesa di documentazioni';
        $info = [
            'testo' => '',
            'dipendente' => $dipendnete->nome_completo,
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
