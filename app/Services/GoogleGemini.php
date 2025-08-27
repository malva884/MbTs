<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class GoogleGemini
{
    protected $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public function generateText($prompt)
    {
        $response = Http::post($this->apiUrl . '?key=' . env('GEMINI_API_KEY'), [
            'contents' => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ],
        ]);
        return $response->json();
    }


}
