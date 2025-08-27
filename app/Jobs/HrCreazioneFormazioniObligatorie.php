<?php

namespace App\Jobs;

use App\Models\HrEmployee;
use App\Models\HrEmployeeTrainingMandatory;
use App\Models\QtFai;
use App\Models\Utility;
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

    /**
     * Create a new job instance.
     */
    public function __construct($id)
    {
        $this->idDipendnete = $id;
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
            $traning->utente_id = Auth::id();
            $traning->path_drive = $dipendnete->path_drive;
            $traning->save();
            $trainings[] = ['titolo' => $obj->formazione, 'scadenza' => '-'];
        }

        $users = Utility::users_notify(['hr_scadenza_formazioni']);
        $subject = $dipendnete->nome_completo .' Formazioni in attesa di documentazioni';
        $info = [
            'testo' => 'Formazioni create e in attesa di documentazioni',
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
