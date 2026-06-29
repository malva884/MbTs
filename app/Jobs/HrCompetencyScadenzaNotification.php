<?php

namespace App\Jobs;

use App\Models\HrCompetencyEvaluation;
use App\Models\HrEmployee;
use App\Models\Utility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class HrCompetencyScadenzaNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $anno = date('Y');
        $meseCorrente = (int)date('n');

        if ($meseCorrente < 10) {
            return;
        }

        $dipendenti = HrEmployee::where('dimesso', false)
            ->with(['roles' => function ($q) {
                $q->where('disattivo', false)->with(['activities' => function ($q2) {
                    $q2->where('disattivo', false);
                }]);
            }, 'department'])
            ->get();

        $raggruppatiPerReparto = [];

        foreach ($dipendenti as $dipendente) {
            $attivitaTotali = 0;
            $attivitaValutate = 0;
            $seenActivityIds = [];

            foreach ($dipendente->roles as $role) {
                foreach ($role->activities as $activity) {
                    if (in_array($activity->id, $seenActivityIds)) continue;
                    $seenActivityIds[] = $activity->id;

                    $attivitaTotali++;
                    $eval = HrCompetencyEvaluation::where('employee_id', $dipendente->id)
                        ->where('activity_id', $activity->id)
                        ->where('anno', $anno)
                        ->first();
                    if ($eval) {
                        $attivitaValutate++;
                    }
                }
            }

            if ($attivitaTotali > 0 && $attivitaValutate < $attivitaTotali) {
                $repartoId = $dipendente->reparto_id;
                if (!isset($raggruppatiPerReparto[$repartoId])) {
                    $raggruppatiPerReparto[$repartoId] = [];
                }
                $raggruppatiPerReparto[$repartoId][] = [
                    'nome' => $dipendente->nome,
                    'cognome' => $dipendente->cognome,
                    'matricola' => $dipendente->matricola,
                    'valutate' => $attivitaValutate,
                    'totali' => $attivitaTotali,
                ];
            }
        }

        $users = Utility::users_notify(['hr_scadenza_formazioni']);

        foreach ($raggruppatiPerReparto as $repartoId => $dipendentiDaValutare) {
            $info = [
                'anno' => $anno,
                'dipendenti' => $dipendentiDaValutare,
            ];

            Mail::send('emails.email_valutazione_scadenza', compact('info'), function ($message) use ($users) {
                $message
                    ->to($users)
                    ->subject('Promemoria: Valutazioni competenze in scadenza');
            });
        }
    }
}
