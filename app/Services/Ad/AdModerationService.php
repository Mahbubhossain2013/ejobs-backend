<?php

namespace App\Services\Ad;

class AdModerationService
{
    /**
     * Scan submitted ad media content or scripts for security threats.
     * Returns array with safety status and validation details.
     */
    public static function checkSafety(string $content, string $type): array
    {
        if (in_array($type, ['html', 'script'])) {
            $normalized = strtolower($content);

            $blockedTerms = [
                '<script', 'eval(', 'onload=', 'onerror=', '<iframe', 
                'document.cookie', 'window.location', 'localstorage',
                'sessionstorage', 'xmlhttprequest', 'fetch(', 'axios'
            ];

            foreach ($blockedTerms as $term) {
                if (str_contains($normalized, $term)) {
                    return [
                        'status' => false,
                        'reason' => "The input script or HTML contains a blocked high-risk term: '{$term}'."
                    ];
                }
            }
        }

        return ['status' => true];
    }

    /**
     * Check if a targeted link is blacklisted or suspicious.
     */
    public static function isUrlBlacklisted(string $url): bool
    {
        $normalized = strtolower($url);

        $spamDomains = [
            'suspicious-ads.ru', 'spam-tracker.cn', 'malicious-domain.xyz', 
            'phishing-link.cc', 'get-free-clicks.ru'
        ];

        foreach ($spamDomains as $domain) {
            if (str_contains($normalized, $domain)) {
                return true;
            }
        }

        return false;
    }
}
