<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\GeminiModelService;

/**
 * GeminiPlaygroundController
 * 
 * Handles the logic for the Free-form Gemini Playground.
 * Unlike the Audio Testing Suite (which uses predefined tests), this controller
 * allows developers to pass raw context strings and select specific models to test
 * different prompting strategies manually.
 */
class GeminiPlaygroundController extends Controller
{
    /**
     * @var GeminiModelService
     * Fetches available models to populate the playground dropdown.
     */
    protected $modelService;

    public function __construct(GeminiModelService $modelService)
    {
        $this->modelService = $modelService;
    }

    /**
     * Display the Free-form Playground frontend.
     */
    public function index()
    {
        // We load models here so the user can explicitly select which one to test against.
        $models = $this->modelService->getAvailableModels();
        return view('gemini-playground.index', compact('models'));
    }

    /**
     * Process a raw user prompt (context) and audio file against the selected model.
     */
    public function process(Request $request)
    {
        // 1. Validate incoming request
        // Context is the manual prompt written by the tester.
        // Model is the specific model ID they want to evaluate with.
        $request->validate([
            'context' => 'required|string',
            'model' => 'required|string',
            'audio' => 'required|file|mimes:audio/mpeg,mpga,mp3,wav,webm,ogg,mp4|max:20480',
        ]);

        // 2. Validate the model securely on the backend
        // We check if the requested model actually exists in the valid model pool.
        $modelName = $request->input('model');
        if (!$this->modelService->isValidModel($modelName)) {
            return response()->json(['error' => 'Invalid Gemini model selected. Please refresh and try again.'], 400);
        }

        // 3. Ensure API keys exist
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'Gemini API key is not configured on the server.'], 500);
        }

        // 4. Extract and encode the audio file for the API payload
        $audioFile = $request->file('audio');
        $base64Audio = base64_encode(file_get_contents($audioFile->getRealPath()));
        
        // Normalize iOS/Safari video types to audio types so Gemini doesn't reject them
        $mimeType = $audioFile->getClientMimeType();
        if (str_starts_with($mimeType, 'video/webm')) $mimeType = 'audio/webm';
        if (str_starts_with($mimeType, 'video/mp4')) $mimeType = 'audio/mp4';

        $startTime = microtime(true);

        // 5. Build the API payload using the user's manual context string
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $request->input('context')],
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

        // 6. Make the API Call to Gemini
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";

        try {
            // Set 120s timeout to allow large audio files to be processed
            $response = Http::timeout(120)->post($url, $payload);
            $executionTime = microtime(true) - $startTime;

            if ($response->successful()) {
                $responseData = $response->json();
                
                // 7. Extract the generated text securely
                $textResponse = 'No text response found in the API response.';
                if (isset($responseData['candidates'][0]['content']['parts'])) {
                    foreach ($responseData['candidates'][0]['content']['parts'] as $part) {
                        if (isset($part['text'])) {
                            $textResponse = $part['text'];
                            break;
                        }
                    }
                }

                // 8. Safely parse Markdown to HTML
                // We strip embedded scripts and disallow unsafe links to prevent XSS
                $htmlResponse = Str::markdown($textResponse, [
                    'html_input' => 'strip', 
                    'allow_unsafe_links' => false,
                ]);

                // Return both the HTML for display and the raw response for debugging in the playground
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
