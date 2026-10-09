<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeminiModelService
 * 
 * Provides centralized logic for discovering, validating, and prioritizing 
 * Google Gemini AI models directly from the API.
 */
class GeminiModelService
{
    /**
     * Fetch a list of available Gemini models that support standard text/audio generation.
     * Results are cached for 1 hour to prevent excessive API calls and UI lag.
     */
    public function getAvailableModels(): array
    {
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return [];
        }

        return Cache::remember('gemini_models', 3600, function () use ($apiKey) {
            $models = [];
            $nextPageToken = null;
            
            $success = true;
            
            try {
                do {
                    $url = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}";
                    if ($nextPageToken) {
                        $url .= "&pageToken={$nextPageToken}";
                    }

                    $response = Http::timeout(15)->get($url);
                    if ($response->successful()) {
                        $responseData = $response->json();
                        $allModels = $responseData['models'] ?? [];
                        
                        foreach ($allModels as $model) {
                            $name = strtolower($model['name']);
                            $methods = $model['supportedGenerationMethods'] ?? [];
                            
                            // Capability Check 1: Must support generation
                            if (!in_array('generateContent', $methods)) {
                                continue;
                            }

                            // Capability Check 2: Exclude highly specialized non-LLMs
                            $excludedKeywords = ['embed', 'aqa', 'transcribe', 'tts', 'computer-use', 'lyria', 'veo'];
                            $isUniversal = true;
                            foreach ($excludedKeywords as $keyword) {
                                if (str_contains($name, $keyword)) {
                                    $isUniversal = false;
                                    break;
                                }
                            }

                            if ($isUniversal) {
                                $modelName = str_replace('models/', '', $model['name']);
                                
                                // Prioritize inexpensive/fast models.
                                $tier = 3;
                                if (str_contains($modelName, 'lite') || str_contains($modelName, '8b')) {
                                    $tier = 1;
                                } elseif (str_contains($modelName, 'flash')) {
                                    $tier = 2;
                                }

                                $models[] = [
                                    'name' => $modelName,
                                    'displayName' => $model['displayName'] ?? $modelName,
                                    'tier' => $tier
                                ];
                            }
                        }
                        
                        $nextPageToken = $responseData['nextPageToken'] ?? null;
                    } else {
                        Log::error('Failed to fetch Gemini models: ' . $response->body());
                        $success = false;
                        break;
                    }
                } while ($nextPageToken);
            } catch (\Exception $e) {
                Log::error('Gemini model discovery exception: ' . $e->getMessage());
                $success = false;
            }

            if (!$success && empty($models)) {
                throw new \Exception("Model discovery failed. Please verify your API key and network connection.");
            }

            usort($models, function($a, $b) {
                if ($a['tier'] === $b['tier']) return $a['name'] <=> $b['name'];
                return $a['tier'] <=> $b['tier'];
            });

            return $models;
        });
    }

    /**
     * Verify if a user-submitted model string is actually valid and currently available.
     * Prevents users from manually overriding the payload to invoke unsupported models.
     */
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

    /**
     * Automatically retrieves the most lightweight/universal model available.
     * Since getAvailableModels() sorts by Tier 1 -> Tier 2, the index [0] is always the best choice.
     * If the absolute lowest model goes down, it safely falls back to the next one.
     */
    public function getBestModel(): ?string
    {
        $models = $this->getAvailableModels();
        return !empty($models) ? $models[0]['name'] : null;
    }
}
