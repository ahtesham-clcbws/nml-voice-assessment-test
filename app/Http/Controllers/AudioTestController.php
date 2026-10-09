<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\GeminiModelService;

class AudioTestController extends Controller
{
    protected $modelService;

    public function __construct(GeminiModelService $modelService)
    {
        $this->modelService = $modelService;
    }

    public function index()
    {
        $tests = config('audio_tests');
        $models = $this->modelService->getAvailableModels();

        return view('audio-tests.index', compact('tests', 'models'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'test_id' => 'required|string',
            'model' => 'required|string',
            'audio' => 'required|file|mimes:audio/mpeg,mpga,mp3,wav,webm,ogg,mp4|max:20480',
        ]);

        $tests = collect(config('audio_tests'));
        $test = $tests->firstWhere('id', $request->input('test_id'));

        if (!$test) {
            return response()->json(['error' => 'Invalid test ID'], 400);
        }

        $modelName = $request->input('model');
        if (!$this->modelService->isValidModel($modelName)) {
            return response()->json(['error' => 'Invalid Gemini model selected. Please refresh and try again.'], 400);
        }

        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'Gemini API key is not configured on the server.'], 500);
        }

        $audioFile = $request->file('audio');
        $base64Audio = base64_encode(file_get_contents($audioFile->getRealPath()));
        $mimeType = $audioFile->getClientMimeType();
        if (str_starts_with($mimeType, 'video/webm')) $mimeType = 'audio/webm';
        if (str_starts_with($mimeType, 'video/mp4')) $mimeType = 'audio/mp4';

        $startTime = microtime(true);

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $test['gemini_context']],
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => $base64Audio
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";

        try {
            $response = Http::timeout(120)->post($url, $payload);
            $executionTime = microtime(true) - $startTime;

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Safely extract text from potentially complex response
                $textResponse = 'No text response found in the API response.';
                if (isset($responseData['candidates'][0]['content']['parts'])) {
                    foreach ($responseData['candidates'][0]['content']['parts'] as $part) {
                        if (isset($part['text'])) {
                            $textResponse = $part['text'];
                            break;
                        }
                    }
                }

                // Safely render markdown server-side to prevent XSS
                $htmlResponse = Str::markdown($textResponse, [
                    'html_input' => 'strip', 
                    'allow_unsafe_links' => false,
                ]);

                return response()->json([
                    'success' => true,
                    'html' => $htmlResponse,
                    'raw' => $responseData,
                    'time' => round($executionTime, 2) . 's',
                    'tokens' => $responseData['usageMetadata'] ?? null,
                    'model' => $modelName
                ]);
            } else {
                Log::error('Gemini API Error Response', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json([
                    'success' => false,
                    'error' => 'The AI evaluation service returned an error. Please try again.',
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Gemini API Exception: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'An unexpected server error occurred during evaluation.'], 500);
        }
    }
}
