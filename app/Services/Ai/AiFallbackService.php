<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Log;

class AiFallbackService
{
    /**
     * Provide direct rule-based recommendations & suggestion structures locally
     */
    public static function getMatchScoreFallback(array $candidateSkills, string $jobDescription): array
    {
        $matched = [];
        $missing = [];
        
        $jobDescWords = explode(' ', strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $jobDescription)));
        
        foreach ($candidateSkills as $skill) {
            $skillLower = strtolower(trim($skill));
            if (in_array($skillLower, $jobDescWords)) {
                $matched[] = $skill;
            } else {
                $missing[] = $skill;
            }
        }

        $totalSkills = count($candidateSkills);
        $score = $totalSkills > 0 ? (int) ((count($matched) / $totalSkills) * 100) : 50;
        
        // Base minimum of 30 if at least 1 skill matched
        if (count($matched) > 0 && $score < 30) {
            $score = 35;
        }

        return [
            'score' => $score,
            'matched_skills' => $matched,
            'missing_skills' => array_slice($missing, 0, 3),
            'advice' => 'Your core competency aligns perfectly with this vacancy. Acquiring conceptual understanding of the remaining skills will optimize your hiring potential.'
        ];
    }

    /**
     * Resilient local rule-based chat fallback responses
     */
    public static function chatbotFallback(string $message): string
    {
        $msg = strtolower($message);
        
        if (str_contains($msg, 'resume') || str_contains($msg, 'cv')) {
            return "To optimize your resume: \n1. Focus on action verbs (Achieved, Built, Managed).\n2. Quantify achievements (e.g., Increased performance by 30%).\n3. Match target job descriptions with relevant keywords like React, Laravel, or Docker.";
        }
        
        if (str_contains($msg, 'interview')) {
            return "Mock Interview Tip: Always structure your answers using the STAR method:\n- Situation: Describe the context.\n- Task: Define the problem or challenge.\n- Action: Detail the steps you executed.\n- Result: State the concrete metrics and outcome achieved.";
        }

        return "As your AI Career Coach, I suggest focusing on building robust projects with modern frameworks like Laravel and React, configuring CI/CD pipelines, and establishing a professional portfolio site.";
    }
}
