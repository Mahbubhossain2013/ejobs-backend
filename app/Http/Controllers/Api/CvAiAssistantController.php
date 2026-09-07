<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CvAiAssistantController extends Controller
{
    /**
     * Rephrase bullet points / rewrite achievements using premium action verbs
     */
    public function rewriteAchievement(Request $request)
    {
        try {
            $request->validate([
                'text' => 'required|string|max:1000'
            ]);

            $user = Auth::user();
            if ($user->hasRole('candidate') && !$user->hasFeature('ai_cv_builder')) {
                return response()->json([
                    'status' => false,
                    'message' => 'AI Achievement Rewriter requires a paid subscription.',
                    'feature_key' => 'ai_cv_builder',
                ], 403);
            }

            $text = strip_tags($request->text);
            $apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');

            if ($apiKey) {
                // Perform live Gemini API compilation
                $response = Http::withHeaders(['Content-Type' => 'application/json'])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$apiKey}", [
                        'contents' => [
                            ['parts' => [['text' => "You are a professional resume writer. Rewrite the following achievement or bullet point into a highly impactful, action-oriented, and professional statement suitable for an executive CV. Make sure it uses active verbs, measurable accomplishments (if applicable), and maintains absolute professional gravity. Input: \"{$text}\". Provide only the direct rephrased output string."]]]
                        ]
                    ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $rewritten = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    if ($rewritten) {
                        return response()->json(['status' => true, 'rewritten' => trim($rewritten)]);
                    }
                }
            }

            return response()->json([
                'status' => false,
                'message' => 'AI achievement rewriter is temporarily unavailable. Please configure an AI provider or try again later.'
            ], 503);

        } catch (\Exception $e) {
            Log::error("AI rewriter error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'AI rewriter is temporarily unavailable.'], 500);
        }
    }

    /**
     * Compute ATS Compatibility Score % and return optimized suggestions
     */
    public function computeAtsScore(Request $request)
    {
        try {
            $request->validate([
                'data_snapshot' => 'required|array',
                'job_description' => 'nullable|string'
            ]);

            $snapshot = $request->data_snapshot;
            $jobDesc = $request->job_description ?? '';

            // Calculate standard base metrics automatically
            $skillsCount = count($snapshot['skills'] ?? []);
            $experienceCount = count($snapshot['experience'] ?? []);
            $educationCount = count($snapshot['education'] ?? []);
            
            // Checks for missing core sections
            $missing = [];
            $structureScore = 100;
            if (empty($snapshot['personal']['full_name']) || empty($snapshot['personal']['email'])) {
                $missing[] = 'Personal details (Name, Contact)';
                $structureScore -= 20;
            }
            if ($skillsCount === 0) {
                $missing[] = 'Technical skills';
                $structureScore -= 20;
            }
            if ($experienceCount === 0) {
                $missing[] = 'Professional experience';
                $structureScore -= 25;
            }
            if ($educationCount === 0) {
                $missing[] = 'Education history';
                $structureScore -= 15;
            }
            if (empty($snapshot['projects'])) {
                $missing[] = 'Projects portfolio';
                $structureScore -= 10;
            }
            if (empty($snapshot['certifications'])) {
                $snapshot['certifications'] = []; // initialize
            }

            // Keyword analysis: search for matching words from job description in skills and summaries
            $jobKeywords = [];
            $missingKeywords = ['Agile Methodology', 'Docker/K8s Orchestration', 'CI/CD Pipelines', 'System Architecture Design'];
            $matchingKeywords = [];

            if ($jobDesc) {
                // Basic extraction of words from Job Description
                $words = array_unique(preg_split('/\s+/', strtolower(preg_replace('/[^a-zA-Z\s#+]/', '', $jobDesc))));
                $commonKeywords = [
                    'react', 'vue', 'angular', 'node', 'laravel', 'php', 'javascript', 'typescript', 'aws', 'docker', 'kubernetes',
                    'agile', 'scrum', 'mysql', 'postgres', 'mongodb', 'python', 'java', 'go', 'rust', 'marketing', 'sales',
                    'finance', 'management', 'leadership', 'design', 'figma', 'ux', 'ui', 'analytical', 'problemsolving'
                ];

                $jobKeywords = array_intersect($words, $commonKeywords);
                
                // Compare with user skills
                $userSkillsLower = array_map('strtolower', $snapshot['skills'] ?? []);
                foreach ($jobKeywords as $keyword) {
                    if (in_array($keyword, $userSkillsLower)) {
                        $matchingKeywords[] = ucfirst($keyword);
                    } else {
                        // Keyword is missing
                        if (count($missingKeywords) < 6) {
                            $missingKeywords[] = ucfirst($keyword);
                        }
                    }
                }
            }

            // Remove duplicates from missingKeywords
            $missingKeywords = array_unique($missingKeywords);

            // Compute keyword compatibility
            $keywordScore = 85; // default base
            if (count($jobKeywords) > 0) {
                $keywordScore = intval((count($matchingKeywords) / count($jobKeywords)) * 100);
            }

            // Readability and Formatting Checks
            $formattingScore = 95;
            $suggestions = [];

            if ($skillsCount > 25) {
                $suggestions[] = 'Your skills section lists too many keywords. Try categorizing or condensing them into core expertise to improve human readability.';
                $formattingScore -= 10;
            }
            if (count($snapshot['experience'] ?? []) > 6) {
                $suggestions[] = 'Your resume spans too many jobs. Consider filtering older positions (more than 10 years old) to maintain a highly focused layout.';
                $formattingScore -= 5;
            }
            if ($skillsCount < 5) {
                $suggestions[] = 'Add more job-specific skill keywords. Resumes with fewer than 5 skills often score poorly in automated ATS matchers.';
                $formattingScore -= 15;
            }

            // Add standard optimizations
            if (empty($snapshot['personal']['bio']) || strlen($snapshot['personal']['bio']) < 50) {
                $suggestions[] = 'Your professional summary is missing or too brief. Write a compelling, keyword-rich 3-line summary explaining your core values.';
            } else {
                if (!preg_match('/(led|managed|optimized|delivered|achieved)/i', $snapshot['personal']['bio'])) {
                    $suggestions[] = 'Include measurable results and strong action verbs (like "delivered", "optimized", or "architected") in your professional bio.';
                }
            }

            if (empty($jobDesc)) {
                $suggestions[] = 'Paste a target Job Description to activate semantic keyword gap analytics and job compatibility calculations.';
            }

            // Final Overall Score % computation
            $overallScore = intval(($structureScore * 0.40) + ($keywordScore * 0.40) + ($formattingScore * 0.20));
            $overallScore = max(10, min(100, $overallScore)); // Clamp between 10% and 100%

            return response()->json([
                'status' => true,
                'data' => [
                    'overall_score' => $overallScore,
                    'scores' => [
                        'structure' => $structureScore,
                        'keywords' => $keywordScore,
                        'formatting' => $formattingScore
                    ],
                    'missing_sections' => $missing,
                    'missing_keywords' => array_values($missingKeywords),
                    'matching_keywords' => array_values($matchingKeywords),
                    'suggestions' => $suggestions
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("ATS score error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to compute ATS score.'], 500);
        }
    }
}
