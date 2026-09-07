<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 12);
        $cacheKey = 'companies_' . md5(serialize([$search, $page, $perPage]));

        $companies = Cache::remember($cacheKey, 1800, function () use ($search, $perPage) {
            $query = Company::where('is_verified', true)
                ->withCount(['jobs' => fn($q) => $q->where('is_active', true)])
                ->withCount(['reviews' => fn($q) => $q->where('status', 'approved')]);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('industry', 'like', '%' . $search . '%');
                });
            }

            return $query->orderBy('jobs_count', 'desc')->paginate($perPage);
        });

        return response()->json(['status' => true, 'data' => $companies]);
    }

    public function featured()
    {
        $companies = Company::where('is_featured', true)
            ->where('is_verified', true)
            ->where('rating', '>', 0)
            ->withCount(['jobs' => fn($q) => $q->where('is_active', true)])
            ->take(8)
            ->get();

        return response()->json(['status' => true, 'data' => $companies]);
    }

    public function publicProfile($slug)
    {
        $company = Company::where('slug', $slug)
            ->withCount(['jobs' => fn($q) => $q->where('is_active', true)])
            ->with(['jobs' => fn($q) => $q->where('is_active', true)->with('category')->latest()->limit(5), 'user'])
            ->first();

        if (!$company && is_numeric($slug)) {
            $company = Company::where('id', $slug)
                ->withCount(['jobs' => fn($q) => $q->where('is_active', true)])
                ->with(['jobs' => fn($q) => $q->where('is_active', true)->with('category')->latest()->limit(5), 'user'])
                ->first();
        }

        abort_unless($company, 404);

        // Get badges from user
        $badges = $company->user ? $company->user->activeBadges() : [];

        // Return all fields
        $companyData = $company->toArray();
        $companyData['active_badges'] = $badges;

        // Fallback or ensure fields exist
        $companyData['trust_score'] = $company->trust_score ?? 100;
        $companyData['trust_explanations'] = $company->trust_explanations ?? [];
        $companyData['profile_strength_breakdown'] = $company->profile_strength_breakdown ?? [];
        $companyData['rating'] = $company->rating ?? 0.00;
        $companyData['reputation_status'] = $company->reputation_status ?? 'good';
        $companyData['is_featured'] = (bool)($company->is_featured ?? false);
        $companyData['profile_completion_percentage'] = $company->profile_completion_percentage ?? 0;

        // Compute locked automated badges milestones progress tracking for company
        $user = $company->user;
        $lockedBadges = [];
        if ($user) {
            $role = 'employer';
            $actualJobs = $company->completed_jobs_count ?? 0;
            $actualScore = $company->trust_score ?? 100;
            $actualRating = $company->rating ?? 0.0;
            $actualSpend = $company->total_spend ?? 0.0;
            $isVerified = (bool)($company->is_verified ?? false);

            $ownedBadgeIds = $user->badges()->pluck('badges.id')->toArray();

            $lockedBadges = \App\Models\Badge::where('is_active', true)
                ->where('is_automatic', true)
                ->where('is_hidden', false)
                ->whereIn('role', [$role, 'both'])
                ->whereNotIn('id', $ownedBadgeIds)
                ->get()
                ->map(function ($badge) use ($actualJobs, $actualScore, $actualRating, $actualSpend, $isVerified) {
                    $rules = $badge->rules;
                    $progressDetails = [];
                    $totalRules = 0;
                    $passedRules = 0;

                    // Detect rule structuring format: new array format vs old key-value
                    $isNewFormat = false;
                    if (is_array($rules) && count($rules) > 0) {
                        $first = reset($rules);
                        if (is_array($first) && isset($first['field'])) {
                            $isNewFormat = true;
                        }
                    }

                    if (!$isNewFormat) {
                        foreach (($rules ?? []) as $key => $targetVal) {
                            $totalRules++;
                            $currentVal = 0;
                            $passed = false;
                            if ($key === 'completed_jobs') {
                                $currentVal = $actualJobs;
                                $passed = $currentVal >= $targetVal;
                            } elseif ($key === 'trust_score') {
                                $currentVal = $actualScore;
                                $passed = $currentVal >= $targetVal;
                            } elseif ($key === 'rating') {
                                $currentVal = $actualRating;
                                $passed = $currentVal >= $targetVal;
                            } elseif ($key === 'spend') {
                                $currentVal = $actualSpend;
                                $passed = $currentVal >= $targetVal;
                            } elseif ($key === 'verified') {
                                $currentVal = $isVerified ? 1 : 0;
                                $targetVal = filter_var($targetVal, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                                $passed = $currentVal == $targetVal;
                            }
                            if ($passed) $passedRules++;
                            $progressDetails[] = [
                                'field' => $key,
                                'current' => $currentVal,
                                'target' => $targetVal,
                                'passed' => $passed
                            ];
                        }
                    } else {
                        foreach (($rules ?? []) as $rule) {
                            if (!isset($rule['field']) || !isset($rule['value'])) {
                                continue;
                            }
                            $totalRules++;
                            $field = $rule['field'];
                            $operator = $rule['operator'] ?? '>=';
                            $targetValue = $rule['value'];

                            $actualValue = 0;
                            if ($field === 'completed_jobs') {
                                $actualValue = $actualJobs;
                            } elseif ($field === 'trust_score') {
                                $actualValue = $actualScore;
                            } elseif ($field === 'rating') {
                                $actualValue = $actualRating;
                            } elseif ($field === 'spend') {
                                $actualValue = $actualSpend;
                            } elseif ($field === 'verified') {
                                $actualValue = $isVerified;
                                $targetValue = filter_var($targetValue, FILTER_VALIDATE_BOOLEAN);
                            }

                            $passed = false;
                            switch ($operator) {
                                case '>=': $passed = $actualValue >= $targetValue; break;
                                case '>': $passed = $actualValue > $targetValue; break;
                                case '<=': $passed = $actualValue <= $targetValue; break;
                                case '<': $passed = $actualValue < $targetValue; break;
                                case '=':
                                case '==': $passed = $actualValue == $targetValue; break;
                                case '!=':
                                case '<>': $passed = $actualValue != $targetValue; break;
                            }

                            if ($passed) $passedRules++;

                            $progressDetails[] = [
                                'field' => $field,
                                'operator' => $operator,
                                'current' => $actualValue,
                                'target' => $targetValue,
                                'passed' => $passed
                            ];
                        }
                    }

                    $percentage = $totalRules > 0 ? min(100, round(($passedRules / $totalRules) * 100)) : 0;
                    $progressText = '';
                    if (count($progressDetails) > 0) {
                        $primary = $progressDetails[0];
                        $fieldName = str_replace('_', ' ', $primary['field']);
                        $progressText = "{$primary['current']}/{$primary['target']} {$fieldName}";
                    }

                    return [
                        'id' => $badge->id,
                        'name' => $badge->name,
                        'badge_key' => $badge->badge_key,
                        'description' => $badge->description,
                        'color' => $badge->color,
                        'icon' => $badge->icon,
                        'icon_type' => $badge->icon_type,
                        'icon_url' => $badge->icon_path ? asset('storage/' . $badge->icon_path) : null,
                        'priority' => $badge->priority,
                        'rarity' => $badge->rarity,
                        'badge_type' => $badge->badge_type,
                        'progress_percentage' => $percentage,
                        'progress_text' => $progressText,
                        'progress_details' => $progressDetails,
                    ];
                })
                ->values()
                ->toArray();
        }
        $companyData['locked_badges'] = $lockedBadges;

        // Employer Follow & Review System
        $companyId = $company->id;
        $followersCount = \Illuminate\Support\Facades\Cache::remember("company_{$companyId}_followers_count", 3600, function () use ($company) {
            return $company->followers()->count();
        });

        $currentUser = \Illuminate\Support\Facades\Auth::guard('sanctum')->user();
        $isFollowing = false;
        if ($currentUser) {
            $isFollowing = $company->followers()->where('user_id', $currentUser->id)->exists();
        }

        $reviewsCount = \Illuminate\Support\Facades\Cache::remember("company_{$companyId}_reviews_count", 3600, function () use ($company) {
            return $company->reviews()->where('status', 'approved')->count();
        });

        $reviews = $company->reviews()
            ->where('status', 'approved')
            ->with('user.profile')
            ->paginate(5)
            ->through(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'is_anonymous' => (bool)$review->is_anonymous,
                    'created_at' => $review->created_at ? $review->created_at->diffForHumans() : null,
                    'user' => $review->is_anonymous ? [
                        'name' => 'Anonymous Candidate',
                        'avatar' => null
                    ] : [
                        'name' => $review->user->name ?? 'Candidate',
                        'avatar' => $review->user->profile->avatar ?? null
                    ]
                ];
            });

        $companyData['followers_count'] = $followersCount;
        $companyData['is_following'] = $isFollowing;
        $companyData['reviews_count'] = $reviewsCount;
        $companyData['reviews_list'] = $reviews;

        return response()->json(['status' => true, 'data' => $companyData]);
    }

    /**
     * Update Employer/Company Profile
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // ১. কোম্পানি না থাকলে অটো ক্রিয়েট হবে (সিস্টেম অ্যাডমিনের জন্য বেস্ট প্র্যাকটিস)
        $company = $user->company()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $request->company_name ?? $user->name . " Company",
                'slug' => Str::slug($request->company_name ?? $user->name . " Company") . '-' . rand(1000, 9999),
                'location' => 'Update Location',
            ]
        );

        // ২. ভ্যালিডেশন
        $request->validate([
            'company_name' => 'sometimes|nullable|string|max:255',
            'name' => 'sometimes|nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:20480',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:20480',
            'trade_license_number' => 'nullable|string|max:255',
            'trade_license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:20480',
            'nid_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:20480',
            'registration_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:20480',
            'website' => 'nullable|string|max:255',
            'founded_year' => 'nullable|string',
            'size' => 'nullable|string',
            'industry' => 'nullable|string',
            'description' => 'nullable|string',
            'city' => 'nullable|string',
            'location' => 'nullable|string',
            'tagline' => 'nullable|string|max:20480',
            'name_bn' => 'nullable|string|max:255',
            'company_type' => 'nullable|string|max:100',
            'business_registration_number' => 'nullable|string|max:255',
            'employee_count' => 'nullable|string',
            'contact_person_name' => 'nullable|string|max:255',
            'contact_person_designation' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'contact_alt_phone' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'head_office_address' => 'nullable|string',
            'address_country' => 'nullable|string|max:100',
            'address_division' => 'nullable|string|max:100',
            'address_district' => 'nullable|string|max:100',
            'address_postal_code' => 'nullable|string|max:20',
            'google_map_embed' => 'nullable|string',
            'mission' => 'nullable|string',
            'vision' => 'nullable|string',
            'values' => 'nullable|string',
            'services_products' => 'nullable|string',
            'working_culture' => 'nullable|string',
            'hr_manager_name' => 'nullable|string|max:255',
            'hr_contact_number' => 'nullable|string|max:255',
            'hr_email' => 'nullable|email|max:255',
            'recruitment_policy' => 'nullable|string',
            'hiring_process' => 'nullable|string',
            'tin_number' => 'nullable|string|max:50',
            'company_video_url' => 'nullable|string|max:20480',
            'youtube_channel' => 'nullable|string|max:20480',
            'instagram_profile' => 'nullable|string|max:20480',
            'facebook' => 'nullable|string|max:20480',
            'linkedin' => 'nullable|string|max:20480',
            'why_join_us' => 'nullable|string',
            'top_skills' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($request, $user, $company) {
                
                // ৩. ইউজার টেবিল আপডেট (Contact Person Name)
                if ($request->filled('name')) {
                    $user->update(['name' => $request->name]);
                }

                // ৪. কোম্পানি ডাটা প্রিপারেশন
                $companyData = $request->only([
                    'website', 'location', 'industry', 'description', 'city',
                    'founded_year', 'size', 'mission', 'vision', 'values',
                    'name_bn', 'tagline', 'company_type', 'business_registration_number',
                    'trade_license_number',
                    'employee_count', 'contact_person_name', 'contact_person_designation',
                    'contact_phone', 'contact_alt_phone', 'contact_email',
                    'head_office_address', 'address_country', 'address_division',
                    'address_district', 'address_postal_code', 'google_map_embed',
                    'services_products', 'working_culture',
                    'hr_manager_name', 'hr_contact_number', 'hr_email',
                    'recruitment_policy', 'hiring_process',
                    'allow_job_posting', 'job_posting_limit_monthly', 'featured_job_allowed',
                    'auto_approval', 'job_expiry_days', 'tin_number',
                    'company_video_url', 'youtube_channel', 'instagram_profile',
                    'email_notifications', 'sms_notifications', 'application_alerts', 'shortlist_alerts',
                    'facebook', 'linkedin',
                ]);

                // Cast string booleans to integers for MySQL
                foreach (['allow_job_posting', 'featured_job_allowed', 'auto_approval'] as $boolField) {
                    if (isset($companyData[$boolField])) {
                        $companyData[$boolField] = filter_var($companyData[$boolField], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                    }
                }

                if ($request->filled('company_name')) {
                    $companyData['name'] = $request->company_name;
                    // ইউনিক স্লাগ নিশ্চিত করতে আইডি যুক্ত করা হয়েছে
                    $companyData['slug'] = Str::slug($request->company_name) . '-' . $company->id;
                }

                // ৫. JSON ফিল্ড হ্যান্ডলিং (Highlights/Socials)
                if ($request->has('highlights')) {
                    $highlights = $request->input('highlights');
                    $companyData['highlights'] = is_string($highlights) ? json_decode($highlights, true) : $highlights;
                }

                if ($request->has('why_join_us')) {
                    $whyJoinUs = $request->input('why_join_us');
                    $companyData['why_join_us'] = is_string($whyJoinUs) ? json_decode($whyJoinUs, true) : $whyJoinUs;
                }

                if ($request->has('top_skills')) {
                    $topSkills = $request->input('top_skills');
                    $companyData['top_skills'] = is_string($topSkills) ? json_decode($topSkills, true) : $topSkills;
                }

                // ৬. লোগো আপলোড (পুরানো ফাইল ডিলিটসহ)
                if ($request->hasFile('logo')) {
                    if ($company->logo && Storage::disk('public')->exists($company->logo)) {
                        Storage::disk('public')->delete($company->logo);
                    }
                    try {
                        $request->files->set('logo', \App\Services\Media\ImageOptimizerService::convertToWebp($request->file('logo')));
                        $enableOpt = \App\Models\Setting::where('key', 'image_enable_optimization')->value('value') ?? '1';
                        if ($enableOpt === '1') {
                            $optimizer = resolve(\App\Services\Media\ImageOptimizerService::class);
                            $result = $optimizer->optimize($request->file('logo'), 'companies/logos');
                            if ($result['status'] === 'success' || $result['status'] === 'fallback') {
                                $companyData['logo'] = $result['original'];
                            } else {
                                $companyData['logo'] = $request->file('logo')->store('companies/logos', 'public');
                            }
                        } else {
                            $companyData['logo'] = $request->file('logo')->store('companies/logos', 'public');
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Logo optimization failed, storing original: ' . $e->getMessage());
                        $companyData['logo'] = $request->file('logo')->store('companies/logos', 'public');
                    }
                }

                // Cover photo upload
                if ($request->hasFile('cover_photo')) {
                    if ($company->cover_photo && Storage::disk('public')->exists($company->cover_photo)) {
                        Storage::disk('public')->delete($company->cover_photo);
                    }
                    try {
                        $coverFile = \App\Services\Media\ImageOptimizerService::convertToWebp($request->file('cover_photo'));
                        $companyData['cover_photo'] = $coverFile->store('companies/covers', 'public');
                    } catch (\Throwable $e) {
                        Log::warning('Cover photo optimization failed, storing original: ' . $e->getMessage());
                        $companyData['cover_photo'] = $request->file('cover_photo')->store('companies/covers', 'public');
                    }
                }

                // Trade license upload
                if ($request->hasFile('trade_license_document')) {
                    if ($company->trade_license_document && Storage::disk('public')->exists($company->trade_license_document)) {
                        Storage::disk('public')->delete($company->trade_license_document);
                    }
                    $companyData['trade_license_document'] = $request->file('trade_license_document')->store('companies/licenses', 'public');
                }

                // NID document upload
                if ($request->hasFile('nid_document')) {
                    if ($company->nid_document && Storage::disk('public')->exists($company->nid_document)) {
                        Storage::disk('public')->delete($company->nid_document);
                    }
                    $nidFile = $request->file('nid_document');
                    $nidExt = strtolower($nidFile->getClientOriginalExtension());
                    if (in_array($nidExt, ['jpg','jpeg','png','gif','webp'])) {
                        $nidFile = \App\Services\Media\ImageOptimizerService::convertToWebp($nidFile);
                    }
                    $companyData['nid_document'] = $nidFile->store('companies/verification', 'public');
                }

                // Registration certificate upload
                if ($request->hasFile('registration_certificate')) {
                    if ($company->registration_certificate && Storage::disk('public')->exists($company->registration_certificate)) {
                        Storage::disk('public')->delete($company->registration_certificate);
                    }
                    $regFile = $request->file('registration_certificate');
                    $regExt = strtolower($regFile->getClientOriginalExtension());
                    if (in_array($regExt, ['jpg','jpeg','png','gif','webp'])) {
                        $regFile = \App\Services\Media\ImageOptimizerService::convertToWebp($regFile);
                    }
                    $companyData['registration_certificate'] = $regFile->store('companies/verification', 'public');
                }

                $company->update($companyData);
            });

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully!',
                'data' => [
                    'company' => $company->fresh(),
                    'user' => $user->fresh()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Company profile update failed for user {$user->id}: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            $msg = config('app.debug') ? $e->getMessage() : 'Update failed. Please try again.';
            return response()->json([
                'status' => false,
                'message' => $msg,
                'debug' => config('app.debug') ? get_class($e) : null,
            ], 500);
        }
    }

    /**
     * Get the employer's full company profile for editing.
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json(['status' => true, 'data' => null]);
        }

        try { $company->load('hrTeam'); } catch (\Throwable $e) {}

        $stats = [
            'total_jobs' => 0,
            'active_jobs' => 0,
            'expired_jobs' => 0,
            'total_applications' => 0,
            'shortlisted' => 0,
        ];
        try {
            $stats = [
                'total_jobs' => $company->jobs()->count(),
                'active_jobs' => $company->jobs()->where('is_active', true)->count(),
                'expired_jobs' => $company->jobs()->where('is_active', false)->count(),
                'total_applications' => \App\Models\JobApplication::whereHas('job', fn($q) => $q->where('company_id', $company->id))->count(),
                'shortlisted' => \App\Models\JobApplication::whereHas('job', fn($q) => $q->where('company_id', $company->id))->where('status', 'shortlisted')->count(),
            ];
        } catch (\Throwable $e) {}

        // ── Company Overview related data (each wrapped so one failure doesn't break everything) ──
        $brochures = collect();
        try { $brochures = \App\Models\CompanyBrochure::where('company_id', $company->id)->latest()->get()->map(fn($b) => array_merge($b->toArray(), ['file_url' => asset('storage/' . $b->file_path)])); } catch (\Throwable $e) {}

        $awards = collect();
        try { $awards = \App\Models\CompanyAward::where('company_id', $company->id)->latest()->get(); } catch (\Throwable $e) {}

        $culturePhotos = collect();
        try { $culturePhotos = \App\Models\CompanyCulturePhoto::where('company_id', $company->id)->latest()->get()->map(fn($p) => array_merge($p->toArray(), ['file_url' => asset('storage/' . $p->file_path)])); } catch (\Throwable $e) {}

        $recentUpdates = collect();
        try {
            $recentUpdates = \App\Models\CompanyUpdate::where('company_id', $company->id)->latest()->limit(10)->get()
                ->map(fn($u) => ['id' => $u->id, 'title' => $u->content, 'time' => $u->created_at->diffForHumans()]);
        } catch (\Throwable $e) {}

        $activeJobs = collect();
        try {
            $activeJobs = \App\Models\Job::where('company_id', $company->id)->where('is_active', true)->latest()->get()
                ->map(fn($j) => [
                    'id' => $j->id, 'title' => $j->title,
                    'type' => $j->type ?? 'Full Time',
                    'mode' => $j->workplace_type ?? $j->job_type ?? 'On-site',
                    'department' => optional($j->category)->name ?? '',
                    'location' => $j->location ?? $company->location ?? '',
                    'experience' => $j->experience_level ?? '',
                    'salary' => $j->salary_range ?? 'Negotiable',
                    'posted' => $j->created_at->diffForHumans(),
                ]);
        } catch (\Throwable $e) {}

        $profileViews = $company->profile_views_count ?? 0;

        $followersCount = 0;
        try { $followersCount = \App\Models\CompanyFollow::where('company_id', $company->id)->count(); } catch (\Throwable $e) {}

        $reviewsCount = 0;
        $avgRating = 0;
        try {
            $reviewsCount = \App\Models\CompanyReview::where('company_id', $company->id)->where('status', 'approved')->count();
            $avgRating = \App\Models\CompanyReview::where('company_id', $company->id)->where('status', 'approved')->avg('rating') ?? 0;
        } catch (\Throwable $e) {}

        $similarCompanies = collect();
        try {
            $similarCompanies = \App\Models\Company::where('industry', $company->industry)
                ->where('id', '!=', $company->id)->where('is_verified', true)
                ->withCount(['jobs' => fn($q) => $q->where('is_active', true)])
                ->take(4)->get()
                ->map(fn($c) => ['name' => $c->name, 'type' => $c->industry ?? 'Company', 'rating' => number_format($c->rating ?? 0, 1), 'slug' => $c->slug]);
        } catch (\Throwable $e) {}

        return response()->json([
            'status' => true,
            'data' => [
                'company' => $company,
                'stats' => $stats,
                'user' => $user,
                'brochures' => $brochures,
                'awards' => $awards,
                'culture_photos' => $culturePhotos,
                'recent_updates' => $recentUpdates,
                'active_jobs' => $activeJobs,
                'profile_views' => $profileViews,
                'followers_count' => $followersCount,
                'reviews_count' => $reviewsCount,
                'avg_rating' => round($avgRating, 1),
                'similar_companies' => $similarCompanies,
            ],
        ]);
    }

    /**
     * Save HR team members for the company.
     */
    public function saveHrTeam(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json(['status' => false, 'message' => 'No company found'], 404);
        }

        $request->validate([
            'members' => 'required|array|max:20',
            'members.*.name' => 'required|string|max:255',
            'members.*.email' => 'nullable|email|max:255',
            'members.*.phone' => 'nullable|string|max:20',
            'members.*.designation' => 'nullable|string|in:admin,hr_manager,recruiter',
        ]);

        $company->hrTeam()->delete();

        foreach ($request->members as $member) {
            \App\Models\CompanyHr::create(array_merge($member, [
                'company_id' => $company->id,
            ]));
        }

        return response()->json(['status' => true, 'message' => 'HR team saved', 'data' => $company->hrTeam]);
    }

    /**
     * Candidates follow or unfollow a company
     */
    public function toggleFollow(Request $request, $id)
    {
        try {
            $user = $request->user();
            $company = Company::findOrFail($id);

            $follow = \App\Models\CompanyFollow::where('user_id', $user->id)
                ->where('company_id', $company->id)
                ->first();

            if ($follow) {
                $follow->delete();
                \Illuminate\Support\Facades\Cache::forget("company_{$company->id}_followers_count");
                
                return response()->json([
                    'status' => true,
                    'is_following' => false,
                    'followers_count' => $company->followers()->count(),
                    'message' => 'Unfollowed company successfully.'
                ]);
            }

            \App\Models\CompanyFollow::create([
                'user_id' => $user->id,
                'company_id' => $company->id
            ]);
            \Illuminate\Support\Facades\Cache::forget("company_{$company->id}_followers_count");

            return response()->json([
                'status' => true,
                'is_following' => true,
                'followers_count' => $company->followers()->count(),
                'message' => 'Followed company successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to toggle follow: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Candidates post a review on a company
     */
    public function storeReview(Request $request, $id)
    {
        try {
            $user = $request->user();
            $company = Company::findOrFail($id);

            // Rate Limit: Max 3 reviews per minute to prevent spam
            $limiterKey = 'reviews-rate-limit:' . $user->id;
            if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($limiterKey, 3)) {
                $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($limiterKey);
                return response()->json([
                    'status' => false,
                    'message' => "Too many attempts. Please try again in {$seconds} seconds."
                ], 429);
            }
            \Illuminate\Support\Facades\RateLimiter::hit($limiterKey, 60);

            $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'required|string|min:5|max:1000',
                'is_anonymous' => 'nullable|boolean'
            ]);

            // Update existing or create new review
            $review = \App\Models\CompanyReview::updateOrCreate(
                ['user_id' => $user->id, 'company_id' => $company->id],
                [
                    'rating' => $request->rating,
                    'comment' => $request->comment,
                    'is_anonymous' => filter_var($request->is_anonymous, FILTER_VALIDATE_BOOLEAN),
                    'status' => 'approved' // Automatically approve for instant listing
                ]
            );

            // Rating Aggregation & Denormalization
            $avgRating = \App\Models\CompanyReview::where('company_id', $company->id)
                ->where('status', 'approved')
                ->avg('rating') ?? 0.0;

            $company->rating = round($avgRating, 2);
            $company->save();

            // Clear cache
            \Illuminate\Support\Facades\Cache::forget("company_{$company->id}_reviews_count");

            return response()->json([
                'status' => true,
                'message' => 'Review submitted successfully!',
                'review' => $review,
                'company_rating' => $company->rating
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to save review: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get paginated list of users following this company
     */
    public function getFollowers($companyId, Request $request)
    {
        try {
            $company = Company::findOrFail($companyId);

            $followers = $company->followers()
                ->with(['profile:id,user_id,avatar,current_position,city'])
                ->paginate(min(50, max(1, intval($request->get('per_page', 20)))));

            $followers->getCollection()->transform(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                    'current_position' => $user->profile->current_position ?? null,
                    'city' => $user->profile->city ?? null,
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $followers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch followers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if the current user follows this company
     */
    public function checkFollowStatus($companyId, Request $request)
    {
        try {
            $company = Company::findOrFail($companyId);
            $user = $request->user();

            $isFollowing = \App\Models\CompanyFollow::where('user_id', $user->id)
                ->where('company_id', $company->id)
                ->exists();

            return response()->json([
                'status' => true,
                'data' => [
                    'is_following' => $isFollowing,
                    'followers_count' => $company->followers()->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to check follow status: ' . $e->getMessage()
            ], 500);
        }
    }
}