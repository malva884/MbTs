<?php

namespace App\Observers;

use App\Models\QtFai;
use App\Services\GoogleDrive;
use Illuminate\Support\Facades\Log;

class QtFaiObserver
{
    /**
     * Gestisce l'evento "creating" (prima del salvataggio).
     */
    public function creating(QtFai $qtFai): void
    {
        Log::info("Inizio Observers");
        $annoCorrente = now()->year;

        $ultimoFai = QtFai::whereYear('created_at', $annoCorrente)
            ->orderBy('created_at', 'desc')
            ->orderBy('codice', 'desc')
            ->first();

        $progressivo = 1;

        if ($ultimoFai && $ultimoFai->codice) {
            $parti = explode('-', $ultimoFai->codice);
            Log::info("Ultimo Fai: " . $ultimoFai->codice);
            if (isset($parti[0])) {
                $progressivo = ((int) $parti[0]) + 1;
            }
        }
        Log::info("codice: " . $progressivo . '-' . $annoCorrente);

        $qtFai->codice = $progressivo . '-' . $annoCorrente;
        $qtFai->esito = 'IN_CORSO';
    }

    /**
     * Gestisce l'evento "created" (dopo il salvataggio).
     */
    public function created(QtFai $qtFai): void
    {
        $disk = 'google';
        $annoCorrente = now()->year;
        $parentFolderId = config('services.google_drive.fai_folder_id');

        try {
            // 1. Cerca o crea la cartella dell'anno (es. "2026")
            $yearFolderId = GoogleDrive::search($parentFolderId, $disk, 'dir', (string)$annoCorrente, true);

            if (!$yearFolderId) {
                Log::error("Impossibile trovare o creare la cartella dell'anno {$annoCorrente} su Google Drive.");
                return;
            }

            // 2. Crea la sotto-cartella del FAI (es. "FAI_1-2026")
            $nomeCartellaFai = "FAI_" . $qtFai->codice;
            $folderId = GoogleDrive::add_folder($yearFolderId, $nomeCartellaFai, $disk);

            if ($folderId) {
                // Salva solo il drive_id nel database
                $qtFai->timestamps = false;
                $qtFai->update(['drive_id' => $folderId]);
            }
        } catch (\Exception $e) {
            Log::error("Errore nell'Observer FAI durante la creazione delle cartelle Drive: " . $e->getMessage());
        }
    }
}
