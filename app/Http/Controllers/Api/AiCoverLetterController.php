<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AiCoverLetterController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'job_title' => 'required|string',
            'company_name' => 'required|string',
            'job_description' => 'nullable|string',
            'candidate_name' => 'nullable|string',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        }

        $profile = $user->profile;
        $skills = $profile ? implode(', ', $profile->skills ?? []) : '';
        $experience = '';
        if ($profile && isset($profile->experience)) {
            foreach ($profile->experience as $exp) {
                $experience .= ($exp['job_title'] ?? '') . ' at ' . ($exp['company_name'] ?? '') . '. ';
            }
        }

        $prompt = "Write a professional cover letter for the following job application. Keep it concise (250-350 words), professional, and compelling.\n\n";
        $prompt .= "Candidate: " . ($request->candidate_name ?? $user->name) . "\n";
        $prompt .= "Skills: {$skills}\n";
        $prompt .= "Experience: {$experience}\n";
        $prompt .= "Position: {$request->job_title}\n";
        $prompt .= "Company: {$request->company_name}\n";
        if ($request->job_description) {
            $prompt .= "Job Description: {$request->job_description}\n";
        }
        $prompt .= "\nWrite the cover letter in a professional tone. Do not use placeholders like [Company Name]. Format with proper paragraphs.";

        try {
            $text = AiManagerService::ask($prompt, 0.7, 1500);

            if ($text) {
                return response()->json(['status' => true, 'cover_letter' => $text]);
            }
        } catch (\Exception $e) {
            Log::error("AI cover letter failed for user {$user->id}: " . $e->getMessage());
        }

        // Graceful fallback: generate a basic cover letter template
        $candidateName = $request->candidate_name ?? $user->name;
        $companyName = $request->company_name;
        $jobTitle = $request->job_title;

        $fallback = "Dear Hiring Manager,\n\n";
        $fallback .= "I am writing to express my strong interest in the {$jobTitle} position at {$companyName}. ";
        $fallback .= "With my background in {$skills}, I am confident in my ability to contribute effectively to your team.\n\n";

        if ($experience) {
            $fallback .= "My professional experience includes: {$experience}\n\n";
        }

        $fallback .= "I am excited about the opportunity to bring my skills and dedication to {$companyName}. ";
        $fallback .= "I would welcome the chance to discuss how my experience aligns with your needs.\n\n";
        $fallback .= "Thank you for considering my application. I look forward to hearing from you.\n\n";
        $fallback .= "Sincerely,\n{$candidateName}";

        return response()->json([
            'status' => true,
            'cover_letter' => $fallback,
            'fallback' => true,
        ]);
    }
}
