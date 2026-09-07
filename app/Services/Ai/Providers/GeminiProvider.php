<?php

namespace App\Services\Ai\Providers;

use Illuminate\Support\Facades\Http;
use App\Models\AiConfig;
use App\Services\Ai\AiProviderInterface;

class GeminiProvider implements AiProviderInterface
{
    protected $config;

    public function __construct(AiConfig $config)
    {
        $this->config = $config;
    }

    public function generateResponse(string $prompt, array $options = [], ?string $systemMessage = null): ?string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->config->model_code}:generateContent";

        // Gemini doesn't support system role — prepend system message to user prompt
        $userContent = $systemMessage ? "{$systemMessage}\n\n{$prompt}" : $prompt;

        $response = Http::timeout($this->config->timeout)
            ->withHeaders([
                'x-goog-api-key' => $this->config->api_key,
                'Content-Type' => 'application/json',
            ])
            ->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $userContent]
                        ]
                    ]
                ]
            ]);

        if ($response->successful()) {
            return $response->json('candidates.0.content.parts.0.text');
        }

        return null;
    }

    public function getConfig(): AiConfig
    {
        return $this->config;
    }
}
