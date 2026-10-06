<?php

namespace App\Console\Commands;

use App\Models\DdtSpedizione;
use App\Services\GeocodingService;
use Illuminate\Console\Command;

class GeocodeDdtSpedizioniCommand extends Command
{
    protected $signature = 'app:geocode-ddt-spedizioni
        {--limit= : Numero massimo di DDT da geocodificare}
        {--force : Rigeocodifica anche i DDT gia provvisti di coordinate}';

    protected $description = 'Geocodifica (backfill) gli indirizzi di destinazione dei DDT di spedizione via Nominatim con cache su geo_cache';

    public function handle()
    {
        $query = DdtSpedizione::whereNotNull('destinazione_indirizzo')
            ->where('destinazione_indirizzo', '<>', '');

        if (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('destinazione_lat')->orWhereNull('destinazione_lng');
            });
        }

        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $totale = $query->count();
        if ($totale === 0) {
            $this->info('[GeocodeDdtSpedizioniCommand] Nessun DDT da geocodificare.');
            return 0;
        }

        $this->info("[GeocodeDdtSpedizioniCommand] Geocoding di {$totale} DDT (rate limit 1 req/s: stimati ~" . ceil($totale * 1.1 / 60) . " min max)...");

        $ok = 0;
        $ko = 0;

        $query->chunkById(100, function ($ddts) use (&$ok, &$ko) {
            foreach ($ddts as $ddt) {
                try {
                    $geo = GeocodingService::geocode(
                        $ddt->destinazione_indirizzo,
                        $ddt->destinazione_provincia,
                        $ddt->destinazione_regione
                    );

                    if ($geo) {
                        $ddt->update([
                            'destinazione_lat' => $geo['lat'],
                            'destinazione_lng' => $geo['lng'],
                            'destinazione_paese' => $geo['paese'],
                        ]);
                        $ok++;
                        $this->info("  {$ddt->numero_ddt}: {$geo['lat']},{$geo['lng']} ({$geo['paese']})");
                    } else {
                        $ko++;
                        $this->warn("  {$ddt->numero_ddt}: non geocodificato ({$ddt->destinazione_indirizzo})");
                    }
                } catch (\Exception $e) {
                    $ko++;
                    $this->error("  {$ddt->numero_ddt}: errore {$e->getMessage()}");
                }
            }
        });

        $this->info("[GeocodeDdtSpedizioniCommand] Completato: {$ok} geocodificati, {$ko} non risolti.");

        return 0;
    }
}
