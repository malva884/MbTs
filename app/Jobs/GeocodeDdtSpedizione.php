<?php

namespace App\Jobs;

use App\Models\DdtSpedizione;
use App\Services\GeocodingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Geocodifica l'indirizzo di destinazione di un DDT e salva lat/lng/paese
 * sul record. I risultati (e i miss) sono cachati su geo_cache.
 */
class GeocodeDdtSpedizione implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $timeout = 60;

    public function __construct(
        protected string $ddtId,
    ) {}

    /**
     * Esecuzione del Job: geocodifica e aggiorna il DDT.
     * Restituisce true se le coordinate sono state valorizzate.
     */
    public function handle(): bool
    {
        $ddt = DdtSpedizione::find($this->ddtId);

        if (!$ddt || empty($ddt->destinazione_indirizzo)) {
            return false;
        }

        // Gia geocodificato: niente da fare
        if ($ddt->destinazione_lat !== null && $ddt->destinazione_lng !== null) {
            return true;
        }

        $geo = GeocodingService::geocode(
            $ddt->destinazione_indirizzo,
            $ddt->destinazione_provincia,
            $ddt->destinazione_regione
        );

        if (!$geo) {
            Log::warning("[GeocodeDdtSpedizione] Indirizzo non geocodificato per DDT {$ddt->numero_ddt}: {$ddt->destinazione_indirizzo}");
            return false;
        }

        $ddt->update([
            'destinazione_lat' => $geo['lat'],
            'destinazione_lng' => $geo['lng'],
            'destinazione_paese' => $geo['paese'],
        ]);

        return true;
    }
}
