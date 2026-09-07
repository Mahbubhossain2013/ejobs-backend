<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\AiModerationLog;
use App\Models\UserSecurityLog;
use App\Services\Ai\AiManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AiModerationMiddleware
{
    /**
     * Intercept and scan incoming postings/messages for terms violations
     */
    public function handle(Request $request, Closure $next, string $channel = 'messages'): Response
    {
        $user = Auth::guard('sanctum')->user();
        
        // Exclude system super admins from scans to allow support/moderator functions
        if ($user && $user->hasAnyRole(['super_admin', 'admin'])) {
            return $next($request);
        }

        // 1. Gather all potential text inputs to scan
        $inputsToScan = [];
        $scanKeys = ['message', 'proposal_text', 'content', 'description', 'review', 'comment', 'body'];
        
        foreach ($scanKeys as $key) {
            if ($request->has($key) && is_string($request->input($key))) {
                $inputsToScan[$key] = $request->input($key);
            }
        }

        if (empty($inputsToScan)) {
            return $next($request);
        }

        // 2. Perform fast regex checks (first line of defense) to conserve AI resources
        foreach ($inputsToScan as $key => $text) {
            $violationFound = $this->fastRegexScan($text);
            
            if ($violationFound) {
                return $this->blockRequest($user, $text, $channel, $violationFound);
            }
        }

        // 3. AI Context Scan (to catch smart bypasses, phishing, spam, or abuse)
        foreach ($inputsToScan as $key => $text) {
            if (strlen($text) < 10) {
                continue;
            }

            $prompt = "You are an Elite AI Content Moderator for a professional freelancer and job portal. Analyze the following text:
            \"{$text}\"
            
            Check for violations:
            - Attempting to share phone numbers, WhatsApp, email, Skype, Discord, or asking to communicate outside the platform.
            - Phishing, malicious URLs, scam postings, spam, or fake opportunities.
            - Harassment, abuse, hate speech, or highly inappropriate language.
            
            Return ONLY a raw JSON object (no code blocks, no explanation) with these keys:
            {
                \"violation_detected\": true/false,
                \"reason\": \"Detailed explanation of violation if found, or empty string\",
                \"confidence_score\": 0.0 to 1.0
            }";

            try {
                $aiResponse = AiManagerService::ask($prompt, temperature: 0.1, maxTokens: 500);
                
                // Clean response
                $cleanJson = trim(str_replace(['```json', '```'], '', $aiResponse));
                $audit = json_decode($cleanJson, true);

                if ($audit && isset($audit['violation_detected']) && $audit['violation_detected'] === true) {
                    if ($audit['confidence_score'] >= 0.7) {
                        return $this->blockRequest($user, $text, $channel, $audit['reason'], $audit['confidence_score']);
                    }
                }
            } catch (\Exception $e) {
                // Keep performance uninterrupted if AI is down and regex cleared it
            }
        }

        return $next($request);
    }

    /**
     * Fast regex sweeps for standard contact sharing patterns
     */
    protected function fastRegexScan(string $text): ?string
    {
        // Email match
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text)) {
            return "Attempt to share email contact coordinates.";
        }

        // Standard phone matches (support BD/international formats)
        if (preg_match('/(?:\+88|88)?01[3-9]\d{8}/', $text) || preg_match('/\b\d{4}[-.\s]?\d{3}[-.\s]?\d{4}\b/', $text)) {
            return "Attempt to share mobile/phone number coordinates.";
        }

        // WhatsApp / Telegram keyword sharing triggers
        $abuseKeywords = ['whatsapp', 'telegram', 'skype', 'discord', 'pay me directly', 'outside payment', 'payment bypass'];
        $lowerText = strtolower($text);
        
        foreach ($abuseKeywords as $word) {
            if (str_contains($lowerText, $word)) {
                return "Platform bypass keyword detected: '{$word}'.";
            }
        }

        return null;
    }

    /**
     * Terminate request with 403 error, log in database, and increment risk rating
     */
    protected function blockRequest($user, string $text, string $channel, string $reason, float $confidence = 1.0): Response
    {
        $userId = $user ? $user->id : null;
        $ip = request()->ip();

        // 1. Log inside ai_moderation_logs
        try {
            AiModerationLog::create([
                'user_id' => $userId,
                'message_text' => Str::limit($text, 250),
                'detected_violations' => [$reason],
                'confidence_score' => $confidence,
                'provider' => 'ai_shield_middleware',
                'action_taken' => 'blocked',
            ]);
        } catch (\Exception $e) {
            // Keep app bulletproof
        }

        // 2. Increment risk score and log security breach alert
        if ($user) {
            try {
                $profile = $user->profile;
                $company = $user->company;

                if ($profile) {
                    $newRisk = min(100, ($profile->ai_risk_score ?? 0) + 15);
                    $profile->update([
                        'ai_risk_score' => $newRisk,
                        'manual_override_status' => $newRisk > 60 ? 'Suspicious' : ($newRisk > 30 ? 'Caution' : 'Trusted')
                    ]);
                }

                if ($company) {
                    $newRisk = min(100, ($company->ai_risk_score ?? 0) + 15);
                    $company->update([
                        'ai_risk_score' => $newRisk,
                        'manual_override_status' => $newRisk > 60 ? 'Suspicious' : ($newRisk > 30 ? 'Caution' : 'Trusted')
                    ]);
                }

                UserSecurityLog::create([
                    'user_id' => $userId,
                    'ip_address' => $ip,
                    'user_agent' => request()->userAgent(),
                    'activity_type' => 'terms_violation',
                    'risk_score' => 15,
                    'vpn_detected' => false,
                    'moderation_details' => "AI Shield blocked transmission on channel '{$channel}'. Reason: {$reason}"
                ]);
            } catch (\Exception $e) {
                // Silent
            }
        }

        // 3. Return clean JSON response
        return response()->json([
            'status' => false,
            'message' => 'Message blocked by platform integrity guard.',
            'violation_reason' => $reason,
            'code' => 'AI_MODERATION_BLOCKED'
        ], 403);
    }
}
// Clean Str helper
if (!class_exists('Str')) {
    class Str extends \Illuminate\Support\Str {}
}
