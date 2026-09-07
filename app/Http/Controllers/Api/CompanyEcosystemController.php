<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyUpdate;
use App\Models\CompanyUpdateReaction;
use App\Models\CompanyUpdateComment;
use App\Models\CompanyBrochure;
use App\Models\CompanyCulturePhoto;
use App\Models\CompanyAward;
use App\Models\ProfileView;
use App\Models\User;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Promotion;
use Carbon\Carbon;
use App\Models\UserProfile;
use App\Models\Setting;
use App\Models\UserBehaviorLog;
use App\Services\Ai\AiManagerService;
use App\Services\ProfileIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CompanyEcosystemController extends Controller
{
    // ==========================================
    // 1. CANDIDATE PROFILE STRENGTH & INTEL
    // ==========================================

    public function getProfileStrength()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        }

        $profile = $user->profile ?? UserProfile::firstOrCreate(['user_id' => $user->id]);

        // Get admin configured weights from Setting
        $weights = [
            'basic_info' => (int) (Setting::where('key', 'weight_basic_info')->value('value') ?? 10),
            'resume' => (int) (Setting::where('key', 'weight_resume')->value('value') ?? 15),
            'skills' => (int) (Setting::where('key', 'weight_skills')->value('value') ?? 10),
            'experience' => (int) (Setting::where('key', 'weight_experience')->value('value') ?? 15),
            'education' => (int) (Setting::where('key', 'weight_education')->value('value') ?? 10),
            'certifications' => (int) (Setting::where('key', 'weight_certifications')->value('value') ?? 10),
            'avatar' => (int) (Setting::where('key', 'weight_avatar')->value('value') ?? 10),
            'portfolio' => (int) (Setting::where('key', 'weight_portfolio')->value('value') ?? 10),
            'social_links' => (int) (Setting::where('key', 'weight_social_links')->value('value') ?? 5),
            'bio' => (int) (Setting::where('key', 'weight_bio')->value('value') ?? 5),
        ];

        // Total weights check
        $totalWeight = array_sum($weights);
        if ($totalWeight <= 0) $totalWeight = 100;

        // Dynamic check of completed items
        $hasBasicInfo = !empty($profile->phone) && !empty($profile->city);
        $hasResume = !empty($profile->resume_path);
        $hasSkills = (is_array($profile->skills) ? count($profile->skills) : count(json_decode($profile->skills, true) ?? [])) >= 3;
        $hasExperience = (is_array($profile->experience) ? count($profile->experience) : count(json_decode($profile->experience, true) ?? [])) > 0;
        $hasEducation = (is_array($profile->education) ? count($profile->education) : count(json_decode($profile->education, true) ?? [])) > 0;
        
        // Custom scans
        $skillsArr = is_array($profile->skills) ? $profile->skills : (json_decode($profile->skills, true) ?? []);
        $hasCertifications = false;
        foreach ($skillsArr as $sk) {
            $name = is_array($sk) ? ($sk['name'] ?? '') : $sk;
            if (stripos($name, 'cert') !== false || stripos($name, 'aws') !== false || stripos($name, 'docker') !== false || stripos($name, 'certified') !== false) {
                $hasCertifications = true;
            }
        }
        if (!$hasCertifications && $profile->education) {
            $eduArr = is_array($profile->education) ? $profile->education : (json_decode($profile->education, true) ?? []);
            foreach ($eduArr as $ed) {
                if (stripos($ed['degree'] ?? '', 'cert') !== false || stripos($ed['degree'] ?? '', 'diploma') !== false) {
                    $hasCertifications = true;
                }
            }
        }

        $hasAvatar = !empty($profile->avatar);
        $hasPortfolio = !empty($profile->portfolio_link) || !empty($profile->projects);
        $hasSocials = !empty($profile->social_links) && count((array)$profile->social_links) > 0;
        $hasBio = !empty($profile->bio) && strlen($profile->bio) > 10;

        // Completion logic
        $earned = 0;
        if ($hasBasicInfo) $earned += $weights['basic_info'];
        if ($hasResume) $earned += $weights['resume'];
        if ($hasSkills) $earned += $weights['skills'];
        if ($hasExperience) $earned += $weights['experience'];
        if ($hasEducation) $earned += $weights['education'];
        if ($hasCertifications) $earned += $weights['certifications'];
        if ($hasAvatar) $earned += $weights['avatar'];
        if ($hasPortfolio) $earned += $weights['portfolio'];
        if ($hasSocials) $earned += $weights['social_links'];
        if ($hasBio) $earned += $weights['bio'];

        $percentage = min(100, round(($earned / $totalWeight) * 100));

        // Save back
        $profile->update(['profile_completion_percentage' => $percentage]);

        // Status tiers
        $status = 'Beginner';
        if ($percentage >= 90) {
            $status = 'Elite';
        } elseif ($percentage >= 70) {
            $status = 'Excellent';
        } elseif ($percentage >= 50) {
            $status = 'Good';
        } elseif ($percentage >= 35) {
            $status = 'Growing';
        }

        // Suggestions
        $suggestions = [];
        if (!$hasResume) {
            $suggestions[] = [
                'text' => "Upload your professional resume PDF to unlock full profile matches.",
                'weight' => $weights['resume'],
                'type' => 'resume'
            ];
        }
        if (!$hasSkills) {
            $suggestions[] = [
                'text' => "Add at least 3 skills to enable AI job matches and recommendations.",
                'weight' => $weights['skills'],
                'type' => 'skills'
            ];
        }
        if (!$hasCertifications) {
            $suggestions[] = [
                'text' => "Add professional certifications (e.g. AWS, Docker) to increase visibility by 25%.",
                'weight' => $weights['certifications'],
                'type' => 'certifications'
            ];
        }
        if (!$hasPortfolio) {
            $suggestions[] = [
                'text' => "Upload portfolio or project showcase for 22% more recruiter visibility.",
                'weight' => $weights['portfolio'],
                'type' => 'portfolio'
            ];
        }
        if (!$hasAvatar) {
            $suggestions[] = [
                'text' => "Upload a professional profile photo for immediate trust gains.",
                'weight' => $weights['avatar'],
                'type' => 'avatar'
            ];
        }
        if (!$hasBio) {
            $suggestions[] = [
                'text' => "Complete your Bio/About Me with details of your experience.",
                'weight' => $weights['bio'],
                'type' => 'bio'
            ];
        }

        return response()->json([
            'status' => true,
            'data' => [
                'percentage' => $percentage,
                'status' => $status,
                'weights' => $weights,
                'breakdown' => [
                    'basic_info' => $hasBasicInfo,
                    'resume' => $hasResume,
                    'skills' => $hasSkills,
                    'experience' => $hasExperience,
                    'education' => $hasEducation,
                    'certifications' => $hasCertifications,
                    'avatar' => $hasAvatar,
                    'portfolio' => $hasPortfolio,
                    'social_links' => $hasSocials,
                    'bio' => $hasBio,
                ],
                'suggestions' => $suggestions
            ]
        ]);
    }

    public function getAiInsights()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        }

        $cacheKey = 'ai_profile_insights_' . $user->id;
        if (Cache::has($cacheKey)) {
            return response()->json(['status' => true, 'data' => Cache::get($cacheKey)]);
        }

        $profile = $user->profile;
        $skills = $profile ? implode(', ', (array)($profile->skills ?? [])) : 'PHP, JavaScript';
        $bio = $profile ? ($profile->bio ?? 'No bio completed yet') : 'No bio completed yet';
        $completedJobs = $profile ? ($profile->completed_jobs_count ?? 0) : 0;
        $trustScore = $profile ? ($profile->trust_score ?? 100) : 100;

        $prompt = "You are an AI Profile Analyzer. Provide deep profile intelligence insights for the candidate:
        Name: {$user->name}
        Skills: {$skills}
        Bio: {$bio}
        Completed Jobs: {$completedJobs}
        Trust Score: {$trustScore}
        
        Return ONLY valid JSON (no markdown, ONLY JSON):
        {
            \"visibility_score\": 82,
            \"market_competitiveness\": \"High\",
            \"visibility_text\": \"Your profile is more visible to Fintech and SaaS employers.\",
            \"match_rate_text\": \"Backend & API jobs match your profile at 85%.\",
            \"salary_insight\": \"Your expected salary is in line with current market average, with a +10% negotiation leverage.\",
            \"skill_gap_analysis\": [\"Docker is highly requested for backend engineers but missing in your profile\", \"AWS Cloud Practitioner certification is preferred by 42% of local employers\"],
            \"suggested_improvements\": [\"Add Docker to skills to boost matching probability\", \"List your GitHub link under social profiles\"]
        }";

        try {
            $aiResponse = AiManagerService::ask($prompt, temperature: 0.3);
            $cleanJson = trim(str_replace(['```json', '```'], '', $aiResponse));
            $data = json_decode($cleanJson, true);
            
            if (!$data || !isset($data['visibility_score'])) {
                throw new \Exception("Invalid JSON format");
            }
        } catch (\Exception $e) {
            $data = [
                'visibility_score' => 75,
                'market_competitiveness' => 'Strong',
                'visibility_text' => 'Your profile has high visibility in custom software development firms.',
                'match_rate_text' => 'Web Development roles match your profile at 80%.',
                'salary_insight' => 'Your salary requirements align perfectly with standard industry guidelines.',
                'skill_gap_analysis' => ['Docker and AWS are missing from your listed skill catalog.'],
                'suggested_improvements' => ['Add Docker containerizations skills', 'Upload a public portfolio link.']
            ];
        }

        Cache::put($cacheKey, $data, now()->addHours(12));

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function getProfileViews()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        }

        // Fetch all profile views log
        $views = ProfileView::where('candidate_id', $user->id)
            ->with(['employer.company'])
            ->latest()
            ->get();

        $impressions = $views->sum('view_count');
        $clicks = $views->sum('view_count');
        $searchAppearances = $views->count() * 2;

        $formattedViews = $views->map(function ($view) {
            $employerUser = $view->employer;
            $company = $employerUser ? $employerUser->company : null;

            if ($view->is_anonymous) {
                return [
                    'id' => $view->id,
                    'company_name' => 'Anonymous Recruiter',
                    'logo' => null,
                    'recruiter_role' => $view->recruiter_role ?? 'Recruitment Lead',
                    'industry' => $company ? $company->industry : 'Information Technology',
                    'timestamp' => $view->updated_at->diffForHumans(),
                    'view_count' => $view->view_count,
                    'is_anonymous' => true,
                ];
            }

            return [
                'id' => $view->id,
                'company_name' => $company ? $company->name : ($employerUser->name ?? 'Employer'),
                'logo' => $company ? $company->logo : null,
                'recruiter_role' => $view->recruiter_role ?? 'Hiring Manager',
                'industry' => $company ? $company->industry : 'Technology',
                'timestamp' => $view->updated_at->diffForHumans(),
                'view_count' => $view->view_count,
                'is_anonymous' => false,
            ];
        });

        // AI sectors insights
        $fintechCount = 0;
        foreach ($formattedViews as $v) {
            if (stripos($v['industry'] ?? '', 'fintech') !== false || stripos($v['industry'] ?? '', 'finance') !== false) {
                $fintechCount++;
            }
        }
        $aiVisitsText = $fintechCount > 0 
            ? "{$fintechCount} employers from the fintech sector viewed your profile this week."
            : "Tech recruiters are currently showing interest in your development profile.";

        return response()->json([
            'status' => true,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'search_appearances' => $searchAppearances,
            'views' => $formattedViews,
            'ai_insights' => $aiVisitsText
        ]);
    }

    public function logProfileView(Request $request)
    {
        $request->validate([
            'candidate_id' => 'required|exists:users,id',
        ]);

        $employer = Auth::user();
        if (!$employer) {
            return response()->json(['status' => false, 'message' => 'Employer not authenticated'], 401);
        }

        $candidateId = $request->candidate_id;

        // Avoid self visits
        if ($employer->id == $candidateId) {
            return response()->json(['status' => true, 'message' => 'Self view ignored']);
        }

        $role = $employer->getRoleNames()->first() ?? 'employer';
        $company = $employer->company;

        // Anonymous toggle checks
        $isAnonymous = $company ? (bool)($company->anonymous_browsing ?? false) : false;

        $view = ProfileView::where('candidate_id', $candidateId)
            ->where('employer_id', $employer->id)
            ->first();

        if ($view) {
            $view->increment('view_count');
            $view->update([
                'is_anonymous' => $isAnonymous,
                'recruiter_role' => $company ? 'Hiring Lead at ' . $company->name : 'Recruiter',
            ]);
        } else {
            ProfileView::create([
                'candidate_id' => $candidateId,
                'employer_id' => $employer->id,
                'recruiter_role' => $company ? 'Hiring Lead at ' . $company->name : 'Recruiter',
                'is_anonymous' => $isAnonymous,
                'view_count' => 1,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Profile visit logged successfully!'
        ]);
    }


    // ==========================================
    // 2. EMPLOYER COMPANY PROFILE SUB-SYSTEMS
    // ==========================================

    public function saveBenefits(Request $request)
    {
        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Company profile not found'], 404);
        }

        $request->validate([
            'benefits' => 'required|array',
            'culture_description' => 'nullable|string|max:1000',
            'perks' => 'nullable|array',
            'remote_flexibility' => 'nullable|string',
        ]);

        // Merge and update
        $company->update([
            'why_join_us' => [
                'benefits' => $request->benefits,
                'culture_description' => $request->culture_description,
                'perks' => $request->perks ?? [],
                'remote_flexibility' => $request->remote_flexibility ?? 'Flexible Hours'
            ]
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Why Join Us section updated successfully!',
            'data' => $company->why_join_us
        ]);
    }

    public function uploadBrochure(Request $request)
    {
        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Company profile not found'], 404);
        }

        $request->validate([
            'title' => 'required|string|max:150',
            'brochure' => 'required|file|mimes:pdf|max:20480',
        ]);

        try {
            if ($request->hasFile('brochure')) {
                $path = $request->file('brochure')->store('companies/brochures', 'public');
                $brochure = CompanyBrochure::create([
                    'company_id' => $company->id,
                    'title' => $request->title,
                    'file_path' => $path,
                    'download_count' => 0
                ]);

                return response()->json([
                    'status' => true,
                    'message' => 'Brochure uploaded successfully!',
                    'data' => $brochure
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['status' => false, 'message' => 'No file uploaded'], 400);
    }

    public function getBrochures($companyId)
    {
        $brochures = CompanyBrochure::where('company_id', $companyId)->latest()->get();
        return response()->json([
            'status' => true,
            'data' => $brochures->map(function ($b) {
                return array_merge($b->toArray(), [
                    'file_url' => asset('storage/' . $b->file_path)
                ]);
            })
        ]);
    }

    public function downloadBrochure($id)
    {
        $brochure = CompanyBrochure::findOrFail($id);
        $brochure->increment('download_count');

        // Check if local file exists, download or return
        $fullPath = storage_path('app/public/' . $brochure->file_path);
        if (file_exists($fullPath)) {
            return response()->download($fullPath, $brochure->title . '.pdf');
        }

        return response()->json([
            'status' => true,
            'message' => 'Brochure download tracked.',
            'url' => asset('storage/' . $brochure->file_path)
        ]);
    }

    public function uploadCulturePhoto(Request $request)
    {
        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Company profile not found'], 404);
        }

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:20480',
            'caption' => 'nullable|string|max:200',
        ]);

        try {
            if ($request->hasFile('photo')) {
                $request->files->set('photo', \App\Services\Media\ImageOptimizerService::convertToWebp($request->file('photo')));
                $enableOpt = \App\Models\Setting::where('key', 'image_enable_optimization')->value('value') ?? '1';
                if ($enableOpt === '1') {
                    $optimizer = resolve(\App\Services\Media\ImageOptimizerService::class);
                    $result = $optimizer->optimize($request->file('photo'), 'companies/culture');
                    if ($result['status'] === 'success' || $result['status'] === 'fallback') {
                        $path = $result['original'];
                    } else {
                        $path = $request->file('photo')->store('companies/culture', 'public');
                    }
                } else {
                    $path = $request->file('photo')->store('companies/culture', 'public');
                }
                
                $photo = CompanyCulturePhoto::create([
                    'company_id' => $company->id,
                    'file_path' => $path,
                    'caption' => $request->caption,
                ]);

                return response()->json([
                    'status' => true,
                    'message' => 'Culture photo uploaded successfully!',
                    'data' => array_merge($photo->toArray(), [
                        'file_url' => asset('storage/' . $path)
                    ])
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['status' => false, 'message' => 'Photo upload failed'], 400);
    }

    public function getCulturePhotos($companyId)
    {
        $photos = CompanyCulturePhoto::where('company_id', $companyId)->latest()->get();
        return response()->json([
            'status' => true,
            'data' => $photos->map(function ($p) {
                return array_merge($p->toArray(), [
                    'file_url' => asset('storage/' . $p->file_path)
                ]);
            })
        ]);
    }

    public function addAward(Request $request)
    {
        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Company profile not found'], 404);
        }

        $request->validate([
            'title' => 'required|string|max:150',
            'issuer' => 'required|string|max:150',
            'year' => 'required|integer|min:1900|max:2030',
            'description' => 'nullable|string|max:20480',
        ]);

        $award = CompanyAward::create([
            'company_id' => $company->id,
            'title' => $request->title,
            'issuer' => $request->issuer,
            'year' => $request->year,
            'description' => $request->description,
            'is_verified' => false // pending admin audits
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Award added successfully! Pending admin audit.',
            'data' => $award
        ]);
    }

    public function getAwards($companyId)
    {
        $awards = CompanyAward::where('company_id', $companyId)->latest()->get();
        return response()->json([
            'status' => true,
            'data' => $awards
        ]);
    }


    // ==========================================
    // 3. LINKEDIN-STYLE UPDATES SUBSYSTEM
    // ==========================================

    public function getUpdates(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $updates = CompanyUpdate::where('company_id', $request->company_id)
            ->with(['comments.user:id,name,avatar'])
            ->latest()
            ->get();

        $currentUser = Auth::guard('sanctum')->user();

        $formatted = $updates->map(function ($up) use ($currentUser) {
            $userLiked = false;
            if ($currentUser) {
                $userLiked = CompanyUpdateReaction::where('update_id', $up->id)
                    ->where('user_id', $currentUser->id)
                    ->exists();
            }

            return [
                'id' => $up->id,
                'content' => $up->content,
                'media_url' => $up->media_path ? asset('storage/' . $up->media_path) : null,
                'likes_count' => $up->likes_count,
                'comments_count' => $up->comments_count,
                'shares_count' => $up->shares_count,
                'created_at' => $up->created_at->diffForHumans(),
                'user_liked' => $userLiked,
                'comments' => $up->comments->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'comment' => $c->comment,
                        'user_name' => $c->user->name ?? 'Candidate',
                        'user_avatar' => $c->user->profile->avatar ?? null,
                        'created_at' => $c->created_at->diffForHumans()
                    ];
                })
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $formatted
        ]);
    }

    public function storeUpdate(Request $request)
    {
        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Company profile not found'], 404);
        }

        $request->validate([
            'content' => 'required|string|max:3000',
            'media' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:20480'
        ]);

        $mediaPath = null;
        if ($request->hasFile('media')) {
            $request->files->set('media', \App\Services\Media\ImageOptimizerService::convertToWebp($request->file('media')));
            $enableOpt = \App\Models\Setting::where('key', 'image_enable_optimization')->value('value') ?? '1';
            if ($enableOpt === '1') {
                $optimizer = resolve(\App\Services\Media\ImageOptimizerService::class);
                $result = $optimizer->optimize($request->file('media'), 'companies/updates');
                if ($result['status'] === 'success' || $result['status'] === 'fallback') {
                    $mediaPath = $result['original'];
                } else {
                    $mediaPath = $request->file('media')->store('companies/updates', 'public');
                }
            } else {
                $mediaPath = $request->file('media')->store('companies/updates', 'public');
            }
        }

        $update = CompanyUpdate::create([
            'company_id' => $company->id,
            'content' => $request->content,
            'media_path' => $mediaPath,
            'likes_count' => 0,
            'comments_count' => 0,
            'shares_count' => 0
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Hiring announcement posted successfully!',
            'data' => $update
        ]);
    }

    public function reactToUpdate($id, Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $update = CompanyUpdate::findOrFail($id);

        $existing = CompanyUpdateReaction::where('update_id', $id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $update->decrement('likes_count');
            $liked = false;
        } else {
            CompanyUpdateReaction::create([
                'update_id' => $id,
                'user_id' => $user->id,
                'reaction_type' => 'like'
            ]);
            $update->increment('likes_count');
            $liked = true;
        }

        return response()->json([
            'status' => true,
            'likes_count' => $update->likes_count,
            'user_liked' => $liked,
        ]);
    }

    public function commentOnUpdate($id, Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'comment' => 'required|string|max:1000'
        ]);

        $update = CompanyUpdate::findOrFail($id);

        $comment = CompanyUpdateComment::create([
            'update_id' => $id,
            'user_id' => $user->id,
            'comment' => $request->comment
        ]);

        $update->increment('comments_count');

        return response()->json([
            'status' => true,
            'message' => 'Comment posted!',
            'data' => [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'user_name' => $user->name,
                'user_avatar' => $user->profile->avatar ?? null,
                'created_at' => $comment->created_at->diffForHumans()
            ]
        ]);
    }


    // ==========================================
    // 4. ECOSYSTEM ANALYTICS REDESIGN
    // ==========================================

    public function getEmployerAnalytics()
    {
        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Company not found'], 404);
        }

        $jobIds = Job::where('company_id', $company->id)->pluck('id');

        $totalJobs = $jobIds->count();
        $activeJobs = Job::where('company_id', $company->id)->where('is_active', true)->count();
        $pausedJobs = Job::where('company_id', $company->id)->where('is_active', false)->count();
        $closedJobs = Job::where('company_id', $company->id)->where('project_status', '!=', 'open')->count();

        $applications = JobApplication::whereIn('job_id', $jobIds);
        $totalApplicants = (clone $applications)->count();
        $currentPeriodStart = now()->subDays(30);
        $prevPeriodStart = now()->subDays(60);
        $applicantsCurrent = (clone $applications)
            ->where('created_at', '>=', $currentPeriodStart)
            ->count();
        $applicantsPrev = (clone $applications)
            ->where('created_at', '>=', $prevPeriodStart)
            ->where('created_at', '<', $currentPeriodStart)
            ->count();
        $applicantsChange = $applicantsPrev > 0 ? round((($applicantsCurrent - $applicantsPrev) / $applicantsPrev) * 100) : 0;

        $currentViews = UserBehaviorLog::where('activity_type', 'job_view')
            ->whereIn('target_id', $jobIds)
            ->where('created_at', '>=', $currentPeriodStart)
            ->count();
        $totalViews = UserBehaviorLog::where('activity_type', 'job_view')
            ->whereIn('target_id', $jobIds)
            ->count();
        $viewsPrev = UserBehaviorLog::where('activity_type', 'job_view')
            ->whereIn('target_id', $jobIds)
            ->where('created_at', '>=', $prevPeriodStart)
            ->where('created_at', '<', $currentPeriodStart)
            ->count();
        $viewsChange = $viewsPrev > 0 ? round((($currentViews - $viewsPrev) / $viewsPrev) * 100) : 0;

        $jobsCurrent = Job::where('company_id', $company->id)
            ->where('created_at', '>=', $currentPeriodStart)
            ->count();
        $jobsPrev = Job::where('company_id', $company->id)
            ->where('created_at', '>=', $prevPeriodStart)
            ->where('created_at', '<', $currentPeriodStart)
            ->count();
        $jobsChange = $jobsPrev > 0 ? round((($jobsCurrent - $jobsPrev) / $jobsPrev) * 100) : 0;

        $hiredCount = Job::where('company_id', $company->id)
            ->whereIn('project_status', ['in_progress', 'completed'])
            ->count();
        $hireRate = $totalJobs > 0 ? round(($hiredCount / $totalJobs) * 100) : 0;
        $conversionRate = $totalApplicants > 0 ? round(($hiredCount / $totalApplicants) * 100) : 0;

        $totalResponded = (clone $applications)->whereIn('status', ['shortlisted', 'rejected', 'interview'])->count();
        $responseRate = $totalApplicants > 0 ? round(($totalResponded / $totalApplicants) * 100) : 0;

        $avgResponseTime = 'N/A';
        $respondedApps = (clone $applications)->whereIn('status', ['shortlisted', 'rejected', 'interview'])->get();
        if ($respondedApps->count() > 0) {
            $totalHours = $respondedApps->sum(fn($a) => max(0, $a->updated_at->diffInHours($a->created_at)));
            $avgH = round($totalHours / $respondedApps->count(), 1);
            $avgResponseTime = $avgH >= 24 ? round($avgH / 24, 1) . ' days' : $avgH . ' hours';
        }

        $pendingActions = (clone $applications)->where('status', 'pending')->count();
        $pendingActions = (clone $applications)->where('status', 'pending')->count();

        $budgetSpent = \App\Models\Promotion::where('user_id', $user->id)->sum('spent_amount');
        $budgetRemaining = \App\Models\Wallet::where('user_id', $user->id)->value('balance') ?? 0;

        // Top jobs by views (from behavior log)
        $topJobViews = UserBehaviorLog::where('activity_type', 'job_view')
            ->whereIn('target_id', $jobIds)
            ->select('target_id', DB::raw('COUNT(*) as views'))
            ->groupBy('target_id')
            ->orderByDesc('views')
            ->limit(5)
            ->get();

        $topJobs = [];
        foreach ($topJobViews as $item) {
            $job = Job::where('id', $item->target_id)
                ->withCount('applications')
                ->first();
            if ($job) {
                $topJobs[] = [
                    'title' => $job->title,
                    'views' => (int) $item->views,
                    'applicants' => (int) $job->applications_count,
                ];
            }
        }

        // Recent applicants
        $recentApplicants = JobApplication::whereIn('job_id', $jobIds)
            ->with(['user', 'job'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($app) => [
                'name' => $app->user->name ?? 'Unknown',
                'job' => $app->job->title ?? 'Job',
                'date' => $app->created_at?->format('M d, Y') ?? '',
                'status' => $app->status ?? 'pending',
            ]);

        // Monthly views (last 6 months)
        $monthlyViews = [];
        $monthlyApplicants = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
            $monthlyViews[] = UserBehaviorLog::where('activity_type', 'job_view')
                ->whereIn('target_id', $jobIds)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();
            $monthlyApplicants[] = JobApplication::whereIn('job_id', $jobIds)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();
        }

        return response()->json([
            'status' => true,
            'data' => [
                'total_jobs' => (int) $totalJobs,
                'active_jobs' => (int) $activeJobs,
                'total_applicants' => (int) $totalApplicants,
                'total_views' => (int) $totalViews,
                'views_change' => $viewsChange,
                'applicants_change' => $applicantsChange,
                'jobs_change' => $jobsChange,
                'response_rate' => $responseRate,
                'avg_response_time' => $avgResponseTime,
                'hire_rate' => $hireRate,
                'total_hires' => (int) $hiredCount,
                'conversion_rate' => $conversionRate,
                'budget_spent' => (float) $budgetSpent,
                'budget_remaining' => (float) $budgetRemaining,
                'monthly_views' => $monthlyViews,
                'monthly_applicants' => $monthlyApplicants,
                'job_status' => [
                    'active' => (int) $activeJobs,
                    'paused' => (int) $pausedJobs,
                    'closed' => (int) $closedJobs,
                ],
                'top_jobs' => $topJobs,
                'recent_applicants' => $recentApplicants,
                'pending_actions' => (int) $pendingActions,
            ]
        ]);
    }
}
