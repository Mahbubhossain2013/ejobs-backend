<?php

namespace App\Services\Ai;

use App\Models\AiConfig;
use App\Models\AiUsageLog;
use App\Services\Ai\Providers\GeminiProvider;
use App\Services\Ai\Providers\OpenAiProvider;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class AiManagerService
{
    /**
     * Get a specific AI provider instance
     */
    public static function getProviderInstance(AiConfig $config): AiProviderInterface
    {
        return match ($config->provider_key) {
            'gemini' => new GeminiProvider($config),
            'openai', 'openrouter', 'fobign' => new OpenAiProvider($config),
            default => throw new Exception("Provider not supported: {$config->provider_key}"),
        };
    }

    /**
     * Get the first active AI provider instance
     */
    public static function getProvider(string $purpose = 'default'): AiProviderInterface
    {
        $config = AiConfig::getByPurpose($purpose);

        if (!$config) {
            throw new Exception("No AI provider configured for purpose: {$purpose}");
        }

        return self::getProviderInstance($config);
    }

    /**
     * Helper to quickly get a completion with full ordered Multi-AI failover and caching
     */
    public static function ask(string $prompt, float $temperature = 0.7, int $maxTokens = 2000): ?string
    {
        $userId = Auth::guard('sanctum')->id();
        
        // 1. Prompt Response Caching Check
        $cacheKey = 'ai_prompt_' . md5($prompt . '_' . $temperature . '_' . $maxTokens);
        if (Cache::has($cacheKey)) {
            $cachedResponse = Cache::get($cacheKey);
            
            // Log a successful cached request telemetry record
            try {
                AiUsageLog::create([
                    'user_id' => $userId,
                    'provider' => 'cached',
                    'model_code' => 'cached-model',
                    'prompt_tokens' => (int)(strlen($prompt) / 4),
                    'completion_tokens' => (int)(strlen($cachedResponse) / 4),
                    'total_tokens' => (int)((strlen($prompt) + strlen($cachedResponse)) / 4),
                    'duration_ms' => 1, // Negligible cache retrieve speed
                    'status' => 'success',
                    'was_fallback' => false,
                    'is_cached' => true,
                ]);
            } catch (\Exception $e) {
                // Ignore DB logging errors to keep the application robust
            }

            return $cachedResponse;
        }

        // 2. Get the list of active AI configurations sorted by priority (failover chain)
        $failoverChain = AiConfig::getFailoverChain();

        if ($failoverChain->isEmpty()) {
            Log::error("Multi-AI Failover System: No active AI configurations found in the database.");
            throw new \RuntimeException("AI service is not configured. Please configure an AI provider in admin settings.");
        }

        $lastError = null;
        $wasFallback = false;

        // 3. Iterate through the providers starting with the primary
        foreach ($failoverChain as $config) {
            $startTime = microtime(true);
            try {
                Log::info("Multi-AI Failover System: Attempting request using '{$config->provider_name}' (Priority: {$config->priority})");
                
                $provider = self::getProviderInstance($config);
                
                // Build execution options
                $options = [
                    'temperature' => $temperature,
                    'maxTokens' => $maxTokens,
                ];

                $response = $provider->generateResponse($prompt, $options);

                if ($response !== null) {
                    $duration = (int)((microtime(true) - $startTime) * 1000);
                    
                    $promptTokens = (int)(strlen($prompt) / 4);
                    $completionTokens = (int)(strlen($response) / 4);
                    $totalTokens = $promptTokens + $completionTokens;

                    // Log telemetry success
                    try {
                        AiUsageLog::create([
                            'user_id' => $userId,
                            'provider' => $config->provider_key,
                            'model_code' => $config->model_code ?? 'default-model',
                            'prompt_tokens' => $promptTokens,
                            'completion_tokens' => $completionTokens,
                            'total_tokens' => $totalTokens,
                            'duration_ms' => $duration,
                            'status' => 'success',
                            'was_fallback' => $wasFallback,
                            'is_cached' => false,
                        ]);
                    } catch (\Exception $e) {
                        // Silent
                    }

                    // Save response in cache for 6 hours
                    Cache::put($cacheKey, $response, now()->addHours(6));

                    // Success! Return immediately
                    return $response;
                }

                throw new Exception("Provider returned an empty response.");

            } catch (Exception $e) {
                $duration = (int)((microtime(true) - $startTime) * 1000);
                $lastError = $e->getMessage();
                Log::warning("Multi-AI Failover System: Provider '{$config->provider_name}' (key: {$config->provider_key}) failed. Reason: {$lastError}");
                
                // Log telemetry failure
                try {
                    AiUsageLog::create([
                        'user_id' => $userId,
                        'provider' => $config->provider_key,
                        'model_code' => $config->model_code ?? 'default-model',
                        'prompt_tokens' => (int)(strlen($prompt) / 4),
                        'completion_tokens' => 0,
                        'total_tokens' => (int)(strlen($prompt) / 4),
                        'duration_ms' => $duration,
                        'status' => 'failure',
                        'error_message' => $lastError,
                        'was_fallback' => $wasFallback,
                        'is_cached' => false,
                    ]);
                } catch (\Exception $ex) {
                    // Silent
                }

                // Log failover telemetry in DB
                $config->increment('failure_count');
                $config->update(['last_failed_at' => now()]);
                
                // Set fallback flag for subsequent loops
                $wasFallback = true;
            }
        }

        Log::critical("Multi-AI Failover System: All active AI providers in the failover chain failed! Last error: {$lastError}");

        // 4. Throw error instead of returning fake data
        throw new \RuntimeException("All AI providers are currently unavailable. Please try again later. Last error: {$lastError}");
    }

    /**
     * Generate a professional Job Description using AI (failover safe)
     */
    public static function generateCvProfile(string $userInput): ?array
    {
        $prompt = "You are an expert CV writer. Analyze the following text provided by a user and extract their professional information.
        User Input: \"{$userInput}\"
        
        Return ONLY a raw JSON object (no markdown, no preamble) with these exact keys:
        {
            \"personal_info\": {\"full_name\": \"...\", \"title\": \"...\", \"email\": \"...\", \"phone\": \"...\", \"city\": \"...\", \"bio\": \"...\"},
            \"skills\": [\"...\", \"...\"],
            \"experience\": [{\"title\": \"...\", \"company\": \"...\", \"duration\": \"...\", \"description\": \"...\"}],
            \"education\": [{\"degree\": \"...\", \"institution\": \"...\", \"passing_year\": \"...\"}],
            \"projects\": [{\"title\": \"...\", \"description\": \"...\", \"link\": \"...\"}],
            \"social_links\": {\"github\": \"...\", \"linkedin\": \"...\"}
        }
        If any information is missing, use an empty string or empty array.
        Analyze the bio to create a compelling professional summary.
        Extract technologies from project descriptions to populate skills.";

        try {
            $aiResponse = self::ask($prompt);

            // Clean the response from potential markdown code blocks
            $cleanJson = trim(str_replace(['```json', '```'], '', $aiResponse));
            $result = json_decode($cleanJson, true);

            // Validate the result
            if (!$result || !isset($result['personal_info'])) {
                throw new Exception("AI returned invalid or incomplete JSON.");
            }

            return $result;
        } catch (Exception $e) {
            Log::error("AI CV Generation Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Clear all health metrics and reset failure counts for all AI providers
     */
    public static function resetFailures(): void
    {
        AiConfig::query()->update([
            'failure_count' => 0,
            'last_failed_at' => null,
        ]);
        Log::info("Multi-AI Failover System: Reset all provider health metrics and failure counts.");
    }
}
