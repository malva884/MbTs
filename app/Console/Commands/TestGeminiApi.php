<?php

namespace App\Console\Commands;

use App\Services\GeminiAiService;
use App\Services\SettingService;
use Illuminate\Console\Command;

class TestGeminiApi extends Command
{
    protected $signature = 'app:test-gemini-api';
    protected $description = 'Testa il funzionamento dell\'API Gemini e il sistema di rotazione dei token';

    public function handle()
    {
        $this->info('[TestGeminiApi] Inizio test API Gemini');
        $this->newLine();

        // Verifica configurazione chiavi
        $settingService = new SettingService();
        $apiKeyValue = $settingService->get('gemini_api_key');

        if (is_array($apiKeyValue)) {
            $this->info("Chiavi API configurate: " . count($apiKeyValue));
            foreach ($apiKeyValue as $index => $key) {
                $masked = substr($key, 0, 8) . '...' . substr($key, -4);
                $this->line("  Chiave " . ($index + 1) . ": {$masked}");
            }
        } else {
            $masked = substr($apiKeyValue, 0, 8) . '...' . substr($apiKeyValue, -4);
            $this->info("Chiave API configurata: {$masked}");
        }

        $this->newLine();

        // Testa il servizio
        try {
            $geminiService = new GeminiAiService();
            $this->info('[TestGeminiApi] Servizio Gemini inizializzato correttamente');
        } catch (\Exception $e) {
            $this->error('[TestGeminiApi] Errore inizializzazione servizio: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('[TestGeminiApi] Test richiesta semplice...');
        $this->newLine();

        // Testa una richiesta semplice
        $testPrompt = "Rispondi con una sola parola: OK";
        try {
            $response = $geminiService->analizzaTesto($testPrompt);
            
            if ($response) {
                $this->info("Risposta Gemini: {$response}");
                $this->newLine();
                $this->info('[TestGeminiApi] ✅ Test completato con successo');
                return 0;
            } else {
                $this->error('[TestGeminiApi] ❌ Nessuna risposta ricevuta da Gemini');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('[TestGeminiApi] ❌ Errore durante il test: ' . $e->getMessage());
            return 1;
        }
    }
}
