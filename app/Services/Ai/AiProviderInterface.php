<?php

namespace App\Services\Ai;

use App\Models\AiConfig;

interface AiProviderInterface
{
    public function generateResponse(string $prompt, array $options = [], ?string $systemMessage = null): ?string;
    public function getConfig(): AiConfig;
}
