<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiPlaygroundController extends Controller
{
    public function index()
    {
        $apiKey = env('GEMINI_API_KEY');
        $models = [];
        
        if ($apiKey) {
            try {
                $response = Http::timeout(10)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
                if ($response->successful()) {
                    $allModels = $response->json('models') ?? [];
                    foreach ($allModels as $model) {
                        if (str_contains(strtolower($model['name']), 'flash')) {
                            $modelName = str_replace('models/', '', $model['name']);
                            $models[] = [
                                'name' => $modelName,
                                'displayName' => $model['displayName'] ?? $modelName
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore and use fallback
            }
        }

        if (empty($models)) {
            $models = [
                ['name' => 'gemini-3.8-flash', 'displayName' => 'Gemini 3.8 Flash']
            ];
        }

        // Sort so the latest stable flash models are near the top, basic alphabetical sort
        usort($models, function($a, $b) {
            return $b['name'] <=> $a['name'];
        });

        return view('gemini-playground.index', compact('models'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'context' => 'required|string',
            'model' => 'required|string',
            'audio' => 'required|file|mimes:audio/mpeg,mpga,mp3,wav,webm,ogg|max:20480',
        ]);


        $apiKey = env('GEMINI_API_KEY');
        
        if (!$apiKey) {
            return response()->json([
                'error' => 'Gemini API key is not configured in .env'
            ], 500);
        }

        $audioFile = $request->file('audio');
        $base64Audio = base64_encode(file_get_contents($audioFile->getRealPath()));
        $mimeType = $audioFile->getClientMimeType();

        // Fix webm mime type if browser sends it weirdly, Google API prefers audio/webm
        if (str_starts_with($mimeType, 'video/webm')) {
            $mimeType = 'audio/webm';
        }

        $startTime = microtime(true);

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

        $model = $request->input('model');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        try {
            $response = Http::timeout(120)->post($url, $payload);
            
            $executionTime = microtime(true) - $startTime;

            if ($response->successful()) {
                $responseData = $response->json();
                $textResponse = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? 'No text response found.';
                $tokenUsage = $responseData['usageMetadata'] ?? null;

                return response()->json([
                    'success' => true,
                    'text' => $textResponse,
                    'raw' => $responseData,
                    'time' => round($executionTime, 2) . 's',
                    'tokens' => $tokenUsage,
                    'model' => $model
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'API Error: ' . $response->body(),
                    'time' => round($executionTime, 2) . 's'
                ], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Gemini API Exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Server Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
