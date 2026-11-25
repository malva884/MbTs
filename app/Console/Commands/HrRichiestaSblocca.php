<?php

namespace App\Console\Commands;

use App\Jobs\RichiesteGiorniDipendenti;
use App\Models\HrApproverRequest;
use App\Models\HrHoursRequested;
use App\Models\HrHoursRequestedDetail;
use App\Models\HrRequestPending;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HrRichiestaSblocca extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:hr-richiesta-sblocca 
                            {id : UUID della richiesta (hr_hours_requesteds.id)}
                            {--riapri-livello= : Riapre (stato=NULL) i record pending del livello specificato}
                            {--approva : Forza approvazione definitiva della richiesta}
                            {--boccia : Forza rifiuto definitivo della richiesta}
                            {--retry-job : Riesegue RichiesteGiorniDipendenti per l\'ultimo pending deciso}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnostica e sblocca una richiesta HR bloccata nel flusso di approvazione';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $id = $this->argument('id');
        $richiesta = HrHoursRequested::find($id);

        if (!$richiesta) {
            $this->error("Richiesta non trovata con ID: {$id}");
            return Command::FAILURE;
        }

        $this->info("=== DIAGNOSI RICHIESTA HR ===");
        $this->line("ID:             {$richiesta->id}");
        $this->line("Dipendente:     {$richiesta->dipendente_cognome} {$richiesta->dipendente_nome} (Matricola: {$richiesta->dipendente_matricola})");
        $this->line("Centro Costo:   {$richiesta->centro_di_costo}");
        $this->line("Data Richiesta: {$richiesta->data_richiesta}");
        $this->line("Stato Richiesta: " . (is_null($richiesta->stato) ? 'NULL (In Attesa)' : ($richiesta->stato == 1 ? '1 (APPROVATA)' : '0 (RIFIUTATA)')));

        $pendings = HrRequestPending::where('richiesta_id', $id)
            ->orderBy('livello', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $this->newLine();
        $this->info("=== PASSAGGI DI APPROVAZIONE (hr_request_pendings) ===");
        $pendingTable = [];
        foreach ($pendings as $p) {
            $statoDesc = is_null($p->stato) ? 'NULL (Aperto)' : ($p->stato == 1 ? '1 (Approvato)' : '0 (Rifiutato)');
            $pendingTable[] = [
                'ID'          => substr($p->id, 0, 8) . '...',
                'Livello'     => $p->livello,
                'User ID'     => $p->user_id,
                'Approvatore' => $p->approvatore ?? '-',
                'Stato'       => $statoDesc,
                'Created At'  => (string) $p->created_at,
                'Updated At'  => (string) $p->updated_at,
            ];
        }
        $this->table(['ID', 'Livello', 'User ID', 'Approvatore', 'Stato', 'Created At', 'Updated At'], $pendingTable);

        // Opzione 1: Riapri livello
        if ($this->option('riapri-livello') !== null) {
            $livello = (int) $this->option('riapri-livello');
            $count = DB::table('hr_request_pendings')
                ->where('richiesta_id', $id)
                ->where('livello', $livello)
                ->update([
                    'stato' => null,
                    'approvatore' => null,
                    'updated_at' => now(),
                ]);

            $this->info("Riaperti {$count} record di livello {$livello} per la richiesta {$id}. Ora i bottoni di approvazione sono visibili agli approvatori.");
            return Command::SUCCESS;
        }

        // Opzione 2: Forza approvazione definitiva
        if ($this->option('approva')) {
            $richiesta->stato = 1;
            $richiesta->save();

            HrHoursRequestedDetail::where('richiesta_id', $id)->update(['confermato' => true]);

            $this->info("Richiesta {$id} approvata forzatamente con successo (stato=1, dettagli confermati).");
            return Command::SUCCESS;
        }

        // Opzione 3: Forza bocciatura definitiva
        if ($this->option('boccia')) {
            $richiesta->stato = 0;
            $richiesta->save();

            HrHoursRequestedDetail::where('richiesta_id', $id)->update(['confermato' => false]);

            $this->info("Richiesta {$id} bocciata con successo (stato=0, dettagli non confermati).");
            return Command::SUCCESS;
        }

        // Opzione 4: Riprova il Job
        if ($this->option('retry-job')) {
            $lastPending = HrRequestPending::where('richiesta_id', $id)
                ->whereNotNull('stato')
                ->orderBy('livello', 'desc')
                ->orderBy('updated_at', 'desc')
                ->first();

            if (!$lastPending) {
                $this->error("Nessun pending con stato impostato trovato per rieseguire il job.");
                return Command::FAILURE;
            }

            $this->info("Dispatch RichiesteGiorniDipendenti({$id}, {$lastPending->user_id})...");
            dispatch_sync(new RichiesteGiorniDipendenti($id, $lastPending->user_id));
            $this->info("Job eseguito sincronicamente.");
            return Command::SUCCESS;
        }

        // Suggerimento automatico se nessun opzione fornita
        $hasOpenPending = $pendings->contains(fn($p) => is_null($p->stato));
        if (is_null($richiesta->stato) && !$hasOpenPending && $pendings->isNotEmpty()) {
            $this->newLine();
            $this->warn("ANOMALIA RILEVATA: Tutti i passaggi pending sono stati decisi, ma la richiesta principale ha stato NULL.");
            $this->line("Comandi disponibili:");
            $this->line("  --retry-job       : Riesegue l'avanzamento / chiusura tramite il Job");
            $this->line("  --approva         : Forza la chiusura immediata come APPROVATA");
            $this->line("  --boccia          : Forza la chiusura immediata come RIFIUTATA");
            $this->line("  --riapri-livello=N: Riapre un livello per consentire una nuova approvazione");
        }

        return Command::SUCCESS;
    }
}
