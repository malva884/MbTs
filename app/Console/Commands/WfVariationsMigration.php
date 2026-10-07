<?php

namespace App\Console\Commands;

use App\Models\WfCategory;
use App\Models\WfDocument;
use App\Models\WfRole;
use App\Models\WfVariations;
use App\Services\GoogleDrive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WfVariationsMigration extends Command
{
    /**
     * Il nome e la firma del comando Artisan.
     *
     * @var string
     */
    protected $signature = 'app:migra-variazioni {--limit=100 : Numero di record da elaborare per blocco}';

    /**
     * La descrizione del comando.
     *
     * @var string
     */
    protected $description = 'Migrazione storica delle variazioni (OL) dal vecchio DB: record, visualizzatori e firme.';

    /**
     * Mappa stati vecchi (int) -> nuovi (string).
     */
    private function mapStato($oldStatus): string
    {
        // Tutte le variazioni storiche risultano approvate:
        // solo lo status 4 (conclusa) mantiene lo stato 'End'.
        switch ((int) $oldStatus) {
            case 4:
                return 'End';
            case 1:
            case 2:
            case 3:
            default:
                return 'Approved';
        }
    }

    /**
     * Data di approvazione storica: end_date se presente, altrimenti updated_at.
     */
    private function mapDataApprovazione($oldVar): ?string
    {
        $date = $oldVar->end_date ?? $oldVar->updated_at;

        return !empty($date) ? date('Y-m-d', strtotime($date)) : null;
    }

    /**
     * Mappa utenti vecchio -> nuovo DB confrontando l'email.
     * Restituisce [old_user_id => new_user_id|null].
     */
    private function buildUserMap(): array
    {
        $oldUsers = DB::connection('mysql_old')->table('users')->get(['id', 'email']);
        $emails = $oldUsers->pluck('email')->filter()->unique()->values();
        $newByEmail = DB::table('users')->whereIn('email', $emails)->pluck('id', 'email');

        $userMap = [];
        foreach ($oldUsers as $oldUser) {
            $userMap[$oldUser->id] = $newByEmail[$oldUser->email] ?? null;
        }

        return $userMap;
    }

    /**
     * Sincronizza visualizzazioni ('Viewed') e firme ('Approved') storiche
     * in wf_user_approvals, inserendo solo le righe mancanti.
     */
    private function syncVariationUsers(string $variationId, $oldVariationId, array $userMap, $role, $viewRole): void
    {
        $oldUsers = DB::connection('mysql_old')->table('variation_users')
            ->where('variations', $oldVariationId)
            ->get();

        foreach ($oldUsers as $oldUser) {
            $userId = $userMap[$oldUser->user] ?? null;

            if (empty($userId)) {
                Log::warning("Migrazione variazioni: utente vecchio {$oldUser->user} non mappato via email (variazione vecchia {$oldVariationId}), riga saltata.");
                continue;
            }

            // Visualizzazione storica → riga 'Viewed' in wf_user_approvals
            if (!empty($oldUser->viewed) && !empty($viewRole->id)) {
                $exists = DB::table('wf_user_approvals')
                    ->where('model', 'WfVariations')
                    ->where('model_id', $variationId)
                    ->where('user_id', $userId)
                    ->where('approval_action', 'Viewed')
                    ->exists();

                if (!$exists) {
                    DB::table('wf_user_approvals')->insert([
                        'user_id' => $userId,
                        'role_id' => $viewRole->id,
                        'model_id' => $variationId,
                        'model' => 'WfVariations',
                        'approval_action' => 'Viewed',
                        'created_at' => $oldUser->data_view ?? $oldUser->updated_at ?? now(),
                        'updated_at' => $oldUser->updated_at ?? now(),
                    ]);
                }
            }

            // Firma storica: utente designato approvatore che ha approvato
            if (!empty($role->id) && !empty($oldUser->approve) && !empty($oldUser->aprovato)) {
                $exists = DB::table('wf_user_approvals')
                    ->where('model', 'WfVariations')
                    ->where('model_id', $variationId)
                    ->where('user_id', $userId)
                    ->where('approval_action', 'Approved')
                    ->exists();

                if (!$exists) {
                    DB::table('wf_user_approvals')->insert([
                        'user_id' => $userId,
                        'role_id' => $role->id,
                        'model_id' => $variationId,
                        'model' => 'WfVariations',
                        'approval_action' => 'Approved',
                        'created_at' => $oldUser->updated_at ?? now(),
                        'updated_at' => $oldUser->updated_at ?? now(),
                    ]);
                }
            }
        }
    }

    /**
     * Riconosce il file di log storico sul Drive: nome "Log*" oppure
     * pattern "{ol}_{YYYY-MM-DD}.pdf" (generato alla data di approvazione).
     */
    private function isLogFileName(string $name): bool
    {
        $name = strtolower($name);

        return str_starts_with($name, 'log') || (bool) preg_match('/_\d{4}-\d{2}-\d{2}\.pdf$/', $name);
    }

    /**
     * Sincronizza i documenti Drive della variazione:
     * - il PDF della variazione (nome semplice, es. "90015735.pdf") → id_file_drive + doc tipologia 5
     * - il log storico ("Log*" o "{ol}_{data}.pdf") → id_log_drive + doc tipologia 100
     * Corregge anche record dove il log datato era stato registrato come PDF della variazione.
     */
    private function syncDriveDocuments($variation): void
    {
        if (empty($variation->folder_drive))
            return;

        try {
            $files = GoogleDrive::search($variation->folder_drive, 'google', 'files');
        }
        catch (\Exception $e) {
            Log::warning("Migrazione variazioni: listing Drive fallito per variazione {$variation->id}: " . $e->getMessage());
            return;
        }

        if (empty($files))
            return;

        $pdfs = collect($files)->filter(fn ($f) => ($f->mimeType ?? null) === 'application/pdf');
        if ($pdfs->isEmpty())
            return;

        // Log: "Log*" o "{ol}_{data}.pdf". PDF variazione: preferito "{ol}.pdf", altrimenti primo non-log
        $logFile = $pdfs->first(fn ($f) => $this->isLogFileName((string) ($f->name ?? '')));
        $pdfFile = $pdfs->first(fn ($f) => ($f->name ?? '') === $variation->ol . '.pdf')
            ?? $pdfs->first(fn ($f) => !$this->isLogFileName((string) ($f->name ?? '')));

        // PDF della variazione
        if (!empty($pdfFile->id)) {
            if ($variation->id_file_drive !== $pdfFile->id)
                $variation->id_file_drive = $pdfFile->id;

            $docExists = DB::table('wf_documents')
                ->where('model', WfVariations::$modelName)
                ->where('model_id', $variation->id)
                ->where('id_file_drive', $pdfFile->id)
                ->exists();

            if (!$docExists) {
                // Riutilizza la riga tipologia 5 esistente se puntava al file sbagliato (es. log datato)
                $oldDoc = DB::table('wf_documents')
                    ->where('model', WfVariations::$modelName)
                    ->where('model_id', $variation->id)
                    ->where('tipologia', 5)
                    ->first();

                if (!empty($oldDoc->id)) {
                    DB::table('wf_documents')->where('id', $oldDoc->id)
                        ->update(['id_file_drive' => $pdfFile->id, 'nome_file' => $pdfFile->name, 'updated_at' => now()]);
                }
                else {
                    WfDocument::addDocument(
                        WfVariations::$modelName,
                        $variation->id,
                        $variation->ol,
                        $pdfFile->name,
                        5,
                        $pdfFile->id,
                        $variation->id
                    );
                }
            }
        }

        // Log storico: solo se distinto dal PDF della variazione
        if (!empty($logFile->id) && $logFile->id !== $variation->id_file_drive) {
            if (empty($variation->id_log_drive))
                $variation->id_log_drive = $logFile->id;

            $docLog = DB::table('wf_documents')
                ->where('model', WfVariations::$modelName)
                ->where('model_id', $variation->id)
                ->where('id_file_drive', $logFile->id)
                ->first();

            if (!empty($docLog->id)) {
                // Il file era registrato come PDF variazione (tipologia 5) → diventa log
                if ($docLog->tipologia != 100)
                    DB::table('wf_documents')->where('id', $docLog->id)
                        ->update(['tipologia' => 100, 'updated_at' => now()]);
            }
            else {
                WfDocument::addDocument(
                    WfVariations::$modelName,
                    $variation->id,
                    $variation->ol,
                    $logFile->name,
                    100,
                    $logFile->id,
                    $variation->id
                );
            }
        }

        if ($variation->isDirty())
            $variation->save();
    }

    /**
     * Aggiorna una variazione già migrata: stato forzato, creator corretto
     * via email, documenti Drive riallineati e firme/visualizzazioni mancanti.
     */
    private function updateExisting($existing, $oldVar, array $userMap, $role, $viewRole): void
    {
        $stato = $this->mapStato($oldVar->status);
        $creator = $userMap[$oldVar->user_creator] ?? null;

        if (empty($creator))
            Log::warning("Migrazione variazioni: creator vecchio {$oldVar->user_creator} non mappato via email (variazione vecchia {$oldVar->id}), mantenuto valore esistente.");

        $update = [
            'stato' => $stato,
            'data_approvazione' => $this->mapDataApprovazione($oldVar),
            'end_date' => $stato === 'End' ? ($oldVar->end_date ?: null) : null,
        ];

        if (!empty($creator))
            $update['creator'] = $creator;

        DB::table('wf_variations')->where('id', $existing->id)->update($update);

        // Riallinea documenti Drive se manca il PDF variazione, il log,
        // o se il doc registrato punta al file di log datato
        $model = WfVariations::find($existing->id);
        if (!empty($model?->folder_drive)) {
            $doc5 = DB::table('wf_documents')
                ->where('model', WfVariations::$modelName)
                ->where('model_id', $existing->id)
                ->where('tipologia', 5)
                ->first();

            $docWrong = empty($doc5) || $this->isLogFileName((string) $doc5->nome_file);
            if (empty($model->id_file_drive) || empty($model->id_log_drive) || $docWrong)
                $this->syncDriveDocuments($model);
        }

        $this->syncVariationUsers($existing->id, $oldVar->id, $userMap, $role, $viewRole);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');

        $this->info("Inizio migrazione variazioni (Blocchi da: {$limit})...");

        // Ruoli per WfVariations (auto-creati se mancanti): servono per
        // registrare firme storiche ('Approved') e letture ('Viewed')
        $role = WfRole::firstOrCreate(
            ['model' => 'WfVariations', 'role' => 'Approvatore'],
            ['disabled' => false]
        );

        $viewRole = WfRole::firstOrCreate(
            ['model' => 'WfVariations', 'role' => 'Visualizzatore'],
            ['disabled' => false]
        );

        // Mappa utenti vecchio -> nuovo DB tramite email
        $userMap = $this->buildUserMap();
        $mapped = count(array_filter($userMap));
        $this->info('Utenti mappati via email: ' . $mapped . ' su ' . count($userMap));

        $query = DB::connection('mysql_old')->table('variations')
            ->select('id', 'user_creator', 'ol', 'status', 'end_date', 'type', 'text', 'rev', 'folder_drive_id', 'category', 'created_at', 'updated_at')
            ->orderBy('created_at', 'asc');

        $totalRecords = $query->count();
        if ($totalRecords === 0) {
            $this->info('Nessun record trovato nel vecchio database.');
            return Command::SUCCESS;
        }

        $this->info("Record totali da analizzare: {$totalRecords}");

        $processed = 0;
        $skipped = 0;
        $updated = 0;

        $query->chunk($limit, function ($oldVariations) use (&$processed, &$skipped, &$updated, $role, $viewRole, $userMap, $totalRecords) {
            foreach ($oldVariations as $oldVar) {
                try {
                    // 1. Record già migrato → aggiorna stato/creator e sincronizza firme
                    $existing = DB::table('wf_variations')
                        ->where('ol', $oldVar->ol)
                        ->where('revisione', $oldVar->rev ?? 0)
                        ->whereDate('created_at', date('Y-m-d', strtotime($oldVar->created_at)))
                        ->first();

                    if (!empty($existing)) {
                        $this->updateExisting($existing, $oldVar, $userMap, $role, $viewRole);
                        $skipped++;
                        $updated++;
                        $processed++;
                        continue;
                    }

                    // 2. Categoria: match per folder_drive o per nome
                    $category = null;
                    if (!empty($oldVar->category)) {
                        $oldCat = DB::connection('mysql_old')->table('workflow_categories')
                            ->where('id', $oldVar->category)
                            ->first();

                        if (!empty($oldCat)) {
                            $catName = $oldCat->name ?? $oldCat->categoria ?? 'Varie';
                            $oldFolder = $oldCat->folder_drive_id ?? $oldCat->folder_drive ?? null;
                            $category = DB::table('wf_categories')
                                ->where('model', 'WfVariations')
                                ->where(function ($q) use ($catName, $oldFolder) {
                                    if ($oldFolder)
                                        $q->where('folder_drive', $oldFolder)
                                            ->orWhere('categoria', $catName);
                                    else
                                        $q->where('categoria', $catName);
                                })
                                ->first();

                            if (empty($category)) {
                                // crea la categoria per WfVariations riusando la cartella Drive
                                $newCat = new WfCategory();
                                $newCat->categoria = $catName;
                                $newCat->model = 'WfVariations';
                                $newCat->folder_drive = $oldFolder;
                                $newCat->save();
                                $category = $newCat;
                            }
                        }
                    }

                    // 3. File allegati storici (PDF principale e log)
                    $fileMain = DB::connection('mysql_old')->table('variation_files')
                        ->where('variations', $oldVar->id)
                        ->where('nomeFile', 'not like', 'Log%')
                        ->orderBy('created_at', 'asc')
                        ->first();

                    $fileLog = DB::connection('mysql_old')->table('variation_files')
                        ->where('variations', $oldVar->id)
                        ->where('nomeFile', 'like', 'Log%')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    // 4. Scrittura del record
                    $stato = $this->mapStato($oldVar->status);

                    // Creator: vecchio user_id → nuovo user_id via email
                    $creator = $userMap[$oldVar->user_creator] ?? null;
                    if (empty($creator))
                        Log::warning("Migrazione variazioni: creator vecchio {$oldVar->user_creator} non mappato via email (variazione vecchia {$oldVar->id}), mantenuto id originale.");

                    $variation = new WfVariations();
                    $variation->creator = $creator ?? $oldVar->user_creator;
                    $variation->ol = $oldVar->ol;
                    $variation->revisione = $oldVar->rev ?? 0;
                    $variation->testo = $oldVar->text ?? null;
                    $variation->stato = $stato;
                    $variation->tipologia = $oldVar->type ?? null;
                    $variation->categoria_id = !empty($category->id) ? $category->id : null;
                    $variation->data_approvazione = $this->mapDataApprovazione($oldVar);
                    $variation->end_date = ($stato === 'End' && !empty($oldVar->end_date)) ? $oldVar->end_date : null;
                    $variation->folder_drive = $oldVar->folder_drive_id ?? null;
                    $variation->id_file_drive = !empty($fileMain->path_drive) ? $fileMain->path_drive : null;
                    $variation->id_log_drive = !empty($fileLog->path_drive) ? $fileLog->path_drive : null;
                    $variation->visibile = true;
                    $variation->created_at = $oldVar->created_at;
                    $variation->updated_at = $oldVar->updated_at;
                    $variation->save();

                    // 5. Documenti
                    if (!empty($fileMain->path_drive)) {
                        WfDocument::addDocument(
                            WfVariations::$modelName,
                            $variation->id,
                            $oldVar->ol,
                            $fileMain->nomeFile,
                            5,
                            $fileMain->path_drive,
                            $variation->id
                        );
                    }

                    if (!empty($fileLog->path_drive)) {
                        WfDocument::addDocument(
                            WfVariations::$modelName,
                            $variation->id,
                            $oldVar->ol,
                            $fileLog->nomeFile,
                            100,
                            $fileLog->path_drive,
                            $variation->id
                        );
                    }

                    // 5b. Fallback Drive: recupera PDF variazione e log storico dalla cartella
                    if ((empty($variation->id_file_drive) || empty($variation->id_log_drive)) && !empty($variation->folder_drive))
                        $this->syncDriveDocuments($variation);

                    // 6. Visualizzatori e firme storiche (user_id mappati via email)
                    $this->syncVariationUsers($variation->id, $oldVar->id, $userMap, $role, $viewRole);
                }
                catch (\Exception $e) {
                    Log::error("Errore durante la migrazione variazione ID vecchio {$oldVar->id}: " . $e->getMessage());
                }

                $processed++;
            }

            $this->info("Elaborati {$processed} di {$totalRecords} record (già presenti aggiornati: {$updated})...");
        });

        $this->info("Migrazione conclusa. Elaborati: {$processed}, già presenti aggiornati: {$updated}, inseriti nuovi: " . ($processed - $updated) . ".");

        return Command::SUCCESS;
    }
}
