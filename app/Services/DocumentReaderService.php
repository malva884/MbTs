<?php

namespace App\Services;

use Spatie\PdfToText\Pdf;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;
use Exception;

class DocumentReaderService
{
    /**
     * Cerca il numero del documento all'interno di un PDF (nativo o scansionato).
     *
     * @param string $filePath Percorso assoluto del file sul server
     * @return string|null Il numero del documento trovato o null
     */
    public function estraiNumeroDocumento(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            Log::error("File non trovato: {$filePath}");
            return null;
        }

        // --- STEP 1: Tentativo con PDF Nativo (Spatie) ---
        try {
            // 1. Generiamo il percorso combinato con base_path
            $rawPath = base_path('bin/pdftotext.exe');

            // 2. FORZATURA PER WINDOWS: Trasformiamo tutte le barre / in contromarce \
            $binaryPath = str_replace('/', DIRECTORY_SEPARATOR, $rawPath);

            if (file_exists($binaryPath)) {
                Log::info("Eseguibile pdftotext.exe trovato! Avvio Spatie...");

                $text = (new Pdf($binaryPath))
                    ->setPdf($filePath)
                    ->text();

                if (!empty(trim($text)) && strlen($text) > 10) {
                    Log::info("PDF identificato come nativo. Analisi in corso con Spatie...");

                    if (preg_match('/(?:Fattura|Documento|N\.|N°)\s*(\d+[\/\s-]*\d*)/i', $text, $matches)) {
                        return trim($matches[1]);
                    }
                }
            } else {
                // Logghiamo il percorso pulito per essere sicuri al 100% di dove stia guardando
                Log::warning("Eseguibile pdftotext.exe non trovato nel percorso convertito: " . $binaryPath);
            }
        } catch (Exception $e) {
            Log::warning("Spatie non è riuscito a leggere il PDF: " . $e->getMessage());
        }

        // --- STEP 2: Il piano B (La magia di Gemini) ---
        Log::info("Il PDF sembra un'immagine o testo non leggibile. Chiamo Gemini AI...");

        try {
            // 1. Leggiamo il contenuto grezzo del file
            $fileContent = file_get_contents($filePath);

            // 2. Prepariamo il prompt
            $prompt = "Analizza questo documento PDF (scansione). Trova il numero di commessa.\n" .
                "IL NUMERO DI COMMESSA SEGUE RIGOROSAMENTE QUESTE REGOLE:\n" .
                "1. È composto ESCLUSIVAMENTE da caratteri numerici (solo cifre).\n" .
                "2. È lungo ESATTAMENTE 10 cifre.\n" .
                "3. INIZIA SEMPRE con le cifre '46' (esempio: 46xxxxxxxx).\n\n" .
                "Rispondi ESCLUSIVAMENTE con il numero di 10 cifre trovato, senza aggiungere spazi, testo, introduzioni o punteggiatura. " .
                "Se non trovi nessun numero che rispetti TUTTE le regole, rispondi rigorosamente con la parola: NON TROVATO.";

            // 3. Inizializziamo il client nativo usando l'API KEY dall'env
            $apiKey = env('GEMINI_API_KEY');
            $client = \Gemini::client($apiKey);

            // 4. Risoluzione dinamica dell'Enum MimeType per evitare errori di tipo o costanti mancanti
            $mimeTypeEnum = \Gemini\Enums\MimeType::from('application/pdf');

            // 5. Inviamo la richiesta
            $response = $client->generativeModel(model: 'gemini-3.1-flash-lite')
                ->generateContent([
                    $prompt,
                    new \Gemini\Data\Blob(
                        mimeType: $mimeTypeEnum, // <--- Passato l'oggetto Enum valido generato dinamicamente
                        data: base64_encode($fileContent)
                    )
                ]);

            $risultatoAi = trim($response->text());

            if (!empty($risultatoAi) && strtolower($risultatoAi) !== 'non trovato') {
                Log::info("Gemini ha trovato il numero: " . $risultatoAi);
                return $risultatoAi;
            }

        } catch (Exception $e) {
            Log::error("Errore durante la chiamata a Gemini: " . $e->getMessage());
        }

        return null; // Se nessuno dei due metodi ha trovato nulla
    }
}
