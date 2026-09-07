<?php

namespace App\Services\Ai\Providers;

use Illuminate\Support\Facades\Http;
use App\Models\AiConfig;
use App\Services\Ai\AiProviderInterface;

class OpenAiProvider implements AiProviderInterface
{
    protected $config;

    public function __construct(AiConfig $config)
    {
        $this->config = $config;
    }

    public function generateResponse(string $prompt, array $options = [], ?string $systemMessage = null): ?string
    {
        $baseUrl = $this->config->api_url ?? 'https://api.openai.com/v1';
        $url = rtrim($baseUrl, '/') . '/chat/completions';

        $messages = [];
        // Use passed system message, fall back to config instruction
        $sysMsg = $systemMessage ?? $this->config->system_instruction;
        if ($sysMsg) {
            $messages[] = ['role' => 'system', 'content' => $sysMsg];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? null;

        $payload = [
            'model' => $this->config->model_code,
            'messages' => $messages,
            'temperature' => $temperature,
        ];
        if ($maxTokens) {
            $payload['max_tokens'] = $maxTokens;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config->api_key,
            'Content-Type' => 'application/json',
        ])
        ->timeout($this->config->timeout)
        ->post($url, $payload);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }

        return null;
    }

    public function getConfig(): AiConfig
    {
        return $this->config;
    }
}
