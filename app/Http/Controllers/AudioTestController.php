<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AudioTestController extends Controller
{
    public function index()
    {
        $tests = config('audio_tests');
        
        $apiKey = env('GEMINI_API_KEY');
        $models = [];
        
        if ($apiKey) {
            try {
                $response = Http::timeout(10)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
                if ($response->successful()) {
                    $allModels = $response->json('models') ?? [];
                    foreach ($allModels as $model) {
                        $name = strtolower($model['name']);
                        if (str_contains($name, 'flash')) {
                            $excludedKeywords = ['preview', 'image', 'tts', 'transcribe', 'computer-use', 'omni'];
                            $isUniversal = true;
                            foreach ($excludedKeywords as $keyword) {
                                if (str_contains($name, $keyword)) {
                                    $isUniversal = false;
                                    break;
                                }
                            }
                            if ($isUniversal) {
                                $modelName = str_replace('models/', '', $model['name']);
                                $tier = str_contains($modelName, 'lite') || str_contains($modelName, '8b') ? 1 : 2;
                                $models[] = [
                                    'name' => $modelName,
                                    'displayName' => $model['displayName'] ?? $modelName,
                                    'tier' => $tier
                                ];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {}
        }

        if (empty($models)) {
            $models = [
                ['name' => 'gemini-3.8-flash-lite', 'displayName' => 'Gemini 3.8 Flash Lite', 'tier' => 1],
                ['name' => 'gemini-3.8-flash', 'displayName' => 'Gemini 3.8 Flash', 'tier' => 2]
            ];
        }

        usort($models, function($a, $b) {
            if ($a['tier'] === $b['tier']) return $a['name'] <=> $b['name'];
            return $a['tier'] <=> $b['tier'];
        });

        return view('audio-tests.index', compact('tests', 'models'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'test_id' => 'required|string',
            'model' => 'required|string',
            'audio' => 'required|file|mimes:audio/mpeg,mpga,mp3,wav,webm,ogg|max:20480',
        ]);

        $tests = collect(config('audio_tests'));
        $test = $tests->firstWhere('id', $request->input('test_id'));

        if (!$test) {
            return response()->json(['error' => 'Invalid test ID'], 400);
        }

        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'Gemini API key is not configured'], 500);
        }

        $audioFile = $request->file('audio');
        $base64Audio = base64_encode(file_get_contents($audioFile->getRealPath()));
        $mimeType = $audioFile->getClientMimeType();
        if (str_starts_with($mimeType, 'video/webm')) $mimeType = 'audio/webm';

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

        $model = $request->input('model');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        try {
            $response = Http::timeout(120)->post($url, $payload);
            $executionTime = microtime(true) - $startTime;

            if ($response->successful()) {
                $responseData = $response->json();
                $textResponse = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? 'No text response found.';
                return response()->json([
                    'success' => true,
                    'text' => $textResponse,
                    'raw' => $responseData,
                    'time' => round($executionTime, 2) . 's',
                    'tokens' => $responseData['usageMetadata'] ?? null,
                    'model' => $model
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'API Error: ' . $response->body(),
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Gemini API Exception: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }
}
