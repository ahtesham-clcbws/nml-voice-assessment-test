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
            try {
                // Fetch all models listed under this API Key. Timeout quickly (10s) as this blocks UI loads.
                $response = Http::timeout(10)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
                if ($response->successful()) {
                    $allModels = $response->json('models') ?? [];
                    foreach ($allModels as $model) {
                        $name = strtolower($model['name']);
                        $methods = $model['supportedGenerationMethods'] ?? [];
                        
                        // We ONLY want models capable of general content generation (text/audio context).
                        if (!in_array('generateContent', $methods)) {
                            continue;
                        }

                        // Filter out highly specialized or experimental models we don't need for basic audio evaluation.
                        $excludedKeywords = ['preview', 'image', 'tts', 'transcribe', 'computer-use', 'omni'];
                        $isUniversal = true;
                        foreach ($excludedKeywords as $keyword) {
                            if (str_contains($name, $keyword)) {
                                $isUniversal = false;
                                break;
                            }
                        }

                        // We prioritize 'flash' models because they are extremely fast and cost-effective,
                        // which is ideal for real-time student audio evaluation.
                        if ($isUniversal && str_contains($name, 'flash')) {
                            // Strip 'models/' prefix since the API endpoints expect just the model name.
                            $modelName = str_replace('models/', '', $model['name']);
                            
                            // Assign tiers to sort them properly. 
                            // Tier 1 (Fastest/Cheapest): flash-lite or 8b variants.
                            // Tier 2 (Standard): standard flash models.
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
                // If fetching fails, log it, and an empty array will trigger fallback logic in controllers.
                Log::error('Failed to fetch Gemini models: ' . $e->getMessage());
            }

            // Sort models by tier (ascending) so Tier 1 is always presented/selected first.
            // If tiers match, sort alphabetically by name.
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
