<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CandidateInterview;
use App\Models\Job;
use App\Models\CareerRoadmap;
use App\Services\Ai\AiManagerService;
use App\Services\Ai\AiAlgorithmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AiCareerAssistantController extends Controller
{
    /**
     * AI Career Assistant chat interface uploader
     */
    public function chatbot(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'chat_history' => 'nullable|array',
        ]);

        $user = auth('sanctum')->user();
        if ($user) {
            $profile = $user->profile;
            $skills = $profile ? implode(', ', (array)($profile->skills ?? [])) : 'None';
            $name = $user->name;
            $position = $profile ? ($profile->current_position ?? 'Professional') : 'Professional';
        } else {
            $skills = 'JavaScript, React, HTML, CSS, PHP';
            $name = 'Guest Explorer';
            $position = 'Full Stack Developer';
        }

        $prompt = "You are an Elite AI Career Coach. The candidate name is {$name}, currently a '{$position}' with skills: {$skills}.
        
        Answer their question: '{$request->message}'.
        Keep it highly professional, extremely practical, and structured. Focus on actionable insights.
        Keep it under 300 words.";

        try {
            $response = AiManagerService::ask($prompt, temperature: 0.7);
            return response()->json([
                'status' => true,
                'response' => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'AI Career Coach was unable to compute response.'
            ], 500);
        }
    }

    /**
     * AI Job Match Telemetry
     */
    public function jobMatch(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Please login to get personalized job recommendations'], 401);
        }

        $profile = $user->profile;
        if (!$profile) {
            return response()->json(['status' => false, 'message' => 'Configure your profile first to get job recommendations'], 400);
        }

        // Fetch top matching jobs
        $allActiveJobs = Job::where('is_active', true)->with('company')->latest()->limit(20)->get();
        $matchedJobs = [];
        foreach ($allActiveJobs as $job) {
            $scoreDetails = AiAlgorithmService::computeMatchScore($user, $job);
            if ($scoreDetails['total_score'] >= 35) {
                // Determine skills array for explanation
                $userSkills = (array)($profile->skills ?? []);
                $jobDescWords = explode(' ', strtolower($job->description));
                $matchedSkills = array_slice(array_intersect(array_map('strtolower', $userSkills), $jobDescWords), 0, 3);
                $missingSkills = array_slice(array_diff($jobDescWords, array_map('strtolower', $userSkills)), 0, 2);

                $matchedJobs[] = [
                    'id' => $job->id,
                    'title' => $job->title,
                    'company_name' => $job->company->name ?? 'Confidential',
                    'match_score' => $scoreDetails['total_score'],
                    'why_matched' => "Your skills match: " . (count($matchedSkills) > 0 ? implode(', ', $matchedSkills) : "General Web Standard"),
                    'missing_skills' => count($missingSkills) > 0 ? $missingSkills : ['Docker', 'AWS'],
                    'budget' => $job->budget ?? 'Negotiable',
                    'location' => $job->location ?? 'Remote',
                ];
            }
        }

        usort($matchedJobs, function ($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });

        return response()->json([
            'status' => true,
            'data' => [
                'overall_match_rate' => count($matchedJobs) > 0 ? $matchedJobs[0]['match_score'] : 65,
                'jobs' => array_slice($matchedJobs, 0, 4)
            ]
        ]);
    }

    /**
     * AI Salary Predictor Gauge
     */
    public function salaryPredict()
    {
        $user = auth('sanctum')->user();
        if ($user && $user->hasRole('candidate') && !$user->hasFeature('ai_career_tools')) {
            return response()->json([
                'status' => false,
                'message' => 'AI Salary Prediction requires a paid subscription.',
                'feature_key' => 'ai_career_tools',
            ], 403);
        }
        if ($user && $user->profile) {
            $profile = $user->profile;
            $title = $profile->current_position ?? 'Software Engineer';
            $skills = implode(', ', (array)($profile->skills ?? []));
            $userId = $user->id;
        } else {
            $title = 'Software Engineer';
            $skills = 'PHP, JavaScript, React, Node.js';
            $userId = 'guest';
        }

        $cacheKey = 'ai_salary_predict_' . $userId;
        if (Cache::has($cacheKey)) {
            return response()->json(['status' => true, 'data' => Cache::get($cacheKey)]);
        }

        $prompt = "Predict the market salary range in BDT/month for a candidate:
        Role: {$title}
        Skills: {$skills}
        
        Return ONLY valid JSON (no markdown, ONLY JSON):
        {
            \"min_salary\": 40000,
            \"avg_salary\": 75000,
            \"max_salary\": 120000,
            \"currency\": \"BDT\",
            \"growth_potential\": \"High\",
            \"skill_growth_tip\": \"Adding Docker and AWS will instantly boost your salary potential by 18%.\"
        }";

        try {
            $aiResponse = AiManagerService::ask($prompt, temperature: 0.3);
            $cleanJson = trim(str_replace(['```json', '```'], '', $aiResponse));
            $data = json_decode($cleanJson, true);

            if (!$data || !isset($data['min_salary'])) {
                throw new \Exception("Invalid structure");
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'AI salary prediction is temporarily unavailable. Please try again later.',
                'error' => $e->getMessage()
            ], 503);
        }

        Cache::put($cacheKey, $data, now()->addHours(24));

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * AI Career Roadmap Tree
     * Premium-only. Returns saved roadmap if available, otherwise generates and saves it.
     */
    public function careerRoadmap(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Please login to generate a roadmap.'], 401);
        }

        if ($user->getRoleNames()->first() === 'candidate' && !$user->hasFeature('ai_career_tools')) {
            return response()->json([
                'status' => false,
                'message' => 'Roadmap generation is available for Premium/Pro users only.',
                'feature_key' => 'ai_career_tools',
            ], 403);
        }

        $profile = $user->profile;
        $title = $profile->current_position ?? 'Professional';
        $skills = implode(', ', (array)($profile->skills ?? []));

        // If user already has a saved roadmap, reuse it unless they explicitly request regeneration
        $saved = CareerRoadmap::where('user_id', $user->id)->latest('id')->first();
        $shouldRegenerate = (bool) $request->boolean('regenerate');

        if (!$shouldRegenerate && $saved && $saved->data) {
            return response()->json(['status' => true, 'data' => $saved->data]);
        }

        $prompt = "Generate a professional tree-based Career Growth Roadmap for:
        Current Role: {$title}
        Current Skills: {$skills}
        Target: Senior Tech Lead / Architect
        
        Return ONLY valid JSON (no markdown, ONLY JSON):
        {
            \"current_level\": \"Professional\",
            \"target_role\": \"Senior Tech Lead / Architect\",
            \"roadmap\": [
                {
                    \"title\": \"Immediate Actions (1-3 months)\",
                    \"skills\": [\"Docker\", \"TypeScript\"],
                    \"certifications\": [\"AWS Certified Cloud Practitioner\"],
                    \"courses\": [\"Complete Cloud Architect Academy - Coursera\"],
                    \"description\": \"Focus on containers and modern typing systems.\"
                },
                {
                    \"title\": \"Mid-Level Transition (3-6 months)\",
                    \"skills\": [\"System Design\", \"Next.js\"],
                    \"certifications\": [\"Docker Professional\"],
                    \"courses\": [\"System Design Primer - GitHub\"],
                    \"description\": \"Focus on scalable APIs and frontend routers.\"
                },
                {
                    \"title\": \"Elite Tech Lead Status (6-12 months)\",
                    \"skills\": [\"Kubernetes\", \"CI/CD Pipelines\"],
                    \"certifications\": [\"Certified Kubernetes Administrator (CKA)\"],
                    \"courses\": [\"DevOps Bootcamp - Udemy\"],
                    \"description\": \"Focus on automated scaling and delivery grids.\"
                }
            ]
        }";

        try {
            $aiResponse = AiManagerService::ask($prompt, temperature: 0.5);
            $cleanJson = trim(str_replace(['```json', '```'], '', $aiResponse));
            $data = json_decode($cleanJson, true);

            if (!$data || !isset($data['roadmap'])) {
                throw new \Exception("Invalid roadmap");
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'AI career roadmap is temporarily unavailable. Please try again later.',
                'error' => $e->getMessage()
            ], 503);
        }

        // Save roadmap to database
        CareerRoadmap::create([
            'user_id' => $user->id,
            'data' => $data,
            'user_prompt' => $request->input('prompt'),
            'current_level' => $data['current_level'] ?? null,
            'target_role' => $data['target_role'] ?? null,
        ]);

        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * Start/Generate AI Mock Interview Questions
     */
    public function startInterview(Request $request)
    {
        $request->validate([
            'role' => 'required|string',
            'difficulty' => 'nullable|string|in:easy,medium,hard',
        ]);

        $user = auth('sanctum')->user();
        if ($user && $user->profile) {
            $profile = $user->profile;
            $title = $profile->current_position ?? $request->role;
            $skills = implode(', ', (array)($profile->skills ?? []));
        } else {
            $title = $request->role;
            $skills = 'PHP, JavaScript, React, Node.js';
        }

        $difficulty = $request->difficulty ?? 'medium';
        $prompt = "You are a lead interviewer at a top company. Generate exactly 5 challenging {$difficulty}-level interview questions for a '{$title}' role matching:
        Role: {$title}
        Skills: {$skills}
        Difficulty: {$difficulty}
        
        Return ONLY valid JSON (no markdown, ONLY JSON):
        [
            \"Question 1...\",
            \"Question 2...\",
            \"Question 3...\",
            \"Question 4...\",
            \"Question 5...\"
        ]";

        try {
            $aiResponse = AiManagerService::ask($prompt, temperature: 0.6);
            $cleanJson = trim(str_replace(['```json', '```'], '', $aiResponse));
            $questions = json_decode($cleanJson, true);

            if (!$questions || count($questions) < 5) {
                throw new \Exception("Invalid questions");
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'AI interview question generation is temporarily unavailable. Please try again later.',
                'error' => $e->getMessage()
            ], 503);
        }

        $sessionId = (string) \Illuminate\Support\Str::uuid();

        return response()->json([
            'status' => true,
            'session_id' => $sessionId,
            'questions' => $questions
        ]);
    }

    /**
     * Evaluate AI Mock Interview Responses transcript and log history
     */
    public function evaluateInterview(Request $request)
    {
        $request->validate([
            'answers' => 'required|array',
            'answers.*.question' => 'required|string',
            'answers.*.answer' => 'required|string',
        ]);

        $user = auth('sanctum')->user();
        $transcript = [];
        foreach ($request->answers as $a) {
            $transcript[] = [
                'question' => $a['question'],
                'answer' => $a['answer']
            ];
        }

        $prompt = "You are an Elite Tech Recruitment Auditor. Analyze this interview transcript:
        " . json_encode($transcript) . "
        
        Evaluate technical correctness, communication clarity, confidence level, and return evaluation scoring.
        Return ONLY valid JSON (no markdown, ONLY JSON):
        {
            \"ai_score\": 78,
            \"weak_areas\": [\"Database indexing and query optimizations\", \"CI/CD pipeline structures\"],
            \"practice_topics\": [\"Read STAR model answer systems\", \"Review docker port bindings documentation\"],
            \"communication_feedback\": \"Communication is articulate and professional, showing solid technical competence but needing deeper elaboration on deployment scaling details.\"
        }";

        try {
            $aiResponse = AiManagerService::ask($prompt, temperature: 0.3);
            $cleanJson = trim(str_replace(['```json', '```'], '', $aiResponse));
            $evaluation = json_decode($cleanJson, true);

            if (!$evaluation || !isset($evaluation['ai_score'])) {
                throw new \Exception("Invalid evaluation");
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'AI interview evaluation is temporarily unavailable. Please try again later.',
                'error' => $e->getMessage()
            ], 503);
        }

        $responseData = [
            'score' => $evaluation['ai_score'],
            'feedback' => $evaluation['communication_feedback'] ?? '',
            'strengths' => [],
            'weak_areas' => $evaluation['weak_areas'] ?? [],
            'improvements' => $evaluation['practice_topics'] ?? [],
        ];

        // Persist to database only if user is logged in
        if ($user) {
            $interview = CandidateInterview::create([
                'user_id' => $user->id,
                'interview_type' => $request->answers[0]['question'] ?? 'technical',
                'history' => $transcript,
                'ai_score' => $evaluation['ai_score'],
                'weak_areas' => $evaluation['weak_areas'] ?? [],
                'practice_topics' => $evaluation['practice_topics'] ?? [],
                'communication_feedback' => $evaluation['communication_feedback'] ?? '',
            ]);
            $responseData['id'] = $interview->id;
        }

        return response()->json([
            'status' => true,
            'message' => 'Interview evaluation completed successfully!',
            'data' => $responseData
        ]);
    }
}
