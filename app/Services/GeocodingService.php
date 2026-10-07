<?php

namespace App\Services;

use App\Models\GeoCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Geocoding di indirizzi di destinazione via Nominatim (OpenStreetMap).
 *
 * - Gratuito e worldwide: precisione fino al civico quando l'indirizzo e completo.
 * - Cache permanente su tabella geo_cache: i destinatari ricorrenti si risolvono
 *   una sola volta; anche i miss vengono memorizzati per non martellare l'API.
 * - Fallback a scalare: indirizzo completo -> CAP + comune -> provincia/regione
 *   (solo indirizzi italiani) per massimizzare la copertura su indirizzi sporchi.
 * - Rate limit Nominatim (1 req/s) gestito con sleep minimo tra le chiamate live.
 */
class GeocodingService
{
    protected const ENDPOINT = 'https://nominatim.openstreetmap.org/search';

    /** Secondi minimi tra due richieste live a Nominatim. */
    protected const MIN_INTERVAL = 1.1;

    /** Timestamp (microtime) dell'ultima richiesta live effettuata in questo processo. */
    protected static ?float $lastCallAt = null;

    /**
     * Geocodifica una destinazione. Restituisce ['lat','lng','paese','display_name','tipo']
     * oppure null se nessuna strategia ha prodotto coordinate.
     * Il parametro $paese (ISO alpha-2 o nome) vincola la ricerca alla nazione giusta.
     */
    public static function geocode(?string $indirizzo, ?string $provincia = null, ?string $regione = null, ?string $paese = null): ?array
    {
        if (empty($indirizzo)) {
            return null;
        }

        $paeseIso = CalcoloCostoSpedizioneService::normalizzaNazione($paese)
            ?? CalcoloCostoSpedizioneService::estraiNazione($indirizzo);
        $countryCode = $paeseIso ? mb_strtolower($paeseIso) : null;

        foreach (self::candidateQueries($indirizzo, $provincia, $regione, $paeseIso) as $query) {
            $chiave = self::chiave($query, $countryCode);

            $cached = GeoCache::where('chiave', $chiave)->first();
            if ($cached) {
                // Cache hit: anche i miss sono memorizzati (lat null)
                if ($cached->lat !== null) {
                    return [
                        'lat' => (float) $cached->lat,
                        'lng' => (float) $cached->lng,
                        'paese' => $cached->paese,
                        'display_name' => $cached->display_name,
                        'tipo' => $cached->tipo,
                    ];
                }

                continue;
            }

            $result = self::nominatim($query, $countryCode);

            if ($result === null) {
                // Errore di rete/HTTP: non cachare, riprova la prossima volta
                return null;
            }

            GeoCache::create([
                'chiave' => $chiave,
                'indirizzo_ricerca' => mb_substr($query, 0, 500),
                'lat' => $result['lat'] ?? null,
                'lng' => $result['lng'] ?? null,
                'paese' => $result['paese'] ?? null,
                'country_code' => $result['country_code'] ?? null,
                'display_name' => isset($result['display_name']) ? mb_substr($result['display_name'], 0, 700) : null,
                'tipo' => $result['tipo'] ?? null,
            ]);

            if (!empty($result['lat'])) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Genera le query di ricerca a scalare per massimizzare la probabilita di match.
     * Funziona per destinazioni di qualsiasi paese: il suffisso nazione viene
     * aggiunto solo quando noto, per non vincolare Nominatim al paese sbagliato.
     */
    public static function candidateQueries(?string $indirizzo, ?string $provincia = null, ?string $regione = null, ?string $paese = null): array
    {
        $queries = [];

        // Ripulisce l'indirizzo dai token tra parentesi non significativi per
        // Nominatim (es. "(08)" = codice distretto tedesco) e normalizza gli spazi
        $pulito = trim((string) $indirizzo);
        $pulito = trim((string) preg_replace('/\s*\([^)]*\)/u', ' ', $pulito));
        $pulito = trim((string) preg_replace('/\s{2,}/u', ' ', $pulito));

        if ($pulito !== '') {
            $queries[] = $pulito;
        }

        $paeseIso = CalcoloCostoSpedizioneService::normalizzaNazione($paese)
            ?? CalcoloCostoSpedizioneService::estraiNazione($pulito);
        $nomePaese = $paeseIso ? (CalcoloCostoSpedizioneService::NAZIONE_NOME_EN[$paeseIso] ?? $paeseIso) : null;

        // Italia se esplicita oppure se sconosciuta ma l'indirizzo ha formato italiano
        $isItalia = $paeseIso === 'IT' || ($paeseIso === null && $provincia !== null);

        $comune = CalcoloCostoSpedizioneService::estraiComune($pulito);

        $cap = null;
        if (preg_match('/\b(\d{4,6})\b/u', mb_strtoupper($pulito), $m)) {
            $cap = $m[1];
        }

        if ($isItalia) {
            // CAP -> ricerca per CAP, piu affidabile del civico su Nominatim
            if ($cap) {
                $queries[] = $cap . ', Italia';
            }

            if ($comune && $provincia) {
                $queries[] = "{$comune}, {$provincia}, Italia";
            }

            if ($provincia) {
                $queries[] = "Provincia di {$provincia}, Italia";
            } elseif ($regione) {
                $queries[] = "{$regione}, Italia";
            }
        } elseif ($nomePaese !== null) {
            // Estero: CAP+citta, poi citta, poi CAP, sempre con il paese
            if ($cap && $comune) {
                $queries[] = "{$cap} {$comune}, {$nomePaese}";
            }
            if ($comune) {
                $queries[] = "{$comune}, {$nomePaese}";
            }
            if ($cap) {
                $queries[] = "{$cap}, {$nomePaese}";
            }
        } else {
            // Paese sconosciuto e formato non italiano: query neutre senza bias
            if ($cap && $comune) {
                $queries[] = "{$cap} {$comune}";
            }
            if ($cap) {
                $queries[] = $cap;
            }
            if ($comune) {
                $queries[] = $comune;
            }
        }

        // Deduplica preservando l'ordine (indirizzo completo sempre per primo)
        return array_values(array_unique(array_filter($queries)));
    }

    /**
     * Chiamata live a Nominatim con throttling 1 req/s.
     * Restituisce l'array risultato (o array con lat null su "nessun risultato")
     * oppure null in caso di errore HTTP/rete.
     * $countryCode (ISO alpha-2 minuscolo, es. 'de') vincola la ricerca al paese.
     */
    protected static function nominatim(string $query, ?string $countryCode = null): ?array
    {
        self::throttle();

        $params = [
            'q' => $query,
            'format' => 'json',
            'addressdetails' => 1,
            'limit' => 1,
        ];
        if ($countryCode !== null) {
            $params['countrycodes'] = $countryCode;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => config('app.name', 'MbTs') . ' geocoding interno spedizioni',
                ])
                ->get(self::ENDPOINT, $params);
        } catch (\Exception $e) {
            Log::warning("[GeocodingService] Errore richiesta Nominatim per '{$query}': " . $e->getMessage());
            return null;
        }

        if (!$response->successful()) {
            Log::warning("[GeocodingService] Nominatim HTTP {$response->status()} per '{$query}'");
            return null;
        }

        $data = $response->json();

        if (empty($data) || !isset($data[0]['lat'], $data[0]['lon'])) {
            // Nessun risultato: miss cachabile
            return ['lat' => null, 'lng' => null, 'paese' => null, 'country_code' => null, 'display_name' => null, 'tipo' => null];
        }

        $hit = $data[0];
        $address = $hit['address'] ?? [];

        return [
            'lat' => (float) $hit['lat'],
            'lng' => (float) $hit['lon'],
            'paese' => $address['country'] ?? null,
            'country_code' => isset($address['country_code']) ? mb_strtoupper($address['country_code']) : null,
            'display_name' => $hit['display_name'] ?? null,
            'tipo' => $hit['type'] ?? null,
        ];
    }

    /**
     * Garantisce l'intervallo minimo tra due chiamate live nello stesso processo.
     */
    protected static function throttle(): void
    {
        if (self::$lastCallAt !== null) {
            $elapsed = microtime(true) - self::$lastCallAt;
            $attesa = self::MIN_INTERVAL - $elapsed;

            if ($attesa > 0) {
                usleep((int) round($attesa * 1_000_000));
            }
        }

        self::$lastCallAt = microtime(true);
    }

    protected static function chiave(string $query, ?string $countryCode = null): string
    {
        // Il paese fa parte della chiave: stessa query in paesi diversi = risultati diversi
        return sha1(mb_strtolower(trim($query)) . '|' . ($countryCode ?? ''));
    }
}
