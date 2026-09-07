<?php

namespace App\Services\Ad;

use App\Models\Promotion;
use App\Models\User;

class AdAiModerationService
{
    /**
     * Audit and score a proposed campaign promotion for safety and policy alignments.
     * Returns a structured array with status, risk scores, and detailed AI reasoning.
     */
    public static function moderateCampaign(Promotion|array $campaign, User $user): array
    {
        $data = $campaign instanceof Promotion ? $campaign->toArray() : $campaign;
        
        $title = strtolower($data['title'] ?? '');
        $dailyBudget = floatval($data['daily_budget'] ?? 0);
        $jobId = $data['job_id'] ?? null;
        $targetLocation = strtolower($data['target_location'] ?? '');
        
        // Default clean state
        $scores = [
            'spam_score' => 0.00,
            'fraud_score' => 0.00,
            'link_safety_score' => 1.00, // 1.0 = safe, 0.0 = unsafe
            'content_policy_score' => 0.00,
            'duplicate_score' => 0.00,
            'anomaly_score' => 0.00,
            'whitelisted_employer' => false,
            'status' => 'safe',
            'reason' => 'AI Moderation review completed: campaign successfully passed all security and spam policies.'
        ];

        $reasons = [];

        // 1. Whitelisted Employer Check
        if ($user->whitelisted_employer || (!empty($data['whitelisted_employer']) && $data['whitelisted_employer'])) {
            $scores['whitelisted_employer'] = true;
            $scores['reason'] = 'Bypassed moderation: campaign belongs to a verified whitelisted employer.';
            return $scores;
        }

        // 2. Anomaly detection (High Daily Budget or budget/spend ratio)
        if ($dailyBudget > 50000.00) {
            $scores['anomaly_score'] = 85.00;
            $scores['fraud_score'] = 45.00;
            $reasons[] = 'The requested daily budget is unusually high and exceeds the automated approval limit.';
        } elseif ($dailyBudget > 10000.00) {
            $scores['anomaly_score'] = 30.00;
            $reasons[] = 'The daily budget is higher than typical campaigns and needs manual review.';
        }

        // 3. Duplicate Campaign Detection (Same employer, same job)
        if ($jobId) {
            $duplicate = Promotion::where('user_id', $user->id)
                ->where('job_id', $jobId)
                ->whereIn('status', ['active', 'pending_review'])
                ->when($campaign instanceof Promotion, function($q) use ($campaign) {
                    return $q->where('id', '!=', $campaign->id);
                })
                ->exists();

            if ($duplicate) {
                $scores['duplicate_score'] = 95.00;
                $reasons[] = 'This job already has an active or pending campaign. Manage the existing campaign from the Promotions page instead of creating a duplicate.';
            }
        }

        // 4. Spam Keyword Scoring
        $spamKeywords = [
            'free money' => 90.00,
            'make millions' => 95.00,
            'crypto richest' => 85.00,
            'fast cash' => 80.00,
            'earn money fast' => 85.00,
            'casino online' => 95.00,
            'gamble rich' => 90.00,
            'guaranteed double' => 95.00,
            'whatsapp chat' => 40.00,
            'work from home' => 15.00
        ];

        foreach ($spamKeywords as $word => $weight) {
            if (str_contains($title, $word)) {
                $scores['spam_score'] = max($scores['spam_score'], $weight);
                $scores['content_policy_score'] = max($scores['content_policy_score'], $weight - 10);
                $reasons[] = 'The campaign title contains blocked marketing language.';
            }
        }

        // 5. External Link / Target Domain Safety Check
        $spamDomains = [
            'suspicious-ads.ru', 'spam-tracker.cn', 'malicious-domain.xyz', 
            'phishing-link.cc', 'get-free-clicks.ru'
        ];

        foreach ($spamDomains as $domain) {
            if (str_contains($targetLocation, $domain)) {
                $scores['link_safety_score'] = 0.05; // extremely low safety
                $scores['fraud_score'] = max($scores['fraud_score'], 90.00);
                $reasons[] = 'The target location contains a blocked or suspicious domain.';
            }
        }

        // 6. Content Policy Violation (e.g. phone numbers or sensitive texts)
        if (preg_match('/[0-9]{10,}/', $title)) {
            $scores['content_policy_score'] = max($scores['content_policy_score'], 75.00);
            $reasons[] = 'The campaign title appears to contain a phone number or long numeric sequence.';
        }

        // Evaluate overall moderation status
        $maxRisk = max(
            $scores['spam_score'],
            $scores['fraud_score'],
            (1.0 - $scores['link_safety_score']) * 100.0,
            $scores['content_policy_score'],
            $scores['duplicate_score'],
            $scores['anomaly_score']
        );

        $reasonText = count(array_unique($reasons)) ? implode(' ', array_unique($reasons)) : '';

        if ($maxRisk >= 75.00) {
            $scores['status'] = 'high_risk';
            $scores['reason'] = $reasonText ?: 'Security block: High safety risk detected during automated screening processes.';
        } elseif ($maxRisk >= 35.00) {
            $scores['status'] = 'uncertain';
            $scores['reason'] = $reasonText ?: 'Queued for manual approval: Contains borderline policy or engagement anomalies.';
        }

        // Update the promotion model directly if passed as object
        if ($campaign instanceof Promotion) {
            $campaign->updateQuietly([
                'spam_score' => $scores['spam_score'],
                'fraud_score' => $scores['fraud_score'],
                'link_safety_score' => $scores['link_safety_score'],
                'content_policy_score' => $scores['content_policy_score'],
                'duplicate_score' => $scores['duplicate_score'],
                'anomaly_score' => $scores['anomaly_score'],
                'ai_explanation' => $scores['reason'],
                'moderation_status' => $scores['status'],
                'whitelisted_employer' => $scores['whitelisted_employer']
            ]);
        }

        return $scores;
    }
}
