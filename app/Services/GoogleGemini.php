<?php

namespace App\Services;

use App\Services\SettingService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class GoogleGemini
{
    protected $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public function generateText($prompt)
    {
        $settingService = new SettingService();
        $apiKey = $settingService->get('gemini_api_key');
        $response = Http::post($this->apiUrl . '?key=' . $apiKey, [
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
