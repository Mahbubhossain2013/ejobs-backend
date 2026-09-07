<?php

namespace App\Services\Ai;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Resume;
use App\Models\UserBehaviorLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\Ai\AiAlgorithmService;
use App\Models\Job;

class AiCareerCoachService
{
    /**
     * Compile candidate profiles and query AI for structured coaching diagnostics.
     */
    public function getCareerInsights(User $user, bool $bypassCache = false): array
    {
        $cacheKey = 'ai_career_insights_' . $user->id;
        
        if (!$bypassCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $profile = $user->profile;
        if (!$profile) {
            return $this->getFallbackInsights();
        }

        // 1. Gather all profile statistics
        $skills = (array)($profile->skills ?? []);
        $experience = (array)($profile->experience ?? []);
        $projects = (array)($profile->projects ?? []);
        
        $savedJobsCount = DB::table('saved_jobs')->where('user_id', $user->id)->count();
        $appliedJobsCount = DB::table('job_applications')->where('user_id', $user->id)->count();
        $rejectedJobsCount = DB::table('job_applications')
            ->where('user_id', $user->id)
            ->where('status', 'rejected')
            ->count();

        $builderResume = Resume::where('user_id', $user->id)->latest()->first();
        $resumeText = $builderResume ? json_encode($builderResume->data_snapshot) : ($profile->resume_path ? 'Resume file uploaded' : 'None');

        $behaviorSummary = $profile->behavior_summary ?: AiAlgorithmService::generateUserBehaviorSummary($user);

        // 2. Draft the AI instruction context
        $context = [
            'name' => $user->name,
            'title' => $profile->current_position ?? 'Entry Level Professional',
            'completion_percentage' => $profile->profile_completion_percentage ?? 0,
            'skills' => $skills,
            'experience' => $experience,
            'projects_portfolio' => $projects,
            'saved_jobs_count' => $savedJobsCount,
            'applied_jobs_count' => $appliedJobsCount,
            'rejected_jobs_count' => $rejectedJobsCount,
            'resume_data' => $resumeText,
            'behavior_patterns' => $behaviorSummary,
        ];

        $prompt = "You are an Elite AI Executive Career Coach. Analyze the following candidate profile telemetry data:
        " . json_encode($context) . "
        
        Generate a comprehensive, highly personalized, and action-oriented career guidance report.
        
        CRITICAL: You must return ONLY a valid raw JSON object (no markdown formatting, no code blocks, no preamble, ONLY JSON). Use this EXACT JSON template:
        {
            \"career_insights\": \"A compelling, formal 2-3 sentence overview of their current career standing and potential.\",
            \"skills_to_improve\": [\"Skill Name - explanation of why to learn\", \"...\"] ,
            \"rejection_reasons_analysis\": \"Constructive analysis of why they might experience proposals rejection, looking at resume, skills gaps, or application volume.\",
            \"suggested_salary_expectation\": \"Suggest a realistic salary range based on skills/title in BDT/USD.\",
            \"career_growth_roadmap\": [
                {
                    \"phase\": \"Phase 1: Immediate Actions (1-3 months)\",
                    \"milestones\": [\"Upgrade resume to match X\", \"Get certified in Y\"]
                },
                {
                    \"phase\": \"Phase 2: Transition (3-6 months)\",
                    \"milestones\": [\"Build 2 high-quality projects showing Z\", \"Apply for Mid-Level roles\"]
                }
            ],
            \"profile_improvement_suggestions\": [\"Improve profile bio by doing X\", \"Add more specific tech tags\"],
            \"cv_optimization_tips\": [\"Include X keyword to clear ATS\", \"Elaborate project descriptions\"],
            \"missing_skill_analysis\": [\"Skill Name - high demand in market and missing from profile\"],
            \"industry_trend_suggestions\": [\"Trend X in their category is booming, learn tools related to it\"],
            \"interview_prep_suggestions\": [\"Question 1 - how they should answer based on their experience\", \"Question 2 - technical question customized for them\"]
        }
        
        Make sure the feedback is hyper-focused on their actual skills and experience.";

        try {
            $aiResponse = AiManagerService::ask($prompt, temperature: 0.7, maxTokens: 2500);

            // Clean response
            $cleanJson = trim(str_replace(['```json', '```'], '', $aiResponse));
            $insights = json_decode($cleanJson, true);

            if (!$insights || !isset($insights['career_insights'])) {
                throw new \Exception("AI generated invalid or missing career coaching nodes.");
            }

            // Calculate dynamic recommendations matching candidate capabilities
            $allActiveJobs = Job::where('is_active', true)->with('company')->latest()->limit(30)->get();
            $matchedJobs = [];
            foreach ($allActiveJobs as $jobItem) {
                $scoreDetails = AiAlgorithmService::computeMatchScore($user, $jobItem);
                if ($scoreDetails['total_score'] >= 35) {
                    $matchedJobs[] = [
                        'id' => $jobItem->id,
                        'title' => $jobItem->title,
                        'company_name' => $jobItem->company->name ?? 'Confidential',
                        'match_score' => $scoreDetails['total_score'],
                        'location' => $jobItem->location ?? 'Remote',
                        'budget' => $jobItem->budget,
                        'is_remote' => (bool)$jobItem->is_remote_project,
                    ];
                }
            }

            usort($matchedJobs, function ($a, $b) {
                return $b['match_score'] <=> $a['match_score'];
            });

            $insights['best_matching_jobs'] = array_slice($matchedJobs, 0, 3);

            // Save in cache for 12 hours
            Cache::put($cacheKey, $insights, now()->addHours(12));

            return $insights;

        } catch (\Exception $e) {
            Log::error("AI Career Coach Service failed: " . $e->getMessage());
            return [
                'career_insights' => null,
                'error' => 'AI career coaching is temporarily unavailable. Please try again later.',
                'best_matching_jobs' => [],
            ];
        }
    }

    /**
     * Return error state when AI is unavailable.
     * Removed: hardcoded fallback advice that was always shown instead of real AI insights.
     */
    protected function getFallbackInsights(array $context = [], ?User $user = null): array
    {
        $bestMatchingJobs = [];
        if ($user) {
            $allActiveJobs = Job::where('is_active', true)->with('company')->latest()->limit(30)->get();
            $matchedJobs = [];
            foreach ($allActiveJobs as $jobItem) {
                $scoreDetails = AiAlgorithmService::computeMatchScore($user, $jobItem);
                if ($scoreDetails['total_score'] >= 35) {
                    $matchedJobs[] = [
                        'id' => $jobItem->id,
                        'title' => $jobItem->title,
                        'company_name' => $jobItem->company->name ?? 'Confidential',
                        'match_score' => $scoreDetails['total_score'],
                        'location' => $jobItem->location ?? 'Remote',
                        'budget' => $jobItem->budget,
                        'is_remote' => (bool)$jobItem->is_remote_project,
                    ];
                }
            }

            usort($matchedJobs, fn($a, $b) => $b['match_score'] <=> $a['match_score']);
            $bestMatchingJobs = array_slice($matchedJobs, 0, 3);
        }

        return [
            'career_insights' => null,
            'error' => 'AI career coaching is temporarily unavailable. Please try again later.',
            'skills_to_improve' => [],
            'rejection_reasons_analysis' => null,
            'suggested_salary_expectation' => null,
            'career_growth_roadmap' => [],
            'profile_improvement_suggestions' => [],
            'cv_optimization_tips' => [],
            'missing_skill_analysis' => [],
            'industry_trend_suggestions' => [],
            'interview_prep_suggestions' => [],
            'best_matching_jobs' => $bestMatchingJobs,
        ];
    }
}
