<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Company;
use App\Models\AiMatchScore;
use App\Models\AiUsageLog;
use App\Models\AiModerationLog;
use App\Services\Ai\AiManagerService;
use App\Services\Ai\AiCareerCoachService;
use App\Services\ProfileStrengthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AiFeatureController extends Controller
{
    /**
     * Generate a professional Job Description using AI
     */
    public function generateJobDescription(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'category' => 'nullable|string',
        ]);

        $user = Auth::user();
        if (!$user->profile || !$user->profile->is_verified) {
            return response()->json([
                'status' => false, 
                'message' => 'AI generation is only available for verified employers.'
            ], 403);
        }

        $title = $request->title;
        $category = $request->category ?? 'General';

        $prompt = "Write a professional, detailed job description for the position: '{$title}' in the '{$category}' sector. 
        Structure the response with HTML tags. Include sections: Job Summary, Key Responsibilities, Required Qualifications, and Benefits. 
        Return ONLY the HTML content.";

        try {
            $aiResponse = AiManagerService::ask($prompt);
            return response()->json([
                'status' => true,
                'description' => $aiResponse
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error generating content.'], 500);
        }
    }

    /**
     * AI Match Score: Compares Candidate Profile with Job Description
     * Includes caching to prevent redundant API calls
     */
    public function getMatchScore($jobId)
    {
        $user = Auth::user();



        $job = Job::with('company')->findOrFail($jobId);
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['status' => false, 'message' => 'Profile not found'], 400);
        }

        // 1. Check for cached result to save AI costs
        $cachedMatch = AiMatchScore::where('user_id', $user->id)
                                   ->where('job_id', $jobId)
                                   ->first();
                                   
        if ($cachedMatch) {
            return response()->json([
                'status' => true, 
                'data' => $cachedMatch
            ]);
        }

        // 2. Calculate profile strength as context for AI
        $profileStrength = ProfileStrengthService::calculate($user);

        // 3. Prepare context for AI
        $candidateData = [
            'bio' => $profile->bio, 
            'skills' => $profile->skills, 
            'experience' => $profile->experience,
            'profile_strength' => $profileStrength,
        ];
        
        $jobData = [
            'title' => $job->title, 
            'description' => strip_tags($job->description)
        ];

        // 4. Prompt Engineering — detailed breakdown for candidates
        $prompt = "You are a career matching expert. Compare this Candidate Profile with the Job Requirements and give a detailed match analysis.

CANDIDATE PROFILE:
" . json_encode($candidateData, JSON_PRETTY_PRINT) . "

JOB REQUIREMENTS:
Title: {$job->title}
Description: {$jobData['description']}

Profile Strength: {$profileStrength}/100 (higher = more complete profile)

ANALYSIS INSTRUCTIONS:
1. Compare the candidate's skills, experience, and education against the job requirements
2. Score 0-100 based on: skill match (40%), experience relevance (30%), profile completeness (20%), education fit (10%)
3. List specific skills the candidate HAS that match the job
4. List specific skills the candidate is MISSING that the job requires
5. Give practical advice on how to improve the match

Return ONLY a valid JSON object (no markdown, no code fences):
{
  \"score\": <number 0-100>,
  \"matched_skills\": [\"skill1\", \"skill2\"],
  \"missing_skills\": [\"skill1\", \"skill2\"],
  \"advice\": \"<2-3 sentence practical advice for the candidate>\"
}";

        try {
            $aiResponse = AiManagerService::ask($prompt);
            
            // Clean markdown code fences and decode
            $cleanJson = preg_replace('/```(?:json)?\s*/i', '', $aiResponse);
            $cleanJson = preg_replace('/```\s*/', '', $cleanJson);
            $cleanJson = trim($cleanJson);
            $analysis = json_decode($cleanJson, true);

            if (!is_array($analysis) || !isset($analysis['score'])) {
                throw new \Exception("Invalid AI response structure: " . substr($cleanJson, 0, 200));
            }

            // Ensure arrays exist
            $analysis['matched_skills'] = $analysis['matched_skills'] ?? [];
            $analysis['missing_skills'] = $analysis['missing_skills'] ?? [];
            $analysis['advice'] = $analysis['advice'] ?? '';

            // 5. Blend AI score with profile strength (80% AI, 20% profile strength)
            $finalScore = round(($analysis['score'] * 0.8) + ($profileStrength * 0.2));
            $analysis['score'] = min(100, max(0, $finalScore));
            $analysis['profile_strength'] = $profileStrength;

            // 6. Store in database for future requests
            $newMatch = AiMatchScore::create([
                'user_id' => $user->id,
                'job_id'  => $jobId,
                'score'   => $analysis['score'],
                'analysis' => $analysis // Assuming 'analysis' is cast to array/json in Model
            ]);

            return response()->json([
                'status' => true, 
                'data' => $newMatch
            ]);

        } catch (\Exception $e) {
            Log::warning("AI Match Error (using fallback): " . $e->getMessage());

            // Fallback: calculate a basic score from profile strength + skill overlap
            $candidateSkills = array_map('strtolower', $profile->skills ?? []);
            $jobKeywords = preg_split('/[\s,;]+/', strtolower($job->title . ' ' . strip_tags($job->description ?? '')));
            $matched = array_intersect($candidateSkills, $jobKeywords);
            $skillScore = count($candidateSkills) > 0 ? min(60, (count($matched) / max(count($candidateSkills), 1)) * 60) : 20;
            $fallbackScore = round(($skillScore * 0.6) + ($profileStrength * 0.4));

            $fallbackData = [
                'score' => min(100, max(0, $fallbackScore)),
                'matched_skills' => array_values(array_slice($matched, 0, 10)),
                'missing_skills' => [],
                'advice' => 'AI analysis is temporarily unavailable. This score is based on your profile strength and skill matching.',
                'profile_strength' => $profileStrength,
                'is_fallback' => true,
            ];

            $newMatch = AiMatchScore::create([
                'user_id' => $user->id,
                'job_id' => $jobId,
                'score' => $fallbackData['score'],
                'analysis' => $fallbackData,
            ]);

            return response()->json([
                'status' => true,
                'data' => $newMatch,
            ]);
        }
    }

    /**
     * Generate CV profile from user prompt using AI
     * Uses AiManagerService for consistent AI handling
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateCvProfile(Request $request)
    {
        try {
            $user = Auth::user();

            if ($user->hasRole('candidate') && !$user->hasFeature('ai_cv_builder')) {
                return response()->json([
                    'status' => false,
                    'message' => 'AI CV Builder requires a paid subscription.',
                    'feature_key' => 'ai_cv_builder',
                ], 403);
            }

            $validated = $request->validate([
                'prompt' => 'required|string|min:10|max:5000',
            ]);

            $prompt = $validated['prompt'];

            Log::info('AI CV Generation started', [
                'user_id' => $user->id,
                'prompt_length' => strlen($prompt),
            ]);

            // Build comprehensive system prompt
            $systemPrompt = $this->buildCvSystemPrompt();

            // Call AI service
            $aiResponse = AiManagerService::ask(
                $systemPrompt . "\n\nUser Input:\n" . $prompt,
                temperature: 0.7,
                maxTokens: 2000
            );

            // Clean and parse JSON response
            $cleanJson = trim(str_replace(['```json', '```'], '', $aiResponse));
            $cvData = json_decode($cleanJson, true);

            if (!$cvData || !is_array($cvData)) {
                throw new \Exception('Failed to parse AI response as valid JSON');
            }

            // Validate generated data
            $validatedData = $this->validateGeneratedCvData($cvData);

            Log::info('AI CV Generation completed', [
                'user_id' => $user->id,
                'sections_count' => count($validatedData),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'CV profile generated successfully',
                'data' => $validatedData,
            ]);
        } catch (\Exception $e) {
            Log::error('AI CV Generation error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to generate CV profile: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build system prompt for CV generation
     * 
     * @return string
     */
    private function buildCvSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert CV/Resume builder assistant. Generate a structured CV profile from user descriptions.

CRITICAL: Return ONLY valid JSON (no markdown, no code blocks, ONLY JSON).

Generate JSON with this EXACT structure:
{
    "personal_info": {
        "full_name": "String - full name",
        "title": "String - professional title/role",
        "email": "String - email if provided",
        "phone": "String - phone if provided",
        "summary": "String - 2-3 sentence professional summary",
        "location": "String - location if mentioned"
    },
    "skills": ["Skill1", "Skill2", "Skill3"],
    "experience": [
        {
            "job_title": "Position name",
            "company_name": "Company name",
            "start_date": "YYYY-MM",
            "end_date": "YYYY-MM or Present",
            "description": "Key achievements"
        }
    ],
    "education": [
        {
            "school_name": "University name",
            "degree": "Degree type",
            "field_of_study": "Major",
            "graduation_year": "YYYY"
        }
    ],
    "projects": [
        {
            "project_name": "Name",
            "description": "What was built",
            "technologies": ["Tech1", "Tech2"],
            "url": "URL if available"
        }
    ],
    "social_links": {
        "linkedin": "URL",
        "github": "URL",
        "portfolio": "URL",
        "twitter": "URL"
    }
}

Rules:
- Extract ALL information provided
- Use realistic dates (YYYY-MM format)
- Keep descriptions professional and concise
- Return ONLY JSON - no explanations
- Empty arrays [] for missing sections
- Don't invent information
PROMPT;
    }

    /**
     * Validate and clean generated CV data
     * 
     * @param array $data
     * @return array
     */
    private function validateGeneratedCvData(array $data): array
    {
        return [
            'personal_info' => [
                'full_name' => is_string($data['personal_info']['full_name'] ?? null) ? trim($data['personal_info']['full_name']) : '',
                'email' => is_string($data['personal_info']['email'] ?? null) ? trim($data['personal_info']['email']) : '',
                'phone' => is_string($data['personal_info']['phone'] ?? null) ? trim($data['personal_info']['phone']) : '',
                'title' => is_string($data['personal_info']['title'] ?? null) ? trim($data['personal_info']['title']) : '',
                'summary' => is_string($data['personal_info']['summary'] ?? null) ? trim($data['personal_info']['summary']) : '',
                'location' => is_string($data['personal_info']['location'] ?? null) ? trim($data['personal_info']['location']) : '',
            ],
            'skills' => array_filter(
                (array)($data['skills'] ?? []),
                fn($s) => is_string($s) && !empty(trim($s))
            ),
            'experience' => array_map(fn($e) => [
                'job_title' => is_string($e['job_title'] ?? null) ? trim($e['job_title']) : '',
                'company_name' => is_string($e['company_name'] ?? null) ? trim($e['company_name']) : '',
                'start_date' => is_string($e['start_date'] ?? null) ? trim($e['start_date']) : '',
                'end_date' => is_string($e['end_date'] ?? null) ? trim($e['end_date']) : '',
                'description' => is_string($e['description'] ?? null) ? trim($e['description']) : '',
            ], (array)($data['experience'] ?? [])),
            'education' => array_map(fn($e) => [
                'school_name' => is_string($e['school_name'] ?? null) ? trim($e['school_name']) : '',
                'degree' => is_string($e['degree'] ?? null) ? trim($e['degree']) : '',
                'field_of_study' => is_string($e['field_of_study'] ?? null) ? trim($e['field_of_study']) : '',
                'graduation_year' => is_string($e['graduation_year'] ?? null) ? trim($e['graduation_year']) : '',
            ], (array)($data['education'] ?? [])),
            'projects' => array_map(fn($p) => [
                'project_name' => is_string($p['project_name'] ?? null) ? trim($p['project_name']) : '',
                'description' => is_string($p['description'] ?? null) ? trim($p['description']) : '',
                'technologies' => array_filter((array)($p['technologies'] ?? []), 'is_string'),
                'url' => is_string($p['url'] ?? null) ? trim($p['url']) : '',
            ], (array)($data['projects'] ?? [])),
            'social_links' => [
                'linkedin' => is_string($data['social_links']['linkedin'] ?? null) ? trim($data['social_links']['linkedin']) : '',
                'github' => is_string($data['social_links']['github'] ?? null) ? trim($data['social_links']['github']) : '',
                'portfolio' => is_string($data['social_links']['portfolio'] ?? null) ? trim($data['social_links']['portfolio']) : '',
                'twitter' => is_string($data['social_links']['twitter'] ?? null) ? trim($data['social_links']['twitter']) : '',
            ],
        ];
    }

    /**
     * Track user activity behavior logs
     */
    public function trackBehavior(Request $request)
    {
        $request->validate([
            'activity_type' => 'required|string|max:50',
            'target_id' => 'nullable|integer',
            'meta_data' => 'nullable|array',
        ]);

        $userId = Auth::id();
        $success = \App\Services\Ai\AiAlgorithmService::trackActivity(
            $userId,
            $request->activity_type,
            $request->target_id,
            $request->meta_data
        );

        return response()->json([
            'status' => true,
            'logged' => $success,
            'message' => $success ? 'Behavior tracked successfully' : 'Behavior tracking skipped/disabled'
        ]);
    }

    /**
     * Get detailed AI recommendations match insights for a specific Job
     */
    public function getJobAiInsights($jobId)
    {
        $user = Auth::user();
        $job = Job::with('company')->findOrFail($jobId);

        $insights = \App\Services\Ai\AiAlgorithmService::computeMatchScore($user, $job);

        return response()->json([
            'status' => true,
            'data' => array_merge($insights, [
                'job_title' => $job->title,
                'company_name' => $job->company->name ?? 'Unknown Company',
            ])
        ]);
    }

    /**
     * Get candidate profile behavioral insights and risk/quality diagnostics
     */
    public function getCandidateAiInsights($username)
    {
        $candidateUser = \App\Models\User::where('username', $username)->with('profile')->firstOrFail();
        $profile = $candidateUser->profile;

        if (!$profile) {
            return response()->json(['status' => false, 'message' => 'Candidate profile not found'], 404);
        }

        // Auto generate behavior summary if missing
        if (empty($profile->behavior_summary)) {
            \App\Services\Ai\AiAlgorithmService::generateUserBehaviorSummary($candidateUser);
            $profile->refresh();
        }

        $diagnostics = \App\Services\Ai\AiAlgorithmService::calculateRiskAndQualityScore($candidateUser);
        $logsCount = \App\Models\UserBehaviorLog::where('user_id', $candidateUser->id)->count();

        return response()->json([
            'status' => true,
            'data' => [
                'user_id' => $candidateUser->id,
                'name' => $candidateUser->name,
                'username' => $candidateUser->username,
                'email' => $candidateUser->email,
                'behavior_summary' => $profile->behavior_summary,
                'risk_score' => $diagnostics['risk_score'],
                'quality_score' => $diagnostics['quality_score'],
                'override_active' => $diagnostics['override_active'],
                'status' => $diagnostics['status'],
                'total_events_logged' => $logsCount,
                'logs' => \App\Models\UserBehaviorLog::where('user_id', $candidateUser->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
            ]
        ]);
    }

    /**
     * Submit risk/quality scoring manual overrides by Admin
     */
    public function submitManualOverride(Request $request)
    {
        $request->validate([
            'target_type' => 'required|string|in:candidate,employer',
            'target_id' => 'required|integer',
            'manual_override_score' => 'nullable|integer|min:0|max:100',
            'manual_override_status' => 'nullable|string|in:Trusted,Caution,Suspicious',
            'override_reason' => 'nullable|string|max:1000',
        ]);

        if ($request->target_type === 'candidate') {
            $profile = \App\Models\UserProfile::where('user_id', $request->target_id)->firstOrFail();
            $profile->update([
                'manual_override_score' => $request->manual_override_score,
                'manual_override_status' => $request->manual_override_status,
                'override_reason' => $request->override_reason,
            ]);
            
            // Re-generate behavior summary for candidate
            $candidateUser = \App\Models\User::findOrFail($request->target_id);
            \App\Services\Ai\AiAlgorithmService::generateUserBehaviorSummary($candidateUser);
            
            $result = \App\Services\Ai\AiAlgorithmService::calculateRiskAndQualityScore($candidateUser);
        } else {
            $company = Company::findOrFail($request->target_id);
            $company->update([
                'manual_override_score' => $request->manual_override_score,
                'manual_override_status' => $request->manual_override_status,
                'override_reason' => $request->override_reason,
            ]);

            // Re-generate behavior summary for employer
            \App\Services\Ai\AiAlgorithmService::generateUserBehaviorSummary($company);

            $result = \App\Services\Ai\AiAlgorithmService::calculateRiskAndQualityScore($company);
        }

        return response()->json([
            'status' => true,
            'message' => 'Manual override scores applied successfully!',
            'data' => $result
        ]);
    }

    /**
     * AI Career Coach: Generates contextual recommendations for candidates
     */
    public function getCandidateCoachInsights(Request $request)
    {
        $user = Auth::user();
        if ($user->hasRole('candidate') && !$user->hasFeature('ai_career_tools')) {
            return response()->json([
                'status' => false,
                'message' => 'AI Career Coach requires a paid subscription.',
                'feature_key' => 'ai_career_tools',
            ], 403);
        }
        $bypassCache = $request->input('refresh') === 'true' || $request->input('refresh') == 1;

        $coachService = new AiCareerCoachService();
        $insights = $coachService->getCareerInsights($user, $bypassCache);

        return response()->json([
            'status' => true,
            'data' => $insights
        ]);
    }

    /**
     * Employer Hiring Insights: Compatibility audit, spam verification, screening questions
     */
    public function getEmployerCandidateInsights($candidateId)
    {
        $user = Auth::user();
        $candidate = User::with('profile')->findOrFail($candidateId);
        $profile = $candidate->profile;

        if (!$profile) {
            return response()->json(['status' => false, 'message' => 'Candidate profile details not configured'], 400);
        }

        // Compute compatibility scores against active job offerings of this employer
        $employerActiveJobs = Job::where('company_id', $user->company->id ?? 0)->where('is_active', true)->get();
        $maxComp = 0;
        foreach ($employerActiveJobs as $job) {
            $scoreDetails = \App\Services\Ai\AiAlgorithmService::computeMatchScore($candidate, $job);
            if ($scoreDetails['total_score'] > $maxComp) {
                $maxComp = $scoreDetails['total_score'];
            }
        }
        if ($maxComp === 0) {
            $maxComp = 65; // basline fallback matching
        }

        $behaviorDiagnostics = \App\Services\Ai\AiAlgorithmService::calculateRiskAndQualityScore($candidate);

        $prompt = "You are an Elite Recruiting Intelligence Officer. Analyze this candidate:
        Name: {$candidate->name}
        Title: {$profile->current_position}
        Skills: " . json_encode($profile->skills) . "
        Summary: {$profile->bio}
        Risk Status: {$behaviorDiagnostics['status']}
        
        Provide high-value hiring tips. Return ONLY a valid JSON:
        {
            \"compatibility_score\": {$maxComp},
            \"spam_risk_rating\": \"{$behaviorDiagnostics['status']}\",
            \"suspicious_alerts\": [\"No verified profile badges loaded\"],
            \"salary_guideline\": \"Based on credentials, suggested salary: $1,500 - $3,000/month\",
            \"screening_questions\": [\"Describe your experience with advanced skill components matching your profile.\", \"How do you manage complex deployments?\"],
            \"hiring_insights\": \"Strong mid-level candidate exhibiting exceptional alignment with standard web application engineering targets.\"
        }";

        try {
            $aiResponse = AiManagerService::ask($prompt, temperature: 0.2, maxTokens: 800);
            $cleanJson = trim(str_replace(['```json', '```'], '', $aiResponse));
            $insights = json_decode($cleanJson, true);
        } catch (\Exception $e) {
            $insights = null;
        }

        if (!$insights) {
            return response()->json([
                'status' => false,
                'message' => 'AI hiring insights are temporarily unavailable. Please try again later.',
                'data' => [
                    'compatibility_score' => $maxComp,
                    'spam_risk_rating' => $behaviorDiagnostics['status'],
                    'error' => 'AI service unavailable',
                ]
            ], 503);
        }

        return response()->json([
            'status' => true,
            'data' => $insights
        ]);
    }

    /**
     * Admin AI Telemetry Analytics: System health, token logs, error rates, fallbacks
     */
    public function getAdminAiAnalytics()
    {
        $totalLogsCount = AiUsageLog::count();
        $successfulLogs = AiUsageLog::where('status', 'success')->count();
        $failedLogs = AiUsageLog::where('status', 'failure')->count();
        
        $totalTokens = AiUsageLog::sum('total_tokens');
        $averageLatency = round(AiUsageLog::avg('duration_ms') ?? 0, 2);
        
        $providersStats = AiUsageLog::select('provider', \DB::raw('count(*) as count'))
            ->groupBy('provider')
            ->get();
            
        $recentFallbackLogs = AiUsageLog::where('was_fallback', true)->latest()->limit(15)->get();
        $recentErrorLogs = AiUsageLog::where('status', 'failure')->latest()->limit(15)->get();
        
        $moderationCount = AiModerationLog::count();
        $recentModerationViolations = AiModerationLog::latest()->limit(15)->get();

        return response()->json([
            'status' => true,
            'data' => [
                'usage_summary' => [
                    'total_requests' => $totalLogsCount,
                    'successful_requests' => $successfulLogs,
                    'failed_requests' => $failedLogs,
                    'success_rate' => $totalLogsCount > 0 ? round(($successfulLogs / $totalLogsCount) * 100, 2) : 100,
                    'total_tokens_consumed' => $totalTokens,
                    'average_latency_ms' => $averageLatency,
                    'moderation_violations_count' => $moderationCount,
                ],
                'providers_distribution' => $providersStats,
                'fallback_logs' => $recentFallbackLogs,
                'error_logs' => $recentErrorLogs,
                'moderation_logs' => $recentModerationViolations,
            ]
        ]);
    }
}