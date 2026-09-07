<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Promotion;
use App\Models\Setting;
use App\Services\Ai\AiAlgorithmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class JobController extends Controller
{
    /**
     * Search and Filter Jobs with Active Ad Impression Tracking
     */
    public function index(Request $request)
    {
        $filterParams = $request->only(['search', 'category_id', 'job_type', 'location', 'division', 'district', 'upazila', 'upozilla', 'per_page', 'page']);
        $cacheKey = 'jobs_' . md5(serialize($filterParams));

        $result = Cache::remember($cacheKey, 300, function () use ($request) {
            $adInjectionEnabled = filter_var(
                Setting::where('key', 'search_ad_injection_enabled')->value('value') ?? '1',
                FILTER_VALIDATE_BOOLEAN
            );

            $promotedJobs = collect();
            $promotedIds = [];

            if ($adInjectionEnabled) {
                $promotionsQuery = Promotion::where('status', 'active')
                    ->where('type', 'sponsored_job')
                    ->where('start_date', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                    })
                    ->with(['job.company', 'job.category']);

                if ($request->filled('category_id')) {
                    $promotionsQuery->whereHas('job', function($q) use ($request) {
                        $q->where('category_id', $request->category_id);
                    });
                }
                if ($request->filled('location')) {
                    $promotionsQuery->where('target_location', 'like', '%' . $request->location . '%');
                }

                $activePromotions = $promotionsQuery->get();
                $rankedPromotions = \App\Services\Ad\AdRankingService::rankPromotions($activePromotions, Auth::user());

                foreach ($rankedPromotions as $promo) {
                    if ($promo->job) {
                        $job = $promo->job;
                        $job->is_promoted = 1;
                        $job->rank_score = $promo->rank_score;
                        $promotedJobs->push($job);
                        $promotedIds[] = $promo->id;
                    }
                }
            }

            $query = Job::with(['company', 'category'])
                ->where('is_active', true)
                ->where('is_remote_project', false)
                ->whereNotIn('id', $promotedJobs->pluck('id'));

            if ($request->filled('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->search . '%')
                      ->orWhereHas('company', function($c) use ($request) {
                          $c->where('name', 'like', '%' . $request->search . '%');
                      });
                });
            }
            if ($request->filled('category_id')) $query->where('category_id', $request->category_id);
            if ($request->filled('job_type')) $query->where('job_type', $request->job_type);
            if ($request->filled('location')) $query->where('location', 'like', '%' . $request->location . '%');
            if ($request->filled('division')) $query->where('division', $request->division);
            if ($request->filled('district')) $query->where('district', $request->district);
            $upazila = $request->upazila ?: $request->upozilla; // backward compat
            if ($upazila) $query->where('upazila', $upazila);
            if ($request->filled('experience_level')) $query->where('experience_level', $request->experience_level);
            if ($request->filled('skills')) {
                $skills = is_array($request->skills) ? $request->skills : explode(',', $request->skills);
                foreach ($skills as $skill) {
                    $query->where('required_skills', 'like', '%' . trim($skill) . '%');
                }
            }
            if ($request->filled('salary_min')) $query->where('salary_min', '>=', $request->salary_min);
            if ($request->filled('salary_max')) $query->where('salary_max', '<=', $request->salary_max);

            $perPage = $request->input('per_page', 12);

            if ($request->filled('sort')) {
                $sort = $request->sort;
                if ($sort === 'salary_high') $query->orderByDesc('salary_max');
                elseif ($sort === 'salary_low') $query->orderBy('salary_min');
                elseif ($sort === 'oldest') $query->oldest();
                else $query->latest();
            } else {
                $query->latest();
            }

            $jobs = $query->paginate($perPage);

            if ($jobs->currentPage() === 1 && $promotedJobs->isNotEmpty()) {
                $merged = $promotedJobs->merge($jobs->items());
                $jobs->setCollection($merged);
            }

            return [
                'jobs' => $jobs,
                'promotedIds' => $promotedIds,
            ];
        });

        $promotedIds = $result['promotedIds'] ?? [];
        if (!empty($promotedIds)) {
            Promotion::whereIn('id', $promotedIds)->increment('impressions');
        }

        // Attach AI match scores for authenticated candidates
        $user = Auth::user();
        if ($user && $user->hasRole('candidate')) {
            $jobsCollection = $result['jobs']->getCollection();
            $matchedJobs = $jobsCollection->map(function ($job) use ($user) {
                try {
                    $matchResult = AiAlgorithmService::computeMatchScore($user, $job);
                    $job->match_score = $matchResult['total_score'] ?? 0;
                } catch (\Exception $e) {
                    $job->match_score = 0;
                }
                return $job;
            });
            $result['jobs']->setCollection($matchedJobs);
        }

        return response()->json([
            'status' => true,
            'data' => $result['jobs']
        ]);
    }

    /**
     * Search and Filter Remote Freelance Projects
     */
    public function remoteJobs(Request $request)
    {
        $query = Job::with(['company', 'category'])
            ->where('is_active', true)
            ->where('is_remote_project', true)
            ->select('jobs.*')
            ->selectRaw('(
                SELECT COUNT(*) FROM promotions 
                WHERE promotions.job_id = jobs.id 
                AND promotions.status = "active" 
                AND promotions.start_date <= NOW() 
                AND promotions.end_date >= NOW()
            ) as is_promoted');

        // 1. Search Filter
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // 2. Filter by Category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // 3. Filter by Budget Type (fixed / hourly)
        if ($request->filled('budget_type')) {
            $query->where('budget_type', $request->budget_type);
        }

        // 4. Filter by Budget limits
        if ($request->filled('budget_min')) {
            $query->where('budget', '>=', $request->budget_min);
        }
        if ($request->filled('budget_max')) {
            $query->where('budget', '<=', $request->budget_max);
        }

        // 5. Filter by Experience Level (entry, intermediate, expert)
        if ($request->filled('experience_level')) {
            $query->where('experience_level', $request->experience_level);
        }

        // 6. Filter by Project Duration
        if ($request->filled('project_duration')) {
            $query->where('project_duration', $request->project_duration);
        }

        // 7. Filter by Timezone
        if ($request->filled('timezone')) {
            $query->where('timezone', 'like', '%' . $request->timezone . '%');
        }

        // 8. Filter by Country Restriction
        if ($request->filled('country_restriction')) {
            $query->where('country_restriction', 'like', '%' . $request->country_restriction . '%');
        }

        // 9. Filter by Language Requirement
        if ($request->filled('language_requirement')) {
            $query->where('language_requirement', 'like', '%' . $request->language_requirement . '%');
        }

        // 10. Filter by Skills tag matches
        if ($request->filled('skills')) {
            $skills = is_array($request->skills) ? $request->skills : explode(',', $request->skills);
            foreach ($skills as $skill) {
                $query->where('required_skills', 'like', '%' . trim($skill) . '%');
            }
        }

        $jobs = $query->orderByDesc('is_promoted')
                      ->latest()
                      ->paginate(12);

        return response()->json([
            'status' => true,
            'data' => $jobs
        ]);
    }

    /**
     * Display the specified job details with Ad Click Analytics
     */
    public function show($id)
    {
        $job = Job::with(['company', 'category'])->find($id);
        
        if (!$job) {
            return response()->json(['status' => false, 'message' => 'Job not found'], 404);
        }

        // Check Promoted Status to track active click performance metrics
        $isPromoted = Promotion::where('job_id', $id)
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->exists();

        if ($isPromoted) {
            Promotion::where('job_id', $id)
                ->where('status', 'active')
                ->increment('clicks');
        }

        // Check if current user has already applied using Sanctum guard for SPA compatibility
        $hasApplied = false;
        if (Auth::guard('sanctum')->check()) {
            $hasApplied = JobApplication::where('job_id', $id)
                ->where('user_id', Auth::guard('sanctum')->id())
                ->exists();
        }

        // Calculate live database telemetry statistics
        $applicationsCount = JobApplication::where('job_id', $id)->count();
        $recentApplicationsCount = JobApplication::where('job_id', $id)
            ->where('created_at', '>=', now()->subDay())
            ->count();
        $viewsCount = \App\Models\UserBehaviorLog::where('activity_type', 'job_view')
            ->where('target_id', $id)
            ->count();

        // 7-day view popularity trend data
        $popularityTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $dateObj = now()->subDays($i);
            $dayName = $dateObj->format('D');
            $count = \App\Models\UserBehaviorLog::where('activity_type', 'job_view')
                ->where('target_id', $id)
                ->whereDate('created_at', $dateObj->toDateString())
                ->count();
            
            $popularityTrend[] = [
                'day' => $dayName,
                'views' => $count
            ];
        }

        return response()->json([
            'status' => true, 
            'data' => $job,
            'has_applied' => $hasApplied,
            'is_promoted' => $isPromoted,
            'applications_count' => $applicationsCount,
            'recent_applications_count' => $recentApplicationsCount,
            'views_count' => $viewsCount,
            'popularity_trend' => $popularityTrend
        ]);
    }

    /**
     * Handle job applications and project proposals for candidates
     */
    public function apply(Request $request, $id)
    {
        $user = Auth::user();
        $job = Job::findOrFail($id);

        if ($user->role !== 'candidate' && !$user->hasRole('candidate')) {
            return response()->json(['status' => false, 'message' => 'Only candidates can apply.'], 403);
        }

        if (JobApplication::where('job_id', $id)->where('user_id', $user->id)->exists()) {
            return response()->json(['status' => false, 'message' => 'You have already applied.'], 400);
        }

        $profile = $user->profile;

        // Job Application Fee (deducted from wallet)
        if (!$job->is_remote_project) {
            $feeAmountSetting = \App\Models\Setting::where('key', 'application_fee')->first();
            $feeAmount = $feeAmountSetting ? (float) $feeAmountSetting->value : 0.00;

            if ($feeAmount > 0) {
                $candidateWallet = \App\Models\Wallet::firstOrCreate(['user_id' => $user->id]);

                if ($candidateWallet->balance < $feeAmount) {
                    return response()->json([
                        'status' => false,
                        'message' => "Insufficient wallet balance to pay standard job application fee of {$feeAmount} BDT. Please add funds to your wallet."
                    ], 402); // Payment Required
                }

                try {
                    DB::transaction(function () use ($candidateWallet, $feeAmount, $job, $user) {
                        // 1. Debit Candidate
                        $candidateWallet->debit(
                            $feeAmount,
                            'job_application_fee',
                            $job->id,
                            'Standard application fee for job: ' . $job->title
                        );

                        // 2. Credit Admin
                        $admin = \App\Models\User::role('admin')->first() ?? \App\Models\User::first();
                        if ($admin) {
                            $adminWallet = \App\Models\Wallet::firstOrCreate(['user_id' => $admin->id]);
                            $adminWallet->credit(
                                $feeAmount,
                                'job_application_fee',
                                $job->id,
                                "Apply fee from user {$user->name} ({$user->id}) for job {$job->id}"
                            );
                        }
                    });
                } catch (\Exception $e) {
                    return response()->json(['status' => false, 'message' => 'Payment transaction failed: ' . $e->getMessage()], 500);
                }
            }
        }

        // Validate Proposal Data if it's a Freelance Remote Project
        if ($job->is_remote_project) {
            $request->validate([
                'cover_letter' => 'required|string|min:10',
                'delivery_days' => 'required|integer|min:1'
            ]);
        }

        $request->validate([
            'expected_salary' => 'nullable|numeric|min:0',
            'portfolio_link' => 'nullable|url|max:20480',
        ]);

        try {
            JobApplication::create([
                'job_id' => $id,
                'user_id' => $user->id,
                'resume_path' => $profile?->resume_path ?? '',
                'cover_letter' => $request->cover_letter,
                'delivery_days' => $request->delivery_days,
                'expected_salary' => $request->expected_salary,
                'portfolio_link' => $request->portfolio_link,
                'status' => 'pending'
            ]);
        } catch (\Exception $e) {
            Log::error("Job application failed for user {$user->id}, job {$id}: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to submit application. Please try again.'], 500);
        }

        // Send application confirmation email
        $brandName = Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'JobBazar');
        $dashboardUrl = config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/dashboard';
        try {
            Mail::to($user->email)->queue(new \App\Mail\GenericMail('emails.job_application_confirmation', [
                'brandName' => $brandName,
                'userName' => $user->name,
                'jobTitle' => $job->title,
                'companyName' => $job->company?->name ?? 'the company',
                'dashboardUrl' => $dashboardUrl,
            ], "{$brandName} — Application Submitted"));
        } catch (\Throwable $e) {
            Log::error("Application confirmation email failed for user {$user->id}: " . $e->getMessage());
        }

        // Send notification email to employer
        try {
            $employer = $job->company?->owner;
            if ($employer && $employer->email) {
                $employerDashboardUrl = config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/employer/applicants';
                Mail::to($employer->email)->queue(new \App\Mail\GenericMail('emails.new_application_for_employer', [
                    'brandName' => $brandName,
                    'employerName' => $employer->name,
                    'jobTitle' => $job->title,
                    'applicantName' => $user->name,
                    'dashboardUrl' => $employerDashboardUrl,
                ], "{$brandName} — New Application Received"));
            }
        } catch (\Throwable $e) {
            Log::error("Employer notification email failed for job {$job->id}: " . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => $job->is_remote_project ? 'Proposal submitted successfully!' : 'Application submitted successfully!'
        ]);
    }

    /**
     * Public job platform stats
     */
    public function stats()
    {
        $activeJobs = \App\Models\Job::where('is_active', true)->count();
        $remoteJobs = \App\Models\Job::where('is_active', true)->where('is_remote_project', true)->count();
        $companies = \App\Models\Company::where('is_verified', true)->count();
        $candidates = \App\Models\User::role('candidate')->count();
        $totalApplications = \App\Models\JobApplication::count();

        $avgSalary = \App\Models\Job::where('is_active', true)->whereNotNull('salary_min')->avg('salary_min');

        return response()->json([
            'status' => true,
            'data' => [
                'total_jobs' => $activeJobs,
                'remote_jobs' => $remoteJobs,
                'total_companies' => $companies,
                'total_candidates' => $candidates,
                'total_applications' => $totalApplications,
                'avg_salary' => $avgSalary ? round($avgSalary) : 0,
            ]
        ]);
    }

    /**
     * Hot Jobs — promoted jobs first, then featured company jobs, up to 20 + 20 remote
     */
    public function hotJobs()
    {
        $promotedJobs = collect();
        $promotedRemoteJobs = collect();

        // Get promoted/sponsored jobs
        $promotions = Promotion::where('status', 'active')
            ->where('type', 'sponsored_job')
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->with(['job.company', 'job.category'])
            ->get();

        foreach ($promotions as $promo) {
            if (!$promo->job) continue;
            $job = $promo->job;
            $job->is_promoted = 1;
            if ($job->is_remote_project) {
                $promotedRemoteJobs->push($job);
            } else {
                $promotedJobs->push($job);
            }
        }

        // Unique by ID
        $promotedIds = $promotedJobs->pluck('id')->unique();
        $promotedRemoteIds = $promotedRemoteJobs->pluck('id')->unique();

        // Fill remaining non-remote slots with jobs from featured companies, then popular
        $nonPromotedCount = max(0, 30 - $promotedJobs->count());
        $regularJobs = collect();
        if ($nonPromotedCount > 0) {
            $regularJobs = Job::with(['company', 'category'])
                ->where('is_active', true)
                ->where('is_remote_project', false)
                ->whereNotIn('id', $promotedIds)
                ->whereHas('company', function ($q) {
                    $q->where('is_featured', true);
                })
                ->latest()
                ->take($nonPromotedCount)
                ->get();
        }

        // If still not enough, get latest popular jobs
        $stillNeeded = max(0, 30 - $promotedJobs->count() - $regularJobs->count());
        $extraJobs = collect();
        if ($stillNeeded > 0) {
            $excludeIds = $promotedIds->merge($regularJobs->pluck('id'))->unique();
            $extraJobs = Job::with(['company', 'category'])
                ->where('is_active', true)
                ->where('is_remote_project', false)
                ->whereNotIn('id', $excludeIds)
                ->latest()
                ->take($stillNeeded)
                ->get();
        }

        // Fill remaining remote slots
        $remoteNeeded = max(0, 30 - $promotedRemoteJobs->count());
        $regularRemote = collect();
        if ($remoteNeeded > 0) {
            $regularRemote = Job::with(['company', 'category'])
                ->where('is_active', true)
                ->where('is_remote_project', true)
                ->whereNotIn('id', $promotedRemoteIds)
                ->latest()
                ->take($remoteNeeded)
                ->get();
        }

        return response()->json([
            'status' => true,
            'data' => [
                'hot_jobs' => $promotedJobs->merge($regularJobs)->merge($extraJobs)->take(30)->values(),
                'remote_jobs' => $promotedRemoteJobs->merge($regularRemote)->take(30)->values(),
            ],
        ]);
    }

    /**
     * Popular skills extracted from active jobs
     */
    public function popularSkills()
    {
        try {
            $allSkills = \App\Models\Job::where('is_active', true)
                ->whereNotNull('required_skills')
                ->pluck('required_skills')
                ->flatMap(function ($s) {
                    if (is_array($s)) return $s;
                    $decoded = json_decode($s, true);
                    return is_array($decoded) ? $decoded : array_map('trim', explode(',', (string) $s));
                })
                ->filter(fn($s) => !empty($s) && is_string($s))
                ->values()
                ->toArray();

            $counted = array_count_values($allSkills);
            arsort($counted);
            $skills = array_keys(array_slice($counted, 0, 20, true));

            return response()->json([
                'status' => true,
                'data' => array_values($skills)
            ]);
        } catch (\Exception $e) {
            Log::error("popularSkills failed: " . $e->getMessage());
            return response()->json(['status' => true, 'data' => []]);
        }
    }
}