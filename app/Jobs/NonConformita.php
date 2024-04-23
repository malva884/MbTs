<?php

namespace App\Jobs;

use App\Models\QtConformita;
use App\Models\Utility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class NonConformita implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id;
    protected $titolo;
    protected $stato;
    protected $riapertura;

    /**
     * Create a new job instance.
     */
    public function __construct($id, $titolo,$stato,$riapertura=null)
    {
        $this->id = $id;
        $this->titolo = $titolo;
        $this->stato = $stato;
        $this->riapertura = $riapertura;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $obj = DB::table('qt_conformitas')->select('qt_conformitas.*','users.full_name','machineries.nome as macchina_nome','defects.difetto as difetto_nome','fiber_types.nome as tipologia_fibra_nome')
            ->join('users','users.id','qt_conformitas.user')
            ->leftJoin('machineries','machineries.id','qt_conformitas.macchina')
            ->leftJoin('defects','defects.id','qt_conformitas.difetto')
            ->leftJoin('fiber_types','fiber_types.id','qt_conformitas.tipologia_fibra')
            ->where('qt_conformitas.id',$this->id)
            ->first();

        switch ($this->stato) {
            case 1:
                $users = Utility::users_notify('qt.conformita.notification');
                break;
            case 2:
                $users = Utility::users_notify('qt.conformita.admin');
                break;
            case 3:
                $users = Utility::users_notify(['qt.conformita.notification','qt.conformita.admin']);
                break;
        }

        $info = array(
            'titolo' => $this->titolo,
            'obj' => $obj,
            'riapertura' => $this->riapertura
        );
        $subject = 'Non_Conformita_'.$obj->ol.'_'.$obj->macchina_nome;

        Mail::send('emails/email_non_conformita', compact('info'), function ($message) use ($users,$subject) {
            $message
                ->to($users)
                ->subject($subject);
        });
    }
}
