<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class ResumeScoreController extends Controller
{
    /**
     * Score a resume by analyzing its CV profile
     */
    public function scoreResume(Request $request)
    {
        $user = Auth::user();

        // Check subscription/quota for resume scoring
        if (!$user->hasFeature('ai_resume_score')) {
            return response()->json([
                'status' => false,
                'message' => 'Resume scoring not available. Please upgrade your plan.',
                'action' => 'upgrade',
            ], 403);
        }

        // Check daily quota
        if ($this->quotaExceeded($user)) {
            return response()->json([
                'status' => false,
                'message' => 'Daily resume scoring quota exceeded. Please try again tomorrow.',
                'action' => 'upgrade',
            ], 403);
        }

        // Get the profile to score
        $profileData = $request->input('profile');
        if (!$profileData) {
            return response()->json([
                'status' => false,
                'message' => 'No profile data provided.',
            ], 400);
        }

        $result = $this->analyzeWithAI($profileData);

        // Use quota on success
        if ($result['status']) {
            $user->useFeatureQuota('ai_resume_score', 1);
        }

        return response()->json($result);
    }

    /**
     * Score a PDF resume by extracting text and analyzing it
     */
    public function scorePdf(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasFeature('ai_resume_score')) {
            return response()->json([
                'status' => false,
                'message' => 'Resume scoring not available. Please upgrade your plan.',
                'action' => 'upgrade',
            ], 403);
        }

        if ($this->quotaExceeded($user)) {
            return response()->json([
                'status' => false,
                'message' => 'Daily resume scoring quota exceeded. Please try again tomorrow.',
                'action' => 'upgrade',
            ], 403);
        }

        $request->validate([
            'resume' => 'required|file|mimes:pdf|max:20480',
        ]);

        try {
            $file = $request->file('resume');
            $parser = new Parser();
            $pdf = $parser->parseFile($file->getRealPath());
            $text = $pdf->getText();

            if (empty(trim($text))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Could not extract text from PDF. Please try a different file.',
                ], 422);
            }

            $apiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));
            if ($apiKey) {
                $result = $this->extractAndScoreWithAi($text);
            } else {
                $result = $this->scoreFromText($text);
            }

            if ($result['status']) {
                $user->useFeatureQuota('ai_resume_score', 1);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to process PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extract structured data from text using AI, then score it
     */
    private function extractAndScoreWithAi(string $text): array
    {
        $apiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));

        $extractPrompt = <<<PROMPT
Extract structured resume data from this text. Return ONLY valid JSON.

TEXT:
{$text}

Return JSON with this structure:
{
  "personal_info": { "full_name": "...", "email": "...", "phone": "...", "city": "...", "country": "...", "title": "..." },
  "summary": "...",
  "skills": ["skill1", "skill2"],
  "experiences": [{ "position": "...", "company": "...", "description": "...", "start_date": "...", "end_date": "..." }],
  "educations": [{ "institution": "...", "degree": "...", "field": "...", "year": "..." }]
}
PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a resume parser. Extract structured data from resume text. Return ONLY valid JSON.'],
                    ['role' => 'user', 'content' => $extractPrompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => 2000,
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content', '');
                $jsonStr = trim($content, '` \n');
                if (str_starts_with($jsonStr, 'json')) {
                    $jsonStr = substr($jsonStr, 4);
                }
                $profile = json_decode($jsonStr, true);
                if ($profile && isset($profile['personal_info'])) {
                    return $this->analyzeWithAI($profile);
                }
            }
        } catch (\Throwable $e) {
            // Fall through to text-based scoring
        }

        return $this->scoreFromText($text);
    }

    /**
     * Score resume from raw text (rule-based, no AI extraction)
     */
    private function scoreFromText(string $text): array
    {
        $score = 0;
        $sections = [];
        $strengths = [];
        $weaknesses = [];
        $improvements = [];
        $lowerText = strtolower($text);

        // Contact completeness (0-10)
        $contactScore = 0;
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text)) $contactScore += 4;
        if (preg_match('/[\+]?[\d\-\(\)\s]{7,}/', $text)) $contactScore += 3;
        if (preg_match('/\b(linkedin|github|portfolio)\b/i', $text)) $contactScore += 3;
        $sections['contact_completeness'] = ['score' => $contactScore, 'feedback' => $contactScore >= 7 ? 'Contact information found in resume.' : 'Ensure your resume includes email, phone, and professional links.'];
        $score += $contactScore;

        // Professional summary (0-15)
        $summaryScore = 0;
        if (preg_match('/(summary|objective|profile|about)/i', $text)) $summaryScore += 5;
        $wordCount = str_word_count($text);
        if ($wordCount >= 300) $summaryScore += 5;
        elseif ($wordCount >= 150) $summaryScore += 3;
        if (preg_match('/\b(result|achiev|improv|deliver|exceed)\b/i', $lowerText)) $summaryScore += 5;
        $summaryScore = min($summaryScore, 15);
        $sections['professional_summary'] = ['score' => $summaryScore, 'feedback' => $summaryScore >= 10 ? 'Good summary section detected.' : 'Add a professional summary highlighting key achievements.'];
        $score += $summaryScore;

        // Work experience (0-20)
        $expScore = 0;
        if (preg_match_all('/(experience|employment|work history)/i', $text, $m)) $expScore += 5;
        if (preg_match('/\b(led|managed|developed|implemented|designed|improved|created|launched|optimized|delivered|spearheaded|architected)\b/i', $lowerText)) $expScore += 5;
        if (preg_match('/\d+\+?\s*(year|month|yr)/i', $text)) $expScore += 3;
        if (preg_match('/[%,+]\s*\d+|\d+\s*%/', $text)) $expScore += 4;
        if (preg_match_all('/(company|corporation|inc\.|ltd|llc)/i', $text, $m)) $expScore += min(count($m[0]) * 1, 3);
        $expScore = min($expScore, 20);
        $sections['work_experience'] = ['score' => $expScore, 'feedback' => $expScore >= 12 ? 'Good work experience section.' : 'Add detailed work experience with quantified achievements.'];
        $score += $expScore;

        // Education (0-10)
        $eduScore = 0;
        if (preg_match('/(education|academic|degree|university|college|bachelor|master|phd|b\.?s\.?|m\.?s\.?|mba)/i', $lowerText)) $eduScore += 5;
        if (preg_match('/\b(gpa|cgpa|honors|distinction|cum laude)\b/i', $lowerText)) $eduScore += 3;
        if (preg_match('/\b(20[0-2]\d)\b/', $text)) $eduScore += 2;
        $eduScore = min($eduScore, 10);
        $sections['education'] = ['score' => $eduScore, 'feedback' => $eduScore >= 6 ? 'Education section detected.' : 'Include your educational background with degree and institution details.'];
        $score += $eduScore;

        // Skills (0-15)
        $skillScore = 0;
        if (preg_match('/(skills|technologies|competencies|proficiencies)/i', $text)) $skillScore += 5;
        preg_match_all('/(?:^|\n)\s*[-•*]\s*(.+)/', $text, $listItems);
        $skillCount = count($listItems[1] ?? []);
        if ($skillCount >= 10) $skillScore += 7;
        elseif ($skillCount >= 5) $skillScore += 4;
        elseif ($skillCount >= 1) $skillScore += 2;
        if (preg_match('/\b/javascript|python|java|react|node|sql|html|css|php|typescript|angular|vue|django|spring|aws|docker|kubernetes|git\b/i', $lowerText)) $skillScore += 3;
        $skillScore = min($skillScore, 15);
        $sections['skills_relevance'] = ['score' => $skillScore, 'feedback' => $skillScore >= 10 ? 'Good skills section detected.' : 'Add a dedicated skills section with relevant technical and soft skills.'];
        $score += $skillScore;

        // ATS compatibility (0-10)
        $atsScore = 0;
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text)) $atsScore += 3;
        if (preg_match('/(experience|education|skills)/i', $text)) $atsScore += 3;
        if ($wordCount >= 200) $atsScore += 2;
        if (!preg_match('/\b(image|img|photo|picture)\b/i', $text)) $atsScore += 2;
        $atsScore = min($atsScore, 10);
        $sections['ats_compatibility'] = ['score' => $atsScore, 'feedback' => $atsScore >= 7 ? 'Good ATS compatibility.' : 'Use standard section headings and avoid images for better ATS parsing.'];
        $score += $atsScore;

        // Formatting (0-10)
        $formatScore = 0;
        if (preg_match('/(experience|education|skills|summary|contact)/i', $text)) $formatScore += 4;
        if (str_word_count($text) >= 200) $formatScore += 3;
        if (preg_match('/\n/', $text)) $formatScore += 1;
        if (!preg_match('/\s{5,}/', $text)) $formatScore += 2;
        $formatScore = min($formatScore, 10);
        $sections['formatting'] = ['score' => $formatScore, 'feedback' => $formatScore >= 7 ? 'Good resume formatting.' : 'Use consistent formatting with clear section headers and bullet points.'];
        $score += $formatScore;

        // Keywords (0-10)
        $keywordScore = 0;
        if (preg_match_all('/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*\b/', $text, $m)) $keywordScore += min(count(array_unique($m[0])) / 5, 5);
        if (preg_match('/(skills|technologies|tools|languages)/i', $text)) $keywordScore += 3;
        if (preg_match('/\b(team|leadership|communication|problem.solving|analytical|creative)\b/i', $lowerText)) $keywordScore += 2;
        $keywordScore = min($keywordScore, 10);
        $sections['keywords'] = ['score' => $keywordScore, 'feedback' => $keywordScore >= 6 ? 'Good keyword usage.' : 'Add industry-specific keywords and action verbs throughout your resume.'];
        $score += $keywordScore;

        // Achievement density (0-5)
        $achieveScore = 0;
        if (preg_match_all('/\d+\+?%|\$\d+|\d+\+?\s*(year|month|project|team|client|user|customer)/i', $text, $m)) $achieveScore += min(count($m[0]) * 1, 3);
        if (preg_match('/\b(increased|reduced|improved|saved|generated|grew|boosted|cut)\b/i', $lowerText)) $achieveScore += 2;
        $achieveScore = min($achieveScore, 5);
        $sections['achievement_density'] = ['score' => $achieveScore, 'feedback' => $achieveScore >= 3 ? 'Good use of quantified achievements.' : 'Add numbers, percentages, and metrics to demonstrate impact.'];
        $score += $achieveScore;

        // Language quality (0-5)
        $langScore = 0;
        if ($wordCount >= 300) $langScore += 2;
        elseif ($wordCount >= 150) $langScore += 1;
        if (preg_match('/\b(led|managed|developed|implemented|designed|improved|created|launched|optimized|delivered|spearheaded|architected|mentored|collaborated|streamlined)\b/i', $lowerText)) $langScore += 2;
        if (preg_match('/\b(proficient|experienced|skilled|certified|award|recognition)\b/i', $lowerText)) $langScore += 1;
        $langScore = min($langScore, 5);
        $sections['language_quality'] = ['score' => $langScore, 'feedback' => $langScore >= 3 ? 'Good professional language.' : 'Use strong action verbs and professional terminology throughout.'];
        $score += $langScore;

        // Grade
        $grade = match(true) {
            $score >= 90 => 'A+',
            $score >= 80 => 'A',
            $score >= 70 => 'B+',
            $score >= 60 => 'B',
            $score >= 50 => 'C+',
            $score >= 40 => 'C',
            default => 'D',
        };

        if ($contactScore >= 7) $strengths[] = 'Contact information is present';
        if ($summaryScore >= 10) $strengths[] = 'Professional summary detected';
        if ($expScore >= 12) $strengths[] = 'Work experience section with achievements';
        if ($skillScore >= 10) $strengths[] = 'Skills section with relevant keywords';

        if ($contactScore < 5) $weaknesses[] = 'Missing or incomplete contact information';
        if ($summaryScore < 5) $weaknesses[] = 'No professional summary found';
        if ($expScore < 8) $weaknesses[] = 'Limited work experience details';
        if ($skillScore < 5) $weaknesses[] = 'Insufficient skills listed';

        $improvements[] = 'Add quantified achievements with specific numbers and metrics';
        $improvements[] = 'Include industry-specific keywords for better ATS visibility';
        $improvements[] = 'Use a clean, professional format with clear section headers';

        return [
            'status' => true,
            'data' => [
                'overall_score' => min($score, 100),
                'grade' => $grade,
                'sections' => $sections,
                'strengths' => $strengths,
                'weaknesses' => $weaknesses,
                'improvements' => $improvements,
                'industry_benchmark' => 65,
            ],
        ];
    }

    /**
     * Analyze resume using AI
     */
    private function analyzeWithAI(array $profile): array
    {
        $apiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));

        if (!$apiKey) {
            // Fallback: rule-based scoring
            return $this->ruleBasedScore($profile);
        }

        $prompt = $this->buildScorePrompt($profile);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert resume analyzer. Score resumes on 25+ parameters and provide detailed feedback. Return ONLY valid JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => 2000,
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content', '');
                $jsonStr = trim($content, '` \n');
                if (str_starts_with($jsonStr, 'json')) {
                    $jsonStr = substr($jsonStr, 4);
                }
                $data = json_decode($jsonStr, true);
                if ($data && isset($data['overall_score'])) {
                    return ['status' => true, 'data' => $data];
                }
            }
        } catch (\Throwable $e) {
            // Fall through to rule-based scoring
        }

        return $this->ruleBasedScore($profile);
    }

    /**
     * Build AI scoring prompt
     */
    private function buildScorePrompt(array $profile): string
    {
        $personal = $profile['personal_info'] ?? [];
        $skills = $profile['skills'] ?? [];
        $experiences = $profile['experiences'] ?? [];
        $educations = $profile['educations'] ?? [];
        $summary = $profile['summary'] ?? '';
        $fullName = $personal['full_name'] ?? 'N/A';
        $title = $personal['title'] ?? 'N/A';

        return <<<PROMPT
Analyze this resume and score it on 25+ parameters.

PROFILE DATA:
Name: {$fullName}
Title: {$title}
Summary: {$summary}
Skills: {$this->safeJoin($skills)}
Experience Count: {$this->safeCount($experiences)}
Education Count: {$this->safeCount($educations)}

Return JSON with this structure:
{
  "overall_score": <0-100>,
  "grade": "<A+/A/B+/B/C+/C/D/F>",
  "sections": {
    "contact_completeness": { "score": <0-10>, "feedback": "..." },
    "professional_summary": { "score": <0-15>, "feedback": "..." },
    "work_experience": { "score": <0-20>, "feedback": "..." },
    "education": { "score": <0-10>, "feedback": "..." },
    "skills_relevance": { "score": <0-15>, "feedback": "..." },
    "ats_compatibility": { "score": <0-10>, "feedback": "..." },
    "formatting": { "score": <0-10>, "feedback": "..." },
    "keywords": { "score": <0-10>, "feedback": "..." },
    "achievement_density": { "score": <0-5>, "feedback": "..." },
    "language_quality": { "score": <0-5>, "feedback": "..." }
  },
  "strengths": ["..."],
  "weaknesses": ["..."],
  "improvements": ["..."],
  "industry_benchmark": <0-100>
}

Be specific and actionable in feedback. Score conservatively.
PROMPT;
    }

    /**
     * Rule-based scoring fallback
     */
    private function ruleBasedScore(array $profile): array
    {
        $score = 0;
        $sections = [];
        $strengths = [];
        $weaknesses = [];
        $improvements = [];

        $personal = $profile['personal_info'] ?? [];
        $skills = $profile['skills'] ?? [];
        $experiences = $profile['experiences'] ?? [];
        $educations = $profile['educations'] ?? [];
        $summary = $profile['summary'] ?? '';

        // Contact completeness (0-10)
        $contactScore = 0;
        if (!empty($personal['full_name'])) $contactScore += 2;
        if (!empty($personal['email'])) $contactScore += 2;
        if (!empty($personal['phone'])) $contactScore += 2;
        if (!empty($personal['city'])) $contactScore += 1;
        if (!empty($personal['country'])) $contactScore += 1;
        if (!empty($personal['title'])) $contactScore += 2;
        $sections['contact_completeness'] = ['score' => $contactScore, 'feedback' => $contactScore >= 8 ? 'Contact information is well complete.' : 'Add missing contact details for better reachability.'];
        $score += $contactScore;

        // Professional summary (0-15)
        $summaryScore = 0;
        if (!empty($summary)) {
            $wordCount = str_word_count($summary);
            if ($wordCount >= 30) $summaryScore += 10;
            elseif ($wordCount >= 15) $summaryScore += 6;
            else $summaryScore += 3;
            if (str_contains(strtolower($summary), 'result') || str_contains(strtolower($summary), 'achiev')) $summaryScore += 3;
            if (str_contains(strtolower($summary), 'year')) $summaryScore += 2;
        }
        $sections['professional_summary'] = ['score' => $summaryScore, 'feedback' => $summaryScore >= 12 ? 'Strong professional summary.' : 'Write a 2-3 sentence summary highlighting your key achievements.'];
        $score += $summaryScore;

        // Work experience (0-20)
        $expScore = 0;
        if (count($experiences) >= 3) $expScore += 10;
        elseif (count($experiences) >= 1) $expScore += 5;
        foreach ($experiences as $exp) {
            if (!empty($exp['description']) && strlen($exp['description']) > 50) $expScore += 2;
            if (!empty($exp['position'])) $expScore += 1;
            if (!empty($exp['company'])) $expScore += 1;
        }
        $expScore = min($expScore, 20);
        $sections['work_experience'] = ['score' => $expScore, 'feedback' => $expScore >= 15 ? 'Good work experience section.' : 'Add more detail to your work experience.'];
        $score += $expScore;

        // Education (0-10)
        $eduScore = 0;
        if (count($educations) >= 2) $eduScore += 8;
        elseif (count($educations) >= 1) $eduScore += 4;
        foreach ($educations as $edu) {
            if (!empty($edu['institution'])) $eduScore += 1;
            if (!empty($edu['degree'])) $eduScore += 1;
        }
        $eduScore = min($eduScore, 10);
        $sections['education'] = ['score' => $eduScore, 'feedback' => $eduScore >= 8 ? 'Education section is well documented.' : 'Add your educational background.'];
        $score += $eduScore;

        // Skills (0-15)
        $skillScore = 0;
        if (count($skills) >= 10) $skillScore += 12;
        elseif (count($skills) >= 5) $skillScore += 8;
        elseif (count($skills) >= 1) $skillScore += 4;
        if (count($skills) > 0) $skillScore += min(count($skills), 3);
        $skillScore = min($skillScore, 15);
        $sections['skills_relevance'] = ['score' => $skillScore, 'feedback' => $skillScore >= 12 ? 'Strong skill set.' : 'Add more relevant skills to your profile.'];
        $score += $skillScore;

        // ATS compatibility (0-10)
        $atsScore = 5;
        if (!empty($personal['full_name'])) $atsScore += 2;
        if (!empty($personal['email'])) $atsScore += 2;
        if (count($skills) >= 3) $atsScore += 1;
        $atsScore = min($atsScore, 10);
        $sections['ats_compatibility'] = ['score' => $atsScore, 'feedback' => $atsScore >= 8 ? 'Good ATS compatibility.' : 'Optimize for ATS with relevant keywords.'];
        $score += $atsScore;

        // Formatting (0-10) - based on completeness of profile fields
        $formatScore = 0;
        if (!empty($personal['full_name'])) $formatScore += 2;
        if (!empty($personal['email'])) $formatScore += 1;
        if (!empty($personal['phone'])) $formatScore += 1;
        if (!empty($personal['location'])) $formatScore += 1;
        if (count($experiences) >= 1) $formatScore += 2;
        if (count($educations) >= 1) $formatScore += 1;
        if (!empty($personal['summary'])) $formatScore += 2;
        $formatScore = min($formatScore, 10);
        $sections['formatting'] = ['score' => $formatScore, 'feedback' => $formatScore >= 7 ? 'Good profile completeness.' : 'Add more profile sections (experience, education, summary) for better formatting.'];
        $score += $formatScore;

        // Keywords (0-10)
        $keywordScore = min(count($skills) * 2, 10);
        $sections['keywords'] = ['score' => $keywordScore, 'feedback' => $keywordScore >= 8 ? 'Good keyword density.' : 'Add industry-specific keywords to improve visibility.'];
        $score += $keywordScore;

        // Achievement density (0-5)
        $achieveScore = 3;
        foreach ($experiences as $exp) {
            if (!empty($exp['description']) && (str_contains($exp['description'], '%') || str_contains($exp['description'], '+') || preg_match('/\d+/', $exp['description']))) {
                $achieveScore = 5;
                break;
            }
        }
        $sections['achievement_density'] = ['score' => $achieveScore, 'feedback' => $achieveScore >= 5 ? 'Good use of quantified achievements.' : 'Add numbers and metrics to your achievements.'];
        $score += $achieveScore;

        // Language quality (0-5) - based on bio/summary length and experience descriptions
        $langScore = 1; // base score
        $summaryLen = strlen($personal['summary'] ?? '');
        if ($summaryLen > 50) $langScore += 1;
        if ($summaryLen > 150) $langScore += 1;
        $hasActionVerbs = false;
        foreach ($experiences as $exp) {
            $desc = strtolower($exp['description'] ?? '');
            if (preg_match('/\b(led|managed|developed|implemented|designed|improved|created|launched|optimized|delivered|spearheaded|architected)\b/', $desc)) {
                $hasActionVerbs = true;
                break;
            }
        }
        if ($hasActionVerbs) $langScore += 1;
        if ($summaryLen > 100 && $hasActionVerbs) $langScore += 1;
        $langScore = min($langScore, 5);
        $sections['language_quality'] = ['score' => $langScore, 'feedback' => $langScore >= 4 ? 'Good use of professional language.' : 'Use action verbs and expand your summary for better language quality.'];
        $score += $langScore;

        // Determine grade
        $grade = match(true) {
            $score >= 90 => 'A+',
            $score >= 80 => 'A',
            $score >= 70 => 'B+',
            $score >= 60 => 'B',
            $score >= 50 => 'C+',
            $score >= 40 => 'C',
            default => 'D',
        };

        // Strengths & weaknesses
        if ($contactScore >= 8) $strengths[] = 'Complete contact information';
        if ($summaryScore >= 10) $strengths[] = 'Strong professional summary';
        if ($expScore >= 15) $strengths[] = 'Extensive work experience';
        if ($skillScore >= 12) $strengths[] = 'Diverse skill set';

        if ($contactScore < 5) $weaknesses[] = 'Missing contact details';
        if ($summaryScore < 6) $weaknesses[] = 'Weak or missing professional summary';
        if ($expScore < 10) $weaknesses[] = 'Limited work experience details';
        if ($skillScore < 8) $weaknesses[] = 'Insufficient skills listed';

        $improvements[] = 'Add quantified achievements (%, numbers, metrics)';
        $improvements[] = 'Use industry-specific keywords';
        $improvements[] = 'Ensure consistent formatting with a professional template';

        return [
            'status' => true,
            'data' => [
                'overall_score' => min($score, 100),
                'grade' => $grade,
                'sections' => $sections,
                'strengths' => $strengths,
                'weaknesses' => $weaknesses,
                'improvements' => $improvements,
                'industry_benchmark' => 65,
            ],
        ];
    }

    private function safeJoin(array $arr): string
    {
        return implode(', ', array_slice($arr, 0, 20));
    }

    private function safeCount(array $arr): int
    {
        return count($arr);
    }

    private function quotaExceeded(User $user): bool
    {
        // Check daily quota
        $dailyLimit = (int) (Setting::where('key', 'resume_score_daily_limit')->first()->value ?? 3);
        $todayCount = \App\Models\WalletTransaction::where('user_id', $user->id)
            ->where('reference_type', 'resume_score')
            ->whereDate('created_at', today())
            ->count();
        return $todayCount >= $dailyLimit;
    }
}
