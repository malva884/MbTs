<?php

namespace App\Services;

use Gemini\Client;
use Gemini\Enums\MimeType;
use Gemini\Data\Blob;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiAiService
{
    protected Client $client;

    public function __construct()
    {
        $apiKey = env('GEMINI_API_KEY');
        Log::info("Key: ".$apiKey);
        // Inizializziamo il client una volta sola nel costruttore
        $this->client = \Gemini::client($apiKey);


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
    public function analizzaFile(string $filePath, string $prompt, string $mimeType = 'application/pdf', string $model = 'gemini-3.1-flash-lite'): ?string
    {
        if (!file_exists($filePath)) {
            Log::error("GeminiAiService: File non trovato in {$filePath}");
            return null;
        }

        try {
            $fileContent = file_get_contents($filePath);
            $mimeTypeEnum = MimeType::from($mimeType);

            return $this->inviaRichiesta($prompt, $mimeTypeEnum, $fileContent, $model);

        } catch (Exception $e) {
            Log::error("GeminiAiService - Errore durante l'analisi del file: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Esegue una richiesta testuale pura (senza file), utile per traduzioni, riassunti, chat o analisi dati.
     */
    public function analizzaTesto(string $prompt, string $model = 'gemini-2.0-flash'): ?string
    {
        try {
            $response = $this->client->generativeModel(model: $model)->generateContent($prompt);
            return trim($response->text());
        } catch (Exception $e) {
            return $this->gestisciEsecuzioneConFallback($prompt, null, null, $model, $e);
        }
    }

    /**
     * Logica interna di invio con sistema di Fallback integrato in caso di server Google sovraccarichi (High Demand).
     */
    protected function inviaRichiesta(string $prompt, MimeType $mimeType, string $fileContent, string $model): ?string
    {
        try {
            $response = $this->client->generativeModel(model: $model)
                ->generateContent([
                    $prompt,
                    new Blob(mimeType: $mimeType, data: base64_encode($fileContent))
                ]);

            return trim($response->text());

        } catch (Exception $e) {
            return $this->gestisciEsecuzioneConFallback($prompt, $mimeType, $fileContent, $model, $e);
        }
    }

    /**
     * Se il modello scelto sperimenta un sovraccarico (High demand), scala automaticamente sul modello sussidiario.
     */
    protected function gestisciEsecuzioneConFallback(string $prompt, ?MimeType $mimeType, ?string $fileContent, string $failedModel, Exception $exception): ?string
    {
        $errorMessage = $exception->getMessage();

        // Se l'errore è dovuto a un picco di richieste sul server Google, attiviamo il piano di riserva
        if (str_contains($errorMessage, 'high demand') || str_contains($errorMessage, 'overloaded')) {
            $fallbackModel = ($failedModel === 'gemini-2.0-flash') ? 'gemini-1.5-flash' : 'gemini-2.0-flash';
            Log::warning("Modello {$failedModel} sovraccarico. Tento il fallback automatico su {$fallbackModel}...");

            try {
                $payload = [$prompt];
                if ($mimeType && $fileContent) {
                    $payload[] = new Blob(mimeType: $mimeType, data: base64_encode($fileContent));
                }

                $response = $this->client->generativeModel(model: $fallbackModel)->generateContent($payload);
                return trim($response->text());
            } catch (Exception $fallbackException) {
                Log::error("Anche il modello di fallback {$fallbackModel} ha fallito: " . $fallbackException->getMessage());
            }
        }

        Log::error("Errore critico Gemini API: " . $errorMessage);
        return null;
    }
}
