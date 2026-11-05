<?php

declare(strict_types=1);

return [
    /**
     * Gemini API Key
     *
     * You will need an API key to access the Gemini API.
     * You can obtain it from Google AI Studio ( https://makersuite.google.com/ )
     */
    'api_key' => env('GEMINI_API_KEY'),

    /**
     * Gemini Base URL
     *
     * If you need a specific base URL for the Gemini API, you can provide it here.
     * Otherwise, leave empty to use the default value.
     */
    'base_url' => env('GEMINI_BASE_URL'),

    /**
     * Timeout (secondi) delle richieste HTTP verso l'API Gemini.
     * Deve restare sotto il max_execution_time del web SAPI (30s)
     * per ottenere un'eccezione catchable invece di un FatalError.
     */
    'request_timeout' => env('GEMINI_REQUEST_TIMEOUT', 25),

    /**
     * Timeout (secondi) della connessione TCP.
     */
    'connect_timeout' => env('GEMINI_CONNECT_TIMEOUT', 10),

    /**
     * Dimensione massima (byte) di un file inviato inline in base64 a Gemini.
     * Il body JSON tiene in memoria ~3.7x la dimensione del file
     * (raw + base64_encode + json_encode nel client): oltre questa soglia
     * il json_encode esaurisce il memory_limit con un fatal non catchable.
     * In ogni caso Gemini accetta max 20MB di payload inline per richiesta.
     */
    'max_inline_bytes' => env('GEMINI_MAX_INLINE_BYTES', 15 * 1024 * 1024),

    /**
     * memory_limit applicato durante le chiamate a Gemini che trasportano file.
     */
    'memory_limit' => env('GEMINI_MEMORY_LIMIT', '512M'),
];
