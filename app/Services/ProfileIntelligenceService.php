<?php

namespace App\Services;

use App\Models\User;
use App\Models\Badge;
use App\Models\Job;
use App\Models\Escrow;
use App\Models\WalletTransaction;
use App\Models\AdminTask;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class ProfileIntelligenceService
{
    /**
     * Analyze and update a single user's profile intelligence metrics
     */
    public function analyzeUser(User $user): void
    {
        try {
            DB::transaction(function () use ($user) {
                $role = $user->getRoleNames()->first() ?? 'candidate';

                if ($role === 'candidate') {
                    $this->analyzeCandidate($user);
                } elseif ($role === 'employer') {
                    $this->analyzeEmployer($user);
                }

                // 3. Auto-Evaluate Badge Rules
                $this->evaluateAutomaticBadges($user, $role);
            });
        } catch (\Exception $e) {
            Log::error("ProfileIntelligenceService Error for User ID {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Analyze Candidate Profile
     */
    protected function analyzeCandidate(User $user): void
    {
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        // 1. Calculate Profile Completion Percentage & Strength Breakdown
        $strengthBreakdown = [
            'avatar' => !empty($profile->avatar) ? 10 : 0,
            'phone' => !empty($profile->phone) ? 10 : 0,
            'bio' => !empty($profile->bio) ? 15 : 0,
            'city' => !empty($profile->city) ? 10 : 0,
            'skills' => (is_array($profile->skills) ? count($profile->skills) : count(json_decode($profile->skills, true) ?? [])) > 0 ? 15 : 0,
            'education' => (is_array($profile->education) ? count($profile->education) : count(json_decode($profile->education, true) ?? [])) > 0 ? 15 : 0,
            'experience' => (is_array($profile->experience) ? count($profile->experience) : count(json_decode($profile->experience, true) ?? [])) > 0 ? 15 : 0,
            'resume' => !empty($profile->resume_path) ? 10 : 0,
        ];
        $completion = array_sum($strengthBreakdown);

        // 2. Update Performance Metrics
        $completedJobs = Job::where('assigned_to', $user->id)
            ->where('project_status', 'completed')
            ->count();

        $totalEarnings = 0;
        if ($user->wallet) {
            $totalEarnings = WalletTransaction::where('wallet_id', $user->wallet->id)
                ->where('reference_type', 'project_payout')
                ->where('status', 'completed')
                ->sum('amount');
        }

        // 3. Compute Trust Score & AI trust explanations
        $trustScore = 100;
        $trustExplanations = [];
        
        // Deduction modifiers
        $isVerified = $user->badges()->where('badge_key', 'verified')->exists();
        if (!$isVerified) {
            $trustScore -= 30;
            $trustExplanations[] = "-30 deduction: Profile identity not yet verified.";
        } else {
            $trustExplanations[] = "+0 bonus: Profile identity verified successfully.";
        }
        
        if (empty($profile->phone)) {
            $trustScore -= 20;
            $trustExplanations[] = "-20 deduction: Missing contact phone number.";
        } else {
            $trustExplanations[] = "+0 bonus: Contact phone number verified.";
        }
        
        if ($completion < 50) {
            $trustScore -= 15;
            $trustExplanations[] = "-15 deduction: Completion percentage under 50% ({$completion}%).";
        } else {
            $trustExplanations[] = "+0 bonus: Profile completion is high ({$completion}%).";
        }
        
        if ($profile->reputation_status === 'suspicious') {
            $trustScore -= 20;
            $trustExplanations[] = "-20 deduction: Account flagged as suspicious by security system.";
        }

        // Bonus modifiers
        if ($user->activeSubscription() && in_array($user->activeSubscription()->plan->slug, ['premium', 'pro'])) {
            $trustScore += 10;
            $trustExplanations[] = "+10 bonus: Active Premium subscription status.";
        }

        $trustScore = max(0, min(100, $trustScore));

        // Suspect Monitoring & Flagging
        $reputation = $profile->reputation_status;
        if ($trustScore < 50 && $reputation !== 'suspicious') {
            $reputation = 'suspicious';
            $this->createSuspiciousAlertTask($user, $trustScore);
        } elseif ($trustScore >= 75 && $reputation === 'suspicious') {
            $reputation = 'good'; // Recovered
        }

        // Save
        $profile->update([
            'profile_completion_percentage' => $completion,
            'completed_jobs_count' => $completedJobs,
            'total_earnings' => $totalEarnings,
            'trust_score' => $trustScore,
            'reputation_status' => $reputation,
            'trust_explanations' => $trustExplanations,
            'profile_strength_breakdown' => $strengthBreakdown,
        ]);
    }

    /**
     * Analyze Employer Profile
     */
    protected function analyzeEmployer(User $user): void
    {
        $company = $user->company()->firstOrCreate(['user_id' => $user->id], [
            'name' => $user->name . ' Company',
            'slug' => \Illuminate\Support\Str::slug($user->name) . '-' . time(),
        ]);

        // 1. Calculate Profile Completion Percentage & Strength Breakdown
        $strengthBreakdown = [
            'description' => !empty($company->description) ? 25 : 0,
            'website' => !empty($company->website) ? 15 : 0,
            'location' => !empty($company->location) ? 15 : 0,
            'industry' => !empty($company->industry) ? 15 : 0,
            'logo' => !empty($company->logo) ? 15 : 0,
            'trade_license' => !empty($company->trade_license_number) ? 15 : 0,
        ];
        $completion = array_sum($strengthBreakdown);

        // 2. Update Performance Metrics
        $completedJobs = Job::where('company_id', $company->id)
            ->where('project_status', 'completed')
            ->count();

        $totalSpend = Escrow::where('employer_id', $user->id)
            ->where('status', 'released')
            ->sum('amount');

        // 3. Compute Trust Score & AI trust explanations
        $trustScore = 100;
        $trustExplanations = [];

        // Deduction modifiers
        if (!$company->is_verified) {
            $trustScore -= 30;
            $trustExplanations[] = "-30 deduction: Company business registration not yet verified.";
        } else {
            $trustExplanations[] = "+0: Company business registration verified.";
        }
        
        if (empty($company->website)) {
            $trustScore -= 15;
            $trustExplanations[] = "-15 deduction: Company website not listed.";
        } else {
            $trustExplanations[] = "+0: Company website listing active.";
        }
        
        if (empty($company->logo)) {
            $trustScore -= 15;
            $trustExplanations[] = "-15 deduction: Company logo not uploaded.";
        } else {
            $trustExplanations[] = "+0: Company logo verified.";
        }
        
        if ($completion < 50) {
            $trustScore -= 15;
            $trustExplanations[] = "-15 deduction: Completion percentage under 50% ({$completion}%).";
        } else {
            $trustExplanations[] = "+0: Profile completion is high ({$completion}%).";
        }
        
        if ($company->reputation_status === 'suspicious') {
            $trustScore -= 20;
            $trustExplanations[] = "-20 deduction: Company flagged as suspicious by security system.";
        }

        // Bonus modifiers
        if ($user->activeSubscription() && in_array($user->activeSubscription()->plan->slug, ['employer-premium', 'employer-pro'])) {
            $trustScore += 10;
            $trustExplanations[] = "+10 bonus: Active Premium subscription status.";
        }

        $trustScore = max(0, min(100, $trustScore));

        // Suspect Monitoring & Flagging
        $reputation = $company->reputation_status;
        if ($trustScore < 50 && $reputation !== 'suspicious') {
            $reputation = 'suspicious';
            $this->createSuspiciousAlertTask($user, $trustScore);
        } elseif ($trustScore >= 75 && $reputation === 'suspicious') {
            $reputation = 'good'; // Recovered
        }

        // Save
        $company->update([
            'profile_completion_percentage' => $completion,
            'completed_jobs_count' => $completedJobs,
            'total_spend' => $totalSpend,
            'trust_score' => $trustScore,
            'reputation_status' => $reputation,
            'trust_explanations' => $trustExplanations,
            'profile_strength_breakdown' => $strengthBreakdown,
        ]);
    }

    /**
     * Create Suspicious Account Warning in Admin Tasks
     */
    protected function createSuspiciousAlertTask(User $user, int $trustScore): void
    {
        AdminTask::firstOrCreate([
            'user_id' => $user->id,
            'title' => "Suspicious Account Flagged: " . $user->name,
        ], [
            'description' => "User account has fallen below the safe ecosystem trust threshold. Current trust score is {$trustScore}/100. Please inspect activity, portfolio validity, and completed jobs for moderation.",
            'is_completed' => false
        ]);
    }

    /**
     * Evaluate and sync automatic badges for the user
     */
    protected function evaluateAutomaticBadges(User $user, string $role): void
    {
        $profile = $user->profile()->first();
        $company = $user->company()->first();

        // Get active automatic badges compatible with the user's role
        $automaticBadges = Badge::where('is_active', true)
            ->where('is_automatic', true)
            ->whereIn('role', [$role, 'both'])
            ->get();

        // Query actual statistics for evaluation
        $actualJobs = $role === 'candidate' 
            ? ($profile ? $profile->completed_jobs_count : 0)
            : ($company ? $company->completed_jobs_count : 0);

        $actualScore = $role === 'candidate'
            ? ($profile ? $profile->trust_score : 100)
            : ($company ? $company->trust_score : 100);

        $actualRating = $role === 'candidate'
            ? ($profile ? $profile->rating : 0.0)
            : ($company ? $company->rating : 0.0);

        $actualEarnings = $profile ? $profile->total_earnings : 0;
        $actualSpend = $company ? $company->total_spend : 0;

        $isVerified = $role === 'candidate'
            ? $user->badges()->where('badge_key', 'verified')->exists()
            : ($company ? (bool)$company->is_verified : false);

        foreach ($automaticBadges as $badge) {
            $rules = $badge->rules;
            if (empty($rules)) {
                continue;
            }

            $eligible = true;

            // Detect rule structuring format: new array format vs old key-value
            $isNewFormat = false;
            if (is_array($rules) && count($rules) > 0) {
                $first = reset($rules);
                if (is_array($first) && isset($first['field'])) {
                    $isNewFormat = true;
                }
            }

            if (!$isNewFormat) {
                // 1. Completed Jobs Rule
                if (isset($rules['completed_jobs'])) {
                    if ($actualJobs < $rules['completed_jobs']) {
                        $eligible = false;
                    }
                }

                // 2. Trust Score Rule
                if (isset($rules['trust_score'])) {
                    if ($actualScore < $rules['trust_score']) {
                        $eligible = false;
                    }
                }

                // 3. Rating Rule
                if (isset($rules['rating'])) {
                    if ($actualRating < $rules['rating']) {
                        $eligible = false;
                    }
                }

                // 4. Earnings / Spend Rule
                if (isset($rules['earnings'])) {
                    if ($actualEarnings < $rules['earnings']) {
                        $eligible = false;
                    }
                }
                if (isset($rules['spend'])) {
                    if ($actualSpend < $rules['spend']) {
                        $eligible = false;
                    }
                }

                // 5. Verification Status Rule
                if (isset($rules['verified']) && $rules['verified']) {
                    if (!$isVerified) {
                        $eligible = false;
                    }
                }
            } else {
                // New format: database-driven conditional rules engine supporting comparison operators
                foreach ($rules as $rule) {
                    if (!isset($rule['field']) || !isset($rule['value'])) {
                        continue;
                    }

                    $field = $rule['field'];
                    $operator = $rule['operator'] ?? '>=';
                    $targetValue = $rule['value'];

                    $actualValue = null;
                    if ($field === 'completed_jobs') {
                        $actualValue = $actualJobs;
                    } elseif ($field === 'trust_score') {
                        $actualValue = $actualScore;
                    } elseif ($field === 'rating') {
                        $actualValue = $actualRating;
                    } elseif ($field === 'earnings') {
                        $actualValue = $actualEarnings;
                    } elseif ($field === 'spend') {
                        $actualValue = $actualSpend;
                    } elseif ($field === 'verified') {
                        $actualValue = $isVerified;
                        $targetValue = filter_var($targetValue, FILTER_VALIDATE_BOOLEAN);
                    } else {
                        continue; // Unknown field
                    }

                    switch ($operator) {
                        case '>=':
                            if ($actualValue < $targetValue) $eligible = false;
                            break;
                        case '>':
                            if ($actualValue <= $targetValue) $eligible = false;
                            break;
                        case '<=':
                            if ($actualValue > $targetValue) $eligible = false;
                            break;
                        case '<':
                            if ($actualValue >= $targetValue) $eligible = false;
                            break;
                        case '=':
                        case '==':
                            if ($actualValue != $targetValue) $eligible = false;
                            break;
                        case '!=':
                        case '<>':
                            if ($actualValue == $targetValue) $eligible = false;
                            break;
                        default:
                            if ($actualValue < $targetValue) $eligible = false;
                            break;
                    }

                    if (!$eligible) {
                        break; // Stop evaluating further rules if one condition fails
                    }
                }
            }

            // Sync Badge Assignment
            $hasBadge = $user->badges()->where('badge_id', $badge->id)->exists();

            if ($eligible && !$hasBadge) {
                // Award Badge
                $user->badges()->attach($badge->id, [
                    'assigned_by' => 'system',
                    'is_visible' => true,
                    'earned_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Send notification
                NotificationFacade::send($user, new SystemNotification([
                    'title' => 'Badge Awarded!',
                    'message' => "Congratulations! You have earned the achievement badge: {$badge->name}.",
                    'type' => 'badge',
                    'action_url' => $role === 'candidate' ? '/dashboard/profile' : '/employer/profile',
                ]));
            } elseif (!$eligible && $hasBadge) {
                // Check if assigned by system before detaching to avoid revoking manual overrides
                $pivot = $user->badges()->where('badge_id', $badge->id)->first()->pivot;
                if ($pivot && $pivot->assigned_by === 'system') {
                    $user->badges()->detach($badge->id);
                }
            }
        }
    }
}
