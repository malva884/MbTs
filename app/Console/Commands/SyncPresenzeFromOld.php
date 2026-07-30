<?php

namespace App\Console\Commands;

use App\Models\HrHoursRequested;
use App\Models\HrHoursRequestedDetail;
use App\Models\HrEmployee;
use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PresenzeDiscrepanciesExport;

class SyncPresenzeFromOld extends Command
{
    /**
     * The name and signature of the console command.
     *
     * # Sincronizza tutti i dati
     * php artisan sync:presenze-from-old
     *
     * # Sincronizza solo una data specifica
     * php artisan sync:presenze-from-old --date=2026-07-30
     *
     * @var string
     */
    protected $signature = 'sync:presenze-from-old {--date= : Data specifica (YYYY-MM-DD). Se non specificato, sincronizza tutti i dati}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizza le presenze dal vecchio sistema al nuovo. Se esiste già nel nuovo, non modifica ma logga la discrepanza e invia mail con Excel.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Inizio sincronizzazione presenze da vecchio sistema...');

        $date = $this->option('date');
        $discrepancies = [];

        // Recupera dati dal vecchio sistema con join per centro
        $query = DB::connection('mysql_old')
            ->table('employees_attendances')
            ->join('employees', 'employees.id', '=', 'employees_attendances.employee')
            ->select('employees_attendances.*', 'employees.centro')
            ->where('employees_attendances.type', '!=', 0); // Esclude revocate

        if ($date) {
            $query->where('employees_attendances.start_date', $date);
        }

        $oldAttendances = $query->get();

        $this->info("Trovati {$oldAttendances->count()} record nel vecchio sistema.");

        foreach ($oldAttendances as $oldAttendance) {
            $matricola = $oldAttendance->matricola;
            $data = $oldAttendance->start_date;
            $tipologiaOld = $oldAttendance->type;
            $hoursOld = $oldAttendance->hours;

            // Cerca nel nuovo sistema
            $newDetail = HrHoursRequestedDetail::where('dipendente_matricola', $matricola)
                ->where('data', $data)
                ->where('confermato', true)
                ->first();

            if (!$newDetail) {
                // Non esiste nel nuovo sistema - crea il record
                $this->createNewRecord($oldAttendance);
                $this->line("Creato nuovo record per matricola {$matricola} data {$data}");
            } else {
                // Esiste nel nuovo sistema - verifica discrepanze
                $tipologiaNew = $newDetail->tipologia;
                $hoursNew = $newDetail->ore_richieste;

                if ($tipologiaOld != $tipologiaNew || $hoursOld != $hoursNew) {
                    $discrepancies[] = [
                        'matricola' => $matricola,
                        'data' => $data,
                        'tipologia_old' => $tipologiaOld,
                        'tipologia_new' => $tipologiaNew,
                        'hours_old' => $hoursOld,
                        'hours_new' => $hoursNew,
                        'discrepanza_type' => $this->getDiscrepancyType($tipologiaOld, $tipologiaNew, $hoursOld, $hoursNew),
                    ];
                    $this->line("Discrepanza trovata per matricola {$matricola} data {$data}");
                }
            }
        }

        $this->info("Sincronizzazione completata. Discrepanze trovate: " . count($discrepancies));

        // Se ci sono discrepanze, genera Excel e invia mail
        if (!empty($discrepancies)) {
            $this->sendDiscrepanciesEmail($discrepancies);
        }

        return Command::SUCCESS;
    }

    /**
     * Crea un nuovo record nel sistema nuovo
     */
    private function createNewRecord($oldAttendance)
    {
        // Recupera informazioni dipendente
        $employee = HrEmployee::where('matricola', $oldAttendance->matricola)->first();

        if (!$employee) {
            $this->warn("Dipendente non trovato per matricola {$oldAttendance->matricola}");
            return;
        }

        // Se il nome è vuoto, usa il cognome o un valore di default
        $nome = $employee->nome ?? '';
        if (empty($nome)) {
            $nome = $employee->cognome ?? 'Dipendente';
        }

        // Crea HrHoursRequested
        $hrRequest = HrHoursRequested::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'bacheca_id' => 0,
            'bacheca_dipendente_id' => 0,
            'dipendente_nome' => $nome,
            'dipendente_cognome' => $employee->cognome ?? '',
            'dipendente_matricola' => $oldAttendance->matricola,
            'stato' => true,
            'data_richiesta' => now(),
            'note' => 'Importato da vecchio sistema',
            'tipologia' => $oldAttendance->type,
            'centro_di_costo' => $oldAttendance->centro ?? '',
        ]);

        // Crea HrHoursRequestedDetail
        HrHoursRequestedDetail::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'richiesta_id' => $hrRequest->id,
            'bacheca_id' => 0,
            'bacheca_dipendente_id' => 0,
            'dipendente_matricola' => $oldAttendance->matricola,
            'data' => $oldAttendance->start_date,
            'ore_richieste' => $oldAttendance->hours,
            'tipologia' => $oldAttendance->type,
            'confermato' => true,
        ]);
    }

    /**
     * Determina il tipo di discrepanza
     */
    private function getDiscrepancyType($tipologiaOld, $tipologiaNew, $hoursOld, $hoursNew)
    {
        if ($tipologiaOld != $tipologiaNew && $hoursOld != $hoursNew) {
            return 'different_all';
        } elseif ($tipologiaOld != $tipologiaNew) {
            return 'different_type';
        } elseif ($hoursOld != $hoursNew) {
            return 'different_hours';
        }
        return 'unknown';
    }

    /**
     * Genera Excel e invia mail con le discrepanze
     */
    private function sendDiscrepanciesEmail($discrepancies)
    {
        try {
            $fileName = 'discrepanze_presenze_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
            $filePath = storage_path('app/' . $fileName);

            // Genera Excel
            Excel::store(new PresenzeDiscrepanciesExport($discrepancies), $fileName, 'local');

            // Recupera destinatari dal sistema di notifiche
            $emails = Utility::users_notify(['hr_sync_presenze_discrepancies']);

            if (empty($emails)) {
                $this->warn('Nessun destinatario configurato per hr_sync_presenze_discrepancies.');
                // Cancella file temporaneo
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return;
            }

            // Invia mail
            Mail::raw('In allegato il file Excel con le discrepanze trovate durante la sincronizzazione delle presenze dal vecchio sistema.', function ($message) use ($filePath, $fileName, $emails) {
                $message->to($emails)
                    ->subject('Discrepanze sincronizzazione presenze')
                    ->attach($filePath, [
                        'as' => $fileName,
                        'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]);
            });

            // Cancella file temporaneo
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $this->info("Email inviata a " . count($emails) . " destinatari con " . count($discrepancies) . " discrepanze.");
            Log::info('Email discrepanze presenze inviata', ['count' => count($discrepancies), 'recipients' => count($emails)]);

        } catch (\Exception $e) {
            $this->error("Errore nell'invio dell'email: " . $e->getMessage());
            Log::error('Errore invio email discrepanze', ['error' => $e->getMessage()]);
        }
    }
}
