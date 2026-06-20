<?php

namespace App\Console\Commands;

use App\Models\WfDocument;
use App\Models\WfOrder;
use App\Services\GoogleDrive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WfOrderMigration extends Command
{
    /**
     * Il nome e la firma del comando Artisan.
     *
     * @var string
     */
    protected $signature = 'app:migra-commesse {--limit=100 : Numero di record da elaborare per blocco}';

    /**
     * La descrizione del comando.
     *
     * @var string
     */
    protected $description = 'Migrazione storica immediata di commesse (1) e revisioni (3) con approvazione forzata automatica e gestione della memoria.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Recuperiamo il limite passato come opzione (default: 100)
        $limit = (int) $this->option('limit');

        $this->info("Inizio migrazione rapida (Stato: Approvato Forzato, Blocchi da: {$limit})...");

        // Prepariamo la query base sul vecchio database
        // Ordiniamo prima per 'type' (1 prima di 3) e poi per data di creazione
        $query = DB::connection('mysql_old')->table('workflows')
            ->join('workflow_files', 'workflow_files.Workflow', '=', 'workflows.id')
            ->select(
                'workflows.id as old_workflow_id',
                'workflows.commessa_name',
                'workflows.type as old_type',
                'workflows.created_at',
                'workflows.status',
                'workflows.end_date',
                'workflow_files.path_folder_drive as old_folder_drive',
                'workflow_files.path_drive as old_file_drive',
                'workflow_files.nomeFile'
            )
            ->whereIn('workflows.type', [1, 3])
            ->orderBy('workflows.type', 'asc')
            ->orderBy('workflows.created_at', 'asc');

        // Contiamo i record totali solo per mostrare una stima all'utente
        $totalRecords = $query->count();
        if ($totalRecords === 0) {
            $this->info('Nessun record trovato nel vecchio database.');
            return Command::SUCCESS;
        }

        $this->info("Record totali da analizzare: {$totalRecords}");

        // Usiamo chunkById per i blocchi se disponibile, oppure un'elaborazione a blocchi standard
        // Nota: Poiché dobbiamo mantenere un ordinamento specifico (type ASC, data ASC),
        // usiamo chunk() classico aggiornando la memoria ad ogni passo.
        $processed = 0;

        $query->chunk($limit, function ($oldWorkflows) use (&$processed, $totalRecords) {
            $p= 0;
            foreach ($oldWorkflows as $oldWf) {
                if($p == 20)
                    break;
                try {
                    $commessa = $oldWf->commessa_name;
                    $tipologiaNuova = $oldWf->old_type;

                    // Estrazione del numero di revisione per il tipo 3
                    $revisione = null;
                    if ($tipologiaNuova == 3) {
                        $tmpName = explode(' ', $oldWf->nomeFile);
                        $revisione = isset($tmpName[1]) ? str_ireplace(['r', '.pdf'], '', $tmpName[1]) : '1';
                    }

                    // 1. Esclusione duplicati nel nuovo DB
                    $existsQuery = DB::table('wf_orders')
                        ->where('commessa', $commessa)
                        ->where('tipologia', $tipologiaNuova);

                    if ($revisione) {
                        $existsQuery->where('revisione', $revisione);
                    }

                    if ($existsQuery->exists()) {
                        $processed++;
                        continue;
                    }

                    // 2. Recupero ID padre per le revisioni
                    $idCommessaPadre = null;
                    if ($tipologiaNuova == 3) {
                        $padre = DB::table('wf_orders')
                            ->where('commessa', $commessa)
                            ->where('tipologia', 1)
                            ->first();

                        if ($padre) {
                            $idCommessaPadre = $padre->id;
                        } else {
                            Log::warning("Migrazione - Revisione ID vecchio {$oldWf->old_workflow_id} saltata: Padre {$commessa} non trovato.");
                            $processed++;
                            continue;
                        }
                    }

                    // 3. Recupero la categoria
                    $catStr = substr($commessa, 0, 3);
                    $category = DB::table('wf_categories')->where('categoria', '=', $catStr)->first();

                    if (empty($category)) {
                        Log::error("Migrazione - Categoria '{$catStr}' non trouvata per {$commessa}. Salto.");
                        $processed++;
                        continue;
                    }

                    // 4. Ricerca del log storico su Drive (se disponibile nella vecchia cartella)
                    $idLogDrive = null;
                    if (!empty($oldWf->old_folder_drive)) {
                        $dateForLog = $oldWf->end_date ?? date('Y-m-d', strtotime($oldWf->created_at));
                        $idLogDrive = GoogleDrive::search(
                            $oldWf->old_folder_drive,
                            'google',
                            'file',
                            $commessa . '_' . $dateForLog . '.pdf',
                            false
                        );
                    }

                    // 5. Scrittura del record WfOrder forzando lo stato ad approvato
                    $workflow = new WfOrder();
                    $workflow->creator = 5;
                    $workflow->stato = 'Approved';
                    $workflow->data_approvazione = $oldWf->end_date ?? date('Y-m-d', strtotime($oldWf->created_at));

                    $workflow->categoria_id = $category->id;
                    $workflow->commessa_sistema = $commessa;
                    $workflow->tipologia = $tipologiaNuova;
                    $workflow->revisione = $revisione;
                    $workflow->commessa = $commessa;
                    $workflow->created_at = $oldWf->created_at;

                    // Manteniamo gli ID nativi di Google Drive senza riscaricare nulla
                    $workflow->folder_drive = $oldWf->old_folder_drive;
                    $workflow->id_file_drive = $oldWf->old_file_drive;
                    $workflow->id_log_drive = $idLogDrive;

                    $workflow->visibile = ($tipologiaNuova == 1);
                    $workflow->id_commessa_padre = $idCommessaPadre;
                    $workflow->save();

                    // 6. Registrazione Documento Principale
                    WfDocument::addDocument(
                        $workflow::$modelName,
                        $workflow->id,
                        $commessa,
                        $oldWf->nomeFile,
                        $tipologiaNuova,
                        $workflow->id_file_drive,
                        $idCommessaPadre ?? $workflow->id
                    );

                    // 7. Registrazione Documento di Log (se rintracciato su Drive)
                    if ($idLogDrive) {
                        WfDocument::addDocument(
                            $workflow::$modelName,
                            $workflow->id,
                            $commessa,
                            'Log_' . $commessa . '.pdf',
                            100,
                            $idLogDrive,
                            $idCommessaPadre ?? $workflow->id
                        );
                    }

                } catch (\Exception $e) {
                    Log::error("Errore durante la migrazione del record ID vecchio {$oldWf->old_workflow_id}: " . $e->getMessage());
                }
                $p++;
                $processed++;
            }

            $this->info("Elaborati {$processed} di {$totalRecords} record...");
        });

        $this->info('Migrazione conclusa. Tutti i record inseriti risultano Approvati ed elaborati a blocchi.');

        return Command::SUCCESS;
    }
}
