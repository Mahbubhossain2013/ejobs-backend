<?php

namespace App\Services\Ad;

use App\Models\Promotion;
use App\Models\User;
use App\Models\Setting;
use Carbon\Carbon;

class AdRankingService
{
    /**
     * Compute ranking priority score for a campaign promotion
     */
    public static function calculateRankScore(Promotion $promo, ?User $user = null): float
    {
        // 1. Fetch Dynamic Weights from Admin Settings
        $wRelevance = floatval(Setting::where('key', 'ranking_relevance_weight')->value('value') ?? 40) / 100.0;
        $wBid = floatval(Setting::where('key', 'ranking_bid_weight')->value('value') ?? 30) / 100.0;
        $wCtr = floatval(Setting::where('key', 'ranking_ctr_weight')->value('value') ?? 20) / 100.0;
        $wFreshness = floatval(Setting::where('key', 'ranking_freshness_weight')->value('value') ?? 10) / 100.0;

        // 2. Budget Bid Component (Bid weight)
        // Normalize bid size (relative to BDT 100 base)
        $bidScore = min(10.0, $promo->daily_budget / 100.0);
        // Apply Admin Bid Override multiplier
        $bidScore *= floatval($promo->bid_override ?? 1.0);

        // 3. CTR Performance Component (CTR weight)
        $ctrScore = 0.0;
        if ($promo->impressions > 0) {
            $ctr = ($promo->clicks / $promo->impressions) * 100.0;
            $ctrScore = min(10.0, $ctr); // Cap CTR contribution
        }
        // Apply Admin CTR Override multiplier
        $ctrScore *= floatval($promo->ctr_override ?? 1.0);

        // 4. Freshness Component (Freshness weight)
        // Decays as time passes since start
        $daysSinceStart = max(0, Carbon::parse($promo->start_date)->diffInDays(now()));
        $freshnessScore = 10.0 / ($daysSinceStart + 1.0);
        // Apply Admin Freshness Override multiplier
        $freshnessScore *= floatval($promo->freshness_override ?? 1.0);

        // 5. Relevance Overlap Component (Relevance weight)
        $relevanceScore = 5.0; // Default relevance if guest or no targets
        
        if ($user && $user->profile && !empty($promo->target_skills)) {
            $userSkills = array_map('strtolower', $user->profile->skills ?? []);
            $targetSkills = array_map('strtolower', $promo->target_skills ?? []);

            if (count($targetSkills) > 0) {
                $intersect = array_intersect($userSkills, $targetSkills);
                $matchRatio = count($intersect) / count($targetSkills);
                $relevanceScore = $matchRatio * 10.0;
            }
        }
        // Apply Admin Relevance Override multiplier
        $relevanceScore *= floatval($promo->relevance_override ?? 1.0);

        // Update the database score metrics periodically for listing overview
        if (abs($promo->relevance_score - $relevanceScore) > 0.1 || abs($promo->ctr_score - $ctrScore) > 0.1) {
            $promo->updateQuietly([
                'relevance_score' => $relevanceScore,
                'ctr_score' => $ctrScore,
            ]);
        }

        // 6. Base Weighted Score (Scale: 0 - 10)
        $baseScore = ($relevanceScore * $wRelevance) + ($bidScore * $wBid) + ($ctrScore * $wCtr) + ($freshnessScore * $wFreshness);

        // 7. Apply Admin Ranking Overrides
        if ($promo->is_pinned) {
            // Guarantee top spot by adding massive boost
            $baseScore += 100.0;
        }

        if (($promo->ranking_override ?? 'none') === 'boost') {
            $baseScore += 5.0;
        } elseif (($promo->ranking_override ?? 'none') === 'demote') {
            $baseScore -= 5.0;
        }

        return $baseScore;
    }

    /**
     * Sort and filter a collection of promotions based on active rank score
     */
    public static function rankPromotions(array|object $promotions, ?User $user = null): array
    {
        $ranked = [];
        foreach ($promotions as $promo) {
            // Suspended ads do not participate in ranking
            if ($promo->is_suspended || $promo->status !== 'active') {
                continue;
            }
            $score = self::calculateRankScore($promo, $user);
            $promo->rank_score = $score;
            $ranked[] = $promo;
        }

        usort($ranked, function ($a, $b) {
            return $b->rank_score <=> $a->rank_score;
        });

        return $ranked;
    }
}
