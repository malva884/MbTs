<?php

namespace App\Services;

use App\Services\SettingService;
use Gemini\Client;
use Gemini\Enums\MimeType;
use Gemini\Data\Blob;
use Gemini\Responses\GenerativeModel\GenerateContentResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeminiAiService
{
    protected array $apiKeys;
    protected int $currentKeyIndex = 0;
    protected array $failedKeys = [];
    protected array $cooldownUntil = [];

    public function __construct()
    {
        $settingService = new SettingService();
        $apiKeyValue = $settingService->get('gemini_api_key');

        // Supporta sia singola chiave che array di chiavi
        if (is_array($apiKeyValue)) {
            $this->apiKeys = array_filter($apiKeyValue); // Rimuovi valori vuoti
        } else {
            $this->apiKeys = [$apiKeyValue];
        }

        if (empty($this->apiKeys)) {
            throw new \Exception('Nessuna chiave API Gemini configurata');
        }
    }

    /**
     * Ottiene un client Gemini con la chiave API corrente
     */
    protected function getClient(): Client
    {
        return $this->makeClient($this->getNextAvailableKey());
    }

    /**
     * Crea un client con timeout HTTP esplicito: senza timeout una chiamata
     * lenta supera il max_execution_time del web SAPI e produce un FatalError
     * non intercettabile, invece di un'eccezione gestibile dal fallback.
     */
    protected function makeClient(string $apiKey): Client
    {
        return \Gemini::factory()
            ->withApiKey($apiKey)
            ->withHttpClient(new \GuzzleHttp\Client([
                'timeout' => config('gemini.request_timeout', 25),
                'connect_timeout' => config('gemini.connect_timeout', 10),
            ]))
            ->make();
    }

    /**
     * Ottiene la prossima chiave API disponibile (con rotazione e gestione saturazione)
     */
    protected function getNextAvailableKey(): string
    {
        $now = time();

        // Pulisci le chiavi che sono finite dal cooldown
        foreach ($this->cooldownUntil as $key => $until) {
            if ($now >= $until) {
                unset($this->cooldownUntil[$key]);
                $index = array_search($key, $this->failedKeys);
                if ($index !== false) {
                    unset($this->failedKeys[$index]);
                }
            }
        }

        // Se tutte le chiavi sono in cooldown, usa la prima (forzatura)
        if (count($this->failedKeys) >= count($this->apiKeys)) {
            $this->failedKeys = [];
            $this->cooldownUntil = [];
            $this->currentKeyIndex = 0;
        }

        // Trova la prossima chiave non fallita
        $attempts = 0;
        $maxAttempts = count($this->apiKeys);

        while ($attempts < $maxAttempts) {
            $key = $this->apiKeys[$this->currentKeyIndex];

            // Se questa chiave non è in cooldown, usala
            if (!isset($this->cooldownUntil[$key])) {
                return $key;
            }

            // Passa alla prossima chiave
            $this->currentKeyIndex = ($this->currentKeyIndex + 1) % count($this->apiKeys);
            $attempts++;
        }

        // Fallback: ritorna la prima chiave se tutte sono in cooldown
        return $this->apiKeys[0];
    }

    /**
     * Segna una chiave come fallita e la mette in cooldown per 5 minuti
     */
    protected function markKeyAsFailed(string $apiKey): void
    {
        if (!in_array($apiKey, $this->failedKeys)) {
            $this->failedKeys[] = $apiKey;
        }
        // Cooldown di 5 minuti (300 secondi)
        $this->cooldownUntil[$apiKey] = time() + 300;

        // Passa alla prossima chiave
        $this->currentKeyIndex = ($this->currentKeyIndex + 1) % count($this->apiKeys);
    }

    /**
     * Analizza qualsiasi file (PDF, Immagini, Audio, ecc.) inviandolo a Gemini con un prompt personalizzato.
     *
     * @param string $filePath Percorso assoluto del file sul server
     * @param string $prompt Le istruzioni specifiche per l'IA
     * @param string $mimeType Il tipo mime del file (es. 'application/pdf', 'image/jpeg')
     * @param string $model Il modello principale da usare
     * @return string|null Il testo restituito dall'IA o null in caso di fallimento
     */
    public function analizzaFile(string $filePath, string $prompt, string $mimeType = 'application/pdf', string $model = 'gemini-3.5-flash-lite'): ?string
    {
        @set_time_limit(120);

        if (!file_exists($filePath)) {
            Log::error("GeminiAiService: File non trovato in {$filePath}");
            return null;
        }

        // Il file occupa in memoria ~3.7x la sua dimensione (raw + base64 +
        // json_encode nel client): alzo il memory_limit e rifiuto i file oltre
        // soglia, altrimenti il json_encode in Request.php esaurisce la memoria
        // con un fatal non intercettabile dai try/catch.
        @ini_set('memory_limit', (string) config('gemini.memory_limit', '512M'));

        $maxBytes = (int) config('gemini.max_inline_bytes', 15 * 1024 * 1024);
        $fileSize = (int) filesize($filePath);
        if ($fileSize > $maxBytes) {
            Log::error("GeminiAiService: file troppo grande per l'invio inline a Gemini ({$fileSize} byte > {$maxBytes} byte): {$filePath}");
            return null;
        }

        try {
            $fileContent = file_get_contents($filePath);
            $mimeTypeEnum = MimeType::from($mimeType);

            return $this->inviaRichiesta($prompt, $mimeTypeEnum, $fileContent, $model);

        } catch (Throwable $e) {
            Log::error("GeminiAiService - Errore durante l'analisi del file: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Esegue una richiesta testuale pura (senza file), utile per traduzioni, riassunti, chat o analisi dati.
     */
    public function analizzaTesto(string $prompt, string $model = 'gemini-3.5-flash-lite'): ?string
    {
        @set_time_limit(120);

        try {
            $client = $this->getClient();
            $response = $client->generativeModel(model: $model)->generateContent($prompt);
            return $this->estraiTesto($response);
        } catch (Throwable $e) {
            return $this->gestisciEsecuzioneConFallback($prompt, null, null, $model, $e);
        }
    }

    /**
     * Logica interna di invio con sistema di Fallback integrato in caso di server Google sovraccarichi (High Demand).
     */
    protected function inviaRichiesta(string $prompt, MimeType $mimeType, string $fileContent, string $model): ?string
    {
        try {
            $client = $this->getClient();
            $response = $client->generativeModel(model: $model)
                ->generateContent([
                    $prompt,
                    new Blob(mimeType: $mimeType, data: base64_encode($fileContent))
                ]);

            return $this->estraiTesto($response);

        } catch (Throwable $e) {
            return $this->gestisciEsecuzioneConFallback($prompt, $mimeType, $fileContent, $model, $e);
        }
    }

    /**
     * Se il modello scelto sperimenta un sovraccarico (High demand) o quota exceeded, scala automaticamente su altri modelli e chiavi API.
     */
    protected function gestisciEsecuzioneConFallback(string $prompt, ?MimeType $mimeType, ?string $fileContent, string $failedModel, Throwable $exception): ?string
    {
        $errorMessage = $exception->getMessage();

        // Timeout/errore di rete transitorio: la chiave non e' in colpa, quindi
        // niente cooldown: ritenta una sola volta con la prossima chiave disponibile.
        if (str_contains($errorMessage, 'timed out') || str_contains($errorMessage, 'cURL error')) {
            try {
                $client = $this->getClient();
                $response = $client->generativeModel(model: $failedModel)
                    ->generateContent($this->buildPayload($prompt, $mimeType, $fileContent));
                Log::info("Gemini: retry dopo errore di rete riuscito (modello {$failedModel})");
                return $this->estraiTesto($response);
            } catch (Throwable $e) {
                Log::error("Errore critico Gemini API (retry dopo errore di rete fallito): " . $e->getMessage());
                return null;
            }
        }

        // Se l'errore è dovuto a quota exceeded o high demand, attiva il fallback
        if (str_contains($errorMessage, 'high demand') ||
            str_contains($errorMessage, 'overloaded') ||
            str_contains($errorMessage, 'quota') ||
            str_contains($errorMessage, 'Quota') ||
            str_contains($errorMessage, 'API key')) {

            // Segna la chiave corrente come fallita
            $currentKey = $this->getNextAvailableKey();
            $this->markKeyAsFailed($currentKey);
            Log::warning("Chiave API fallita (quota/demand). Tento con prossima chiave...");

            // Tenta con la prossima chiave API
            try {
                $client = $this->getClient();
                $response = $client->generativeModel(model: $failedModel)->generateContent($this->buildPayload($prompt, $mimeType, $fileContent));
                Log::info("Riuscito con nuova chiave API");
                return $this->estraiTesto($response);
            } catch (Throwable $e) {
                Log::warning("Anche la nuova chiave API ha fallito: " . $e->getMessage());
            }

            // Se anche la nuova chiave fallisce, prova fallback modelli con chiave diversa
            Log::warning("Tento fallback modelli con rotazione chiavi...");

            $fallbackModels = [
                'gemini-3.1-flash-lite',
                'gemini-3.5-flash-lite',
                'gemini-3.5-flash',
                'gemini-3.6-flash',
                'gemini-3-flash-preview',
                'gemini-2.5-flash-lite',
                'gemini-3.1-flash-lite',
            ];

            $fallbackModels = array_filter($fallbackModels, fn($model) => $model !== $failedModel);

            foreach ($fallbackModels as $fallbackModel) {
                foreach ($this->apiKeys as $apiKey) {
                    if (isset($this->cooldownUntil[$apiKey])) continue;

                    try {
                        Log::info("Tento modello {$fallbackModel} con nuova chiave API");
                        $client = $this->makeClient($apiKey);
                        $response = $client->generativeModel(model: $fallbackModel)->generateContent($this->buildPayload($prompt, $mimeType, $fileContent));
                        Log::info("Fallback riuscito con modello {$fallbackModel} e nuova chiave");
                        return $this->estraiTesto($response);
                    } catch (Throwable $fallbackException) {
                        Log::warning("Modello {$fallbackModel} con chiave ha fallito: " . $fallbackException->getMessage());
                        $this->markKeyAsFailed($apiKey);
                        continue;
                    }
                }
            }

            Log::error("Tutte le combinazioni modello/chiave hanno fallito");
        }

        Log::error("Errore critico Gemini API: " . $errorMessage);
        return null;
    }

    /**
     * Estrae il testo dalla risposta iterando candidati e parti.
     * Il quick accessor $response->text() del SDK lancia ValueError quando la
     * risposta contiene piu' di una Part o Part non testuali (es. thought-part
     * dei modelli gemini-3.x), quindi qui concateniamo tutte le parti di testo.
     */
    protected function estraiTesto(GenerateContentResponse $response): ?string
    {
        $text = '';
        foreach ($response->candidates as $candidate) {
            foreach ($candidate->content->parts as $part) {
                if ($part->text !== null) {
                    $text .= $part->text;
                }
            }
        }

        $text = trim($text);
        return $text === '' ? null : $text;
    }

    /**
     * Costruisce il payload per la richiesta API
     */
    protected function buildPayload(string $prompt, ?MimeType $mimeType, ?string $fileContent): array
    {
        $payload = [$prompt];
        if ($mimeType && $fileContent) {
            $payload[] = new Blob(mimeType: $mimeType, data: base64_encode($fileContent));
        }
        return $payload;
    }
}
