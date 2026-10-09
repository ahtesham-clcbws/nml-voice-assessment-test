<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\GeminiModelService;

/**
 * AudioTestController
 * 
 * Handles the logic for the predefined Audio Testing Suite.
 * This controller receives audio uploads, matches them to predefined test configurations,
 * and securely communicates with the Gemini API to evaluate the audio.
 */
class AudioTestController extends Controller
{
    /**
     * @var GeminiModelService
     * Used to securely fetch and validate available models from the Gemini API.
     */
    protected $modelService;

    public function __construct(GeminiModelService $modelService)
    {
        $this->modelService = $modelService;
    }

    /**
     * Display the Audio Testing Suite frontend.
     * Passes the predefined tests from the config file and available models to the view.
     */
    public function index()
    {
        // Load the predefined tests (reading passages, instructions, etc.) from config/audio_tests.php
        $tests = config('audio_tests');
        
        // Fetch valid models dynamically to ensure the UI only shows models that are currently available.
        $models = $this->modelService->getAvailableModels();

        return view('audio-tests.index', compact('tests', 'models'));
    }

    /**
     * Process an uploaded audio file against a predefined test using the Gemini API.
     */
    public function process(Request $request)
    {
        // 1. Validate the incoming request. 
        // We enforce strict mime types (mp3, wav, webm, mp4, etc.) and a max file size of 20MB.
        $request->validate([
            'test_id' => 'required|string',
            'audio' => 'required|file|mimes:audio/mpeg,mpga,mp3,wav,webm,ogg,mp4|max:20480',
        ]);

        // 2. Locate the specific predefined test configuration based on the provided test_id.
        $tests = collect(config('audio_tests'));
        $test = $tests->firstWhere('id', $request->input('test_id'));

        // Prevent execution if a tampered or invalid test ID is passed.
        if (!$test) {
            return response()->json(['error' => 'Invalid test ID'], 400);
        }

        // 3. Automatically select the best, most lightweight model available for audio processing.
        // We do this server-side to prevent users from bypassing or injecting expensive model names.
        $modelName = $this->modelService->getBestModel();
        if (!$modelName) {
            return response()->json(['error' => 'No valid Gemini models available on the server.'], 500);
        }

        // 4. Ensure the API key is configured.
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'Gemini API key is not configured on the server.'], 500);
        }

        // 5. Process the audio file for the Gemini API payload.
        // The Gemini API requires the audio to be sent as a base64 encoded string within inlineData.
        $audioFile = $request->file('audio');
        $base64Audio = base64_encode(file_get_contents($audioFile->getRealPath()));
        
        // Normalize mime types. Some browsers (especially Safari) record as video/mp4 or video/webm.
        // We map these to their audio equivalents so the Gemini API parses them correctly as audio streams.
        $mimeType = $audioFile->getClientMimeType();
        if (str_starts_with($mimeType, 'video/webm')) $mimeType = 'audio/webm';
        if (str_starts_with($mimeType, 'video/mp4')) $mimeType = 'audio/mp4';

        $startTime = microtime(true); // Track execution time for performance metrics

        // 6. Construct the payload matching the Gemini Multimodal API structure.
        // We pass the highly-specific 'gemini_context' (the grading rubric & expected text) as text,
        // and attach the user's recorded audio as inline data.
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

        // 7. Execute the API Call
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";

        try {
            // Increase timeout to 120s because analyzing long audio files can take a while.
            $response = Http::timeout(120)->post($url, $payload);
            $executionTime = microtime(true) - $startTime;

            if ($response->successful()) {
                $responseData = $response->json();
                
                // 8. Safely extract text from the potentially complex Gemini API response.
                // The API can return multiple candidates or parts. We locate the first valid text node.
                $textResponse = 'No text response found in the API response.';
                if (isset($responseData['candidates'][0]['content']['parts'])) {
                    foreach ($responseData['candidates'][0]['content']['parts'] as $part) {
                        if (isset($part['text'])) {
                            $textResponse = $part['text'];
                            break;
                        }
                    }
                }

                // 9. Convert Markdown to HTML securely.
                // We do this server-side and strip dangerous HTML inputs (like <script>) to prevent XSS vulnerabilities,
                // ensuring the output is perfectly safe before it hits the browser.
                $htmlResponse = Str::markdown($textResponse, [
                    'html_input' => 'strip', 
                    'allow_unsafe_links' => false,
                ]);

                return response()->json([
                    'success' => true,
                    'html' => $htmlResponse, // The safe, beautifully formatted assessment report
                    'raw' => $responseData,  // Kept for debugging / token count monitoring
                    'time' => round($executionTime, 2) . 's',
                    'tokens' => $responseData['usageMetadata'] ?? null,
                    'model' => $modelName
                ]);
            } else {
                // Keep the actual error payload hidden from users for security, but log it for developers.
                Log::error('Gemini API Error Response', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json([
                    'success' => false,
                    'error' => 'The AI evaluation service returned an error. Please try again.',
                ], $response->status());
            }
        } catch (\Exception $e) {
            // Handle fatal connectivity or timeout errors.
            Log::error('Gemini API Exception: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'An unexpected server error occurred during evaluation.'], 500);
        }
    }
}
