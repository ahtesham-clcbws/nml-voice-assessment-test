<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiModelService
{
    public function getAvailableModels(): array
    {
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return [];
        }

        return Cache::remember('gemini_models', 3600, function () use ($apiKey) {
            $models = [];
            try {
                $response = Http::timeout(10)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
                if ($response->successful()) {
                    $allModels = $response->json('models') ?? [];
                    foreach ($allModels as $model) {
                        $name = strtolower($model['name']);
                        $methods = $model['supportedGenerationMethods'] ?? [];
                        
                        // Medium issue: Check actual capabilities
                        if (!in_array('generateContent', $methods)) {
                            continue;
                        }

                        // Filter out specific domains we don't need for audio evaluation
                        $excludedKeywords = ['preview', 'image', 'tts', 'transcribe', 'computer-use', 'omni'];
                        $isUniversal = true;
                        foreach ($excludedKeywords as $keyword) {
                            if (str_contains($name, $keyword)) {
                                $isUniversal = false;
                                break;
                            }
                        }

                        // Even though we filter, we want to prioritize Flash
                        if ($isUniversal && str_contains($name, 'flash')) {
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
            } catch (\Exception $e) {
                Log::error('Failed to fetch Gemini models: ' . $e->getMessage());
            }

            usort($models, function($a, $b) {
                if ($a['tier'] === $b['tier']) return $a['name'] <=> $b['name'];
                return $a['tier'] <=> $b['tier'];
            });

            return $models;
        });
    }

    public function isValidModel(string $modelName): bool
    {
        $models = $this->getAvailableModels();
        foreach ($models as $model) {
            if ($model['name'] === $modelName) {
                return true;
            }
        }
        return false;
    }
}
