<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleGemini;
use Illuminate\Support\Facades\Log;

class GeminiController extends Controller
{
    protected $geminiService;

    public function __construct(GoogleGemini $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function generate(Request $request)
    {
        Log::channel('stderr')->info('Entro');

        $response = $this->geminiService->generateText('dati produzioni fibra ottica 2025');
        Log::channel('stderr')->info((array)$response);
        return response()->json($response);
    }
}
