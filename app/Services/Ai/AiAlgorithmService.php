<?php

namespace App\Services\Ai;

use App\Models\AiAlgorithmSetting;
use App\Models\UserBehaviorLog;
use App\Models\AiConfig;
use App\Models\Job;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Company;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiAlgorithmService
{
    /**
     * Log user activity behavior if the respective setting toggle is enabled.
     */
    public static function trackActivity(?int $userId, string $activityType, ?int $targetId = null, ?array $metaData = null): bool
    {
        if (!$userId) {
            return false;
        }

        $settings = AiAlgorithmSetting::getActive();

        // Map activity types to config switches
        $map = [
            'job_view' => 'track_job_views',
            'job_save' => 'track_job_saves',
            'application' => 'track_applications',
            'profile_visit' => 'track_profile_visits',
            'company_visit' => 'track_company_visits',
            'search_history' => 'track_search_history',
            'scroll_depth' => 'track_scroll_depth',
            'click_pattern' => 'track_click_patterns',
            'session_engagement' => 'track_session_engagement',
        ];

        $settingKey = $map[$activityType] ?? null;

        if ($settingKey && !$settings->{$settingKey}) {
            // Tracking is disabled for this activity type
            return false;
        }

        try {
            UserBehaviorLog::create([
                'user_id' => $userId,
                'activity_type' => $activityType,
                'target_id' => $targetId,
                'meta_data' => $metaData,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error("Telemetry log failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Compute recommendations score breakdown based on dynamic database weights.
     */
    public static function computeMatchScore(User $candidate, Job $job): array
    {
        $settings = AiAlgorithmSetting::getActive();
        $profile = $candidate->profile;

        if (!$profile) {
            return [
                'total_score' => 0,
                'breakdown' => []
            ];
        }

        // 1. Skill Matching Score (0 - 100)
        $skillsScore = 0;
        
        $rawSkills = (array)($profile->skills ?? []);
        $skillsList = [];
        foreach ($rawSkills as $skill) {
            if (is_string($skill)) {
                $skillsList[] = strtolower($skill);
            } elseif (is_array($skill)) {
                if (isset($skill['value'])) {
                    $skillsList[] = strtolower($skill['value']);
                } elseif (isset($skill['name'])) {
                    $skillsList[] = strtolower($skill['name']);
                } else {
                    foreach ($skill as $val) {
                        if (is_string($val)) {
                            $skillsList[] = strtolower($val);
                        }
                    }
                }
            }
        }
        $candidateSkills = array_unique($skillsList);
        
        $jobTitle = strtolower($job->title);
        $jobDesc = strtolower($job->description);
        
        if (!empty($candidateSkills)) {
            $matched = 0;
            foreach ($candidateSkills as $skill) {
                if (str_contains($jobTitle, $skill) || str_contains($jobDesc, $skill)) {
                    $matched++;
                }
            }
            $skillsScore = count($candidateSkills) > 0 ? (int)(($matched / count($candidateSkills)) * 100) : 0;
            // Cap skills matching score with a base minimum of 30 if at least 1 skill matched
            if ($matched > 0 && $skillsScore < 30) {
                $skillsScore = 30;
            }
        }

        // 2. Saved Jobs Score (0 or 100)
        $savedJobsScore = 0;
        $isSaved = \DB::table('saved_jobs')
            ->where('user_id', $candidate->id)
            ->where('job_id', $job->id)
            ->exists();
        if ($isSaved) {
            $savedJobsScore = 100;
        }

        // 3. Company Interactions Score (0 - 100)
        $companyFollowsScore = 0;
        // Check if candidate previously applied to this company's jobs
        if ($job->company) {
            // Check if user follows this company (highest priority)
            $hasFollowedCompany = \DB::table('company_follows')
                ->where('user_id', $candidate->id)
                ->where('company_id', $job->company->id)
                ->exists();

            if ($hasFollowedCompany) {
                $companyFollowsScore = 100;
            } else {
                $hasPreviousApply = \DB::table('job_applications')
                    ->join('jobs', 'job_applications.job_id', '=', 'jobs.id')
                    ->where('job_applications.user_id', $candidate->id)
                    ->where('jobs.company_id', $job->company->id)
                    ->exists();
                if ($hasPreviousApply) {
                    $companyFollowsScore = 80;
                } else {
                    // Check if they viewed this company's profile
                    $viewedCompany = UserBehaviorLog::where('user_id', $candidate->id)
                        ->where('activity_type', 'company_visit')
                        ->where('target_id', $job->company->id)
                        ->exists();
                    if ($viewedCompany) {
                        $companyFollowsScore = 60;
                    }
                }
            }
        }

        // 4. Recent Activity Score (0 - 100)
        $recentActivityScore = 10;
        $lastActive = UserBehaviorLog::where('user_id', $candidate->id)
            ->orderBy('created_at', 'desc')
            ->first();
        if ($lastActive) {
            $days = $lastActive->created_at->diffInDays(now());
            if ($days <= 3) {
                $recentActivityScore = 100;
            } elseif ($days <= 10) {
                $recentActivityScore = 80;
            } elseif ($days <= 30) {
                $recentActivityScore = 50;
            } else {
                $recentActivityScore = 20;
            }
        }

        // 5. Location Relevance Score (0 - 100)
        $locationScore = 0;
        $candidateCity = strtolower($profile->city ?? '');
        $jobLocation = strtolower($job->location ?? '');
        if (!empty($candidateCity) && !empty($jobLocation)) {
            if (str_contains($jobLocation, $candidateCity) || str_contains($candidateCity, $jobLocation)) {
                $locationScore = 100;
            } elseif ($job->job_type === 'Remote' || $job->is_remote_project) {
                $locationScore = 80; // Remote matches well regardless of city
            } else {
                $locationScore = 20;
            }
        } else {
            $locationScore = 30;
        }

        // 6. Premium Boosting Score (0 or 100)
        $premiumBoostingScore = 0;
        // Check if user has active premium subscription
        $hasPremium = \DB::table('user_subscriptions')
            ->where('user_id', $candidate->id)
            ->where('status', 'active')
            ->exists();
        if ($hasPremium) {
            $premiumBoostingScore = 100;
        }

        // Compute weighted total
        $wSkills = $settings->skills_matching_weight;
        $wSaved = $settings->saved_jobs_weight;
        $wCompany = $settings->company_follows_weight;
        $wRecent = $settings->recent_activity_weight;
        $wLocation = $settings->location_relevance_weight;
        $wPremium = $settings->premium_boosting_weight;

        $totalWeight = $wSkills + $wSaved + $wCompany + $wRecent + $wLocation + $wPremium;
        if ($totalWeight === 0) {
            $totalWeight = 100;
        }

        $earnedSkills = ($skillsScore * $wSkills) / 100;
        $earnedSaved = ($savedJobsScore * $wSaved) / 100;
        $earnedCompany = ($companyFollowsScore * $wCompany) / 100;
        $earnedRecent = ($recentActivityScore * $wRecent) / 100;
        $earnedLocation = ($locationScore * $wLocation) / 100;
        $earnedPremium = ($premiumBoostingScore * $wPremium) / 100;

        $totalScore = (int)(($earnedSkills + $earnedSaved + $earnedCompany + $earnedRecent + $earnedLocation + $earnedPremium) * (100 / $totalWeight));
        if ($totalScore > 100) $totalScore = 100;

        return [
            'total_score' => $totalScore,
            'breakdown' => [
                'skills_matching' => [
                    'label' => 'Skill Matching',
                    'score' => $skillsScore,
                    'weight' => $wSkills,
                    'earned' => round($earnedSkills, 1)
                ],
                'saved_jobs' => [
                    'label' => 'Saved Job Alignment',
                    'score' => $savedJobsScore,
                    'weight' => $wSaved,
                    'earned' => round($earnedSaved, 1)
                ],
                'company_follows' => [
                    'label' => 'Company Follow / View',
                    'score' => $companyFollowsScore,
                    'weight' => $wCompany,
                    'earned' => round($earnedCompany, 1)
                ],
                'recent_activity' => [
                    'label' => 'Recent Platform Activity',
                    'score' => $recentActivityScore,
                    'weight' => $wRecent,
                    'earned' => round($earnedRecent, 1)
                ],
                'location_relevance' => [
                    'label' => 'Location Relevance',
                    'score' => $locationScore,
                    'weight' => $wLocation,
                    'earned' => round($earnedLocation, 1)
                ],
                'premium_boosting' => [
                    'label' => 'Premium Account Boost',
                    'score' => $premiumBoostingScore,
                    'weight' => $wPremium,
                    'earned' => round($earnedPremium, 1)
                ]
            ]
        ];
    }

    /**
     * Compute risk & quality scores based on user behavior logs.
     * Respects manual overrides if set by an admin.
     */
    public static function calculateRiskAndQualityScore($userOrProfile): array
    {
        $profile = null;
        $user = null;

        if ($userOrProfile instanceof User) {
            $user = $userOrProfile;
            $profile = $user->profile;
        } elseif ($userOrProfile instanceof UserProfile) {
            $profile = $userOrProfile;
            $user = $profile->user;
        } elseif ($userOrProfile instanceof Company) {
            $company = $userOrProfile;
            $user = $company->user;
            
            // For companies, calculate risk / quality:
            $risk = 0;
            $quality = 100;

            // Automated flags
            if ($company->profile_completion_percentage < 40) {
                $risk += 25;
            }
            if ($company->rating && $company->rating < 3.0) {
                $risk += 30;
            }
            if ($company->is_verified) {
                $risk -= 20;
            }
            $risk = max(0, min(100, $risk));

            $quality = max(0, min(100, 100 - $risk + ($company->is_verified ? 20 : 0)));

            // Handle manual overrides
            if ($company->manual_override_score !== null) {
                $quality = $company->manual_override_score;
                $risk = 100 - $quality;
            }

            return [
                'risk_score' => $risk,
                'quality_score' => $quality,
                'override_active' => $company->manual_override_score !== null,
                'status' => $company->manual_override_status ?? ($risk > 60 ? 'Suspicious' : ($risk > 30 ? 'Caution' : 'Trusted'))
            ];
        }

        if (!$profile || !$user) {
            return ['risk_score' => 0, 'quality_score' => 100, 'override_active' => false, 'status' => 'Trusted'];
        }

        // Automated Risk calculation for Candidates
        $risk = 0;

        // Factor 1: Active logs and activity ratio
        $appCount = UserBehaviorLog::where('user_id', $user->id)->where('activity_type', 'application')->count();
        $saveCount = UserBehaviorLog::where('user_id', $user->id)->where('activity_type', 'job_save')->count();
        if ($appCount > 15 && $profile->completed_jobs_count === 0) {
            // High application spam with no completions
            $risk += 25;
        }

        // Factor 2: Profile completion details
        if ($profile->profile_completion_percentage < 30) {
            $risk += 25;
        } elseif ($profile->profile_completion_percentage < 50) {
            $risk += 10;
        }

        // Factor 3: Ratings
        if ($profile->rating !== null && $profile->rating < 3.0) {
            $risk += 35;
        }

        // Factor 4: Verification status reduces risk
        $hasVerifiedBadge = \DB::table('badge_user')
            ->join('badges', 'badge_user.badge_id', '=', 'badges.id')
            ->where('badge_user.user_id', $user->id)
            ->where('badges.badge_key', 'verified')
            ->exists();
        if ($hasVerifiedBadge || $profile->reputation_status === 'verified') {
            $risk -= 30;
        }

        $risk = max(0, min(100, $risk));

        // Automated Quality calculation
        $quality = 100 - $risk;
        if ($profile->completed_jobs_count > 5) {
            $quality += 15;
        }
        if ($profile->is_featured) {
            $quality += 10;
        }
        $quality = max(0, min(100, $quality));

        // Overrides check
        $overrideActive = $profile->manual_override_score !== null;
        if ($overrideActive) {
            $quality = $profile->manual_override_score;
            $risk = 100 - $quality;
        }

        $status = $profile->manual_override_status;
        if (empty($status)) {
            if ($risk > 60) {
                $status = 'Suspicious';
            } elseif ($risk > 30) {
                $status = 'Caution';
            } else {
                $status = 'Trusted';
            }
        }

        return [
            'risk_score' => $risk,
            'quality_score' => $quality,
            'override_active' => $overrideActive,
            'status' => $status
        ];
    }

    /**
     * Compile behavior telemetry history and generate a beautiful human-readable narrative summary using AI.
     */
    public static function generateUserBehaviorSummary($userOrProfile): string
    {
        $profile = null;
        $company = null;
        $user = null;

        if ($userOrProfile instanceof User) {
            $user = $userOrProfile;
            $profile = $user->profile;
            $company = $user->company;
        } elseif ($userOrProfile instanceof UserProfile) {
            $profile = $userOrProfile;
            $user = $profile->user;
        } elseif ($userOrProfile instanceof Company) {
            $company = $userOrProfile;
            $user = $company->user;
        }

        if (!$user) {
            return "No behavior telemetry data found.";
        }

        // Fetch logs count
        $logs = UserBehaviorLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        if ($logs->isEmpty()) {
            $roleLabel = $company ? 'Employer' : 'Candidate';
            return "This {$roleLabel} is relatively new to the platform and has not yet established a significant activity history. Early parameters indicate positive compliance.";
        }

        // Aggregate behaviors
        $counts = $logs->groupBy('activity_type')->map(fn($item) => count($item))->toArray();
        
        $activityReport = "User ID: {$user->id}, Email: {$user->email}\n";
        $activityReport .= "Role: " . ($company ? 'Employer' : 'Candidate') . "\n";
        $activityReport .= "Activity Breakdown:\n";
        foreach ($counts as $type => $count) {
            $activityReport .= "- {$type}: {$count} times\n";
        }

        // Gather location/completion data for AI context
        if ($company) {
            $activityReport .= "Profile Completion: {$company->profile_completion_percentage}%\n";
            $activityReport .= "Company Industry: {$company->industry}, Size: {$company->size}\n";
        } else {
            $skillsArray = [];
            foreach ((array)($profile->skills ?? []) as $s) {
                if (is_string($s)) {
                    $skillsArray[] = $s;
                } elseif (is_array($s)) {
                    $skillsArray[] = $s['value'] ?? $s['name'] ?? json_encode($s);
                }
            }
            $activityReport .= "Profile Completion: {$profile->profile_completion_percentage}%\n";
            $activityReport .= "Candidate City: {$profile->city}, Skills: " . implode(', ', $skillsArray) . "\n";
        }

        $prompt = "You are an advanced AI Talent & Platform Integrity Auditor. Analyze the following user activity report:
        
        {$activityReport}
        
        Write a professional, human-readable 2-3 sentence behavior summary paragraph analyzing their platform behavior.
        - If they are a candidate, highlight their job application intensity, skill focus, interest in remote vs onsite, engagement levels, and activity trends.
        - If they are an employer, highlight their recruitment habits, size of hires, and speed of interaction.
        - Keep the tone formal, insightful, and strictly narrative (no bullet points, no preamble). Max 80 words.";

        // Invoke the AI manager (fully failover-safe!)
        $summary = AiManagerService::ask($prompt);

        if (!$summary) {
            $skillsArray = [];
            foreach ((array)($profile->skills ?? []) as $s) {
                if (is_string($s)) {
                    $skillsArray[] = $s;
                } elseif (is_array($s)) {
                    $skillsArray[] = $s['value'] ?? $s['name'] ?? json_encode($s);
                }
            }
            if (empty($skillsArray)) {
                $skillsArray = ['Web Development'];
            }
            $summary = "Active candidate showing strong interest in " . implode(', ', array_slice($skillsArray, 0, 3)) . " roles. Frequently browses job openings with high platform engagement.";
        }

        $summaryText = trim($summary);

        // Store the summary in the database
        if ($company) {
            $company->update(['behavior_summary' => $summaryText]);
        } else {
            $profile->update(['behavior_summary' => $summaryText]);
        }

        return $summaryText;
    }
}
