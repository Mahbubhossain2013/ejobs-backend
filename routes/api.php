<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\FinancialSettingController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\PublicProfileController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\CandidateController;
use App\Http\Controllers\Api\EmployerController;
use App\Http\Controllers\Api\AiFeatureController;
use App\Http\Controllers\Api\CvResumeController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\AiAssistantController;
use App\Http\Controllers\Api\CvTemplateController;
use App\Http\Controllers\Api\CvProfileController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SeoController;
use App\Http\Controllers\Api\CompanyEcosystemController;
use App\Http\Controllers\Api\CompanyReviewController;
use App\Http\Controllers\Api\AiCareerAssistantController;
use App\Http\Controllers\Api\ImageSettingsController;
use App\Http\Controllers\Api\ImageModerationController;
use App\Http\Controllers\Api\BillingProfileController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\WorkDiaryController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\CustomHireController;
use App\Http\Controllers\Api\MilestoneController;

// ==========================================
// 1. PUBLIC ROUTES (No Login Required)
// ==========================================

// --- Auth ---
Route::middleware('security_monitor')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/two-factor/verify-login', [AuthController::class, 'verifyTwoFactor'])->middleware('throttle:5,1');
    Route::get('/check-username', [AuthController::class, 'checkUsername'])->middleware('throttle:30,1');
    Route::post('/check-account-type', [AuthController::class, 'checkAccountType'])->middleware('throttle:20,1');
});

// --- Social Authentication (Google/Facebook) — needs session for OAuth state ---
Route::middleware('web')->group(function () {
    Route::get('/auth/{provider}/redirect', [\App\Http\Controllers\Api\SocialAuthController::class, 'redirect']);
    Route::get('/auth/{provider}/callback', [\App\Http\Controllers\Api\SocialAuthController::class, 'callback']);
});
Route::get('/auth/social-settings', [\App\Http\Controllers\Api\SocialAuthController::class, 'getSettings']);

// --- Public Data & Listings ---
Route::get('/cv/templates/public', [CvResumeController::class, 'getTemplates']);
Route::get('/candidate/cv/templates', [CvResumeController::class, 'getTemplates']);
Route::get('/candidate/cv/live-preview/{slug}', [CvResumeController::class, 'livePreview']);
Route::post('/candidate/cv/live-preview/{slug}', [CvResumeController::class, 'livePreview']);
Route::get('/cv/live-preview/{slug}', [CvResumeController::class, 'livePreview']);
Route::post('/cv/live-preview/{slug}', [CvResumeController::class, 'livePreview']);
Route::get('/settings/theme', [SettingController::class, 'getThemeSettings']);
Route::get('/settings/manifest', function () {
    $data = Cache::remember('pwa_manifest', 3600, function () {
        $keys = ['site_name', 'site_favicon', 'meta_description', 'primary_color'];
        $settings = \App\Models\Setting::whereIn('key', $keys)->pluck('value', 'key')->toArray();
        $siteName = $settings['site_name'] ?? 'JobPortal';
        $favicon = $settings['site_favicon'] ?? '/favicon.svg';
        $description = $settings['meta_description'] ?? 'Find your dream job or hire the best talent.';
        $themeColor = $settings['primary_color'] ?? '#2563eb';
        return [
            'short_name' => mb_substr($siteName, 0, 12),
            'name' => $siteName . ' - Job Board & Career Platform',
            'description' => $description,
            'icons' => [
                [
                    'src' => $favicon ?: '/favicon.svg',
                    'type' => 'image/svg+xml',
                    'sizes' => 'any',
                    'purpose' => 'any maskable',
                ],
            ],
            'start_url' => '/',
            'scope' => '/',
            'background_color' => '#ffffff',
            'theme_color' => $themeColor,
            'display' => 'standalone',
            'orientation' => 'portrait',
            'categories' => ['jobs', 'business', 'education'],
            'prefer_related_applications' => false,
        ];
    });
    return response()->json($data)->header('Cache-Control', 'public, max-age=3600');
});
Route::get('/settings/financial', [\App\Http\Controllers\Api\FinancialSettingController::class, 'getSettings']);
Route::get('/homepage/data', [\App\Http\Controllers\Api\HomepageController::class, 'index']);

Route::get('/settings/public', function () {
    $settings = Cache::remember('public_settings', 3600, function () {
        $keys = [
            'regular_job_apply_fee_enabled',
            'regular_job_apply_fee',
            'site_name',
            'site_logo',
            'footer_copyright_text',
            'footer_credit_enabled',
            'footer_credit_text',
            'footer_credit_url',
            'support_phone',
            'support_email',
            'support_website',
            'facebook_page',
            'facebook_messenger',
            'whatsapp_number',
            'telegram_channel',
            'youtube_channel',
        ];
        $settings = \App\Models\Setting::whereIn('key', $keys)->pluck('value', 'key')->toArray();
        foreach ($settings as $key => $value) {
            $decoded = json_decode($value, true);
            $settings[$key] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
        }
        return $settings;
    });
    return response()->json(['status' => true, 'data' => $settings]);
});
Route::get('/settings/homepage', function () {
    $parsed = Cache::remember('homepage_settings', 3600, function () {
        $keys = [
            'homepage_notices',
            'homepage_trending_searches',
            'homepage_trending_searches_bn',
            'homepage_features_grid',
            'homepage_offer_banner',
            'homepage_quick_links',
            'homepage_recruitment_callout',
            'homepage_hero_section',
            'homepage_ai_assistant',
            'homepage_newsletter',
            'homepage_categories',
            'homepage_hot_jobs',
            'homepage_remote_jobs',
        ];
        $settings = \App\Models\Setting::whereIn('key', $keys)->pluck('value', 'key')->toArray();
        $parsed = [];
        foreach ($settings as $key => $value) {
            $decoded = json_decode($value, true);
            $parsed[$key] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
        }
        return $parsed;
    });
    return response()->json(['status' => true, 'data' => $parsed]);
});
Route::get('/notices', [SettingController::class, 'getNotices']);
Route::get('/categories', function () {
    $categories = Cache::remember('active_categories_with_children', 3600, function () {
        return \App\Models\Category::where('is_active', true)
            ->whereNull('parent_id')
            ->withCount('jobs')
            ->with(['children' => function ($q) {
                $q->where('is_active', true)
                    ->withCount('jobs');
            }])
            ->orderBy('is_highlighted', 'desc')
            ->orderBy('name_en')
            ->get();
    });
    return response()->json(['status' => true, 'data' => $categories]);
});

Route::get('/categories/highlighted', function () {
    $categories = Cache::remember('highlighted_categories', 3600, function () {
        return \App\Models\Category::where('is_active', true)
            ->where('is_highlighted', true)
            ->whereNull('parent_id')
            ->withCount('jobs')
            ->with(['children' => function ($q) {
                $q->where('is_active', true)
                    ->withCount('jobs');
            }])
            ->orderBy('name_en')
            ->limit(6)
            ->get();
    });
    return response()->json(['status' => true, 'data' => $categories]);
});

Route::get('/categories/{id}', function ($id) {
    $category = \App\Models\Category::withCount(['jobs' => function ($q) {
        $q->where('is_active', true);
    }])->with(['children' => function ($q) {
        $q->withCount(['jobs' => function ($q2) {
            $q2->where('is_active', true);
        }]);
    }])->find($id);

    if (!$category) {
        return response()->json(['status' => false, 'message' => 'Category not found'], 404);
    }

    // Job stats for this category
    $catJobs = \App\Models\Job::where('category_id', $id)->where('is_active', true);
    $stats = [
        'total_jobs' => $category->jobs_count,
        'companies' => \App\Models\Company::whereHas('jobs', function ($q) use ($id) {
            $q->where('category_id', $id)->where('is_active', true);
        })->count(),
        'avg_salary' => 0,
        'remote_jobs' => $catJobs->clone()->where('is_remote_project', true)->count(),
    ];

    // Popular skills in this category
    $popularSkills = \App\Models\Job::where('category_id', $id)->where('is_active', true)
        ->pluck('required_skills')
        ->filter()
        ->flatMap(function ($s) { return is_array($s) ? $s : explode(',', $s); })
        ->map(fn($s) => trim($s))
        ->filter()
        ->countBy()
        ->sortDesc()
        ->take(12)
        ->keys()
        ->toArray();

    return response()->json([
        'status' => true,
        'data' => [
            'category' => $category,
            'stats' => $stats,
            'popular_skills' => $popularSkills,
        ],
    ]);
});
Route::get('/companies', [CompanyController::class, 'index']);
Route::get('/companies/featured', [CompanyController::class, 'featured']);
Route::get('/companies/{slug}', [CompanyController::class, 'publicProfile']);
Route::get('/companies/{companyId}/brochures', [CompanyEcosystemController::class, 'getBrochures']);
Route::get('/companies/{companyId}/culture-photos', [CompanyEcosystemController::class, 'getCulturePhotos']);
Route::get('/companies/{companyId}/awards', [CompanyEcosystemController::class, 'getAwards']);
Route::get('/companies/updates', [CompanyEcosystemController::class, 'getUpdates']);
Route::get('/companies/{companyId}/reviews', [CompanyReviewController::class, 'index']);

// --- CV Builder Public Previews & Demo (no auth required for builder wizard preview) ---
Route::get('/cv/templates/public', [\App\Http\Controllers\Api\CvTemplateController::class, 'publicTemplates']);
Route::get('/cv/demo/{slug}', [\App\Http\Controllers\Api\CvResumeController::class, 'previewDemo']);
Route::match(['get', 'post'], '/cv/live-preview/{slug}', [\App\Http\Controllers\Api\CvResumeController::class, 'livePreview']);
Route::match(['get', 'post'], '/candidate/cv/live-preview/{slug}', [\App\Http\Controllers\Api\CvResumeController::class, 'livePreview']);
Route::get('/jobs', [JobController::class, 'index']);
Route::get('/jobs/remote', [JobController::class, 'remoteJobs']);
Route::get('/jobs/stats', [JobController::class, 'stats']);
Route::get('/jobs/hot', [JobController::class, 'hotJobs']);
Route::get('/jobs/popular-skills', [JobController::class, 'popularSkills']);
Route::get('/jobs/{id}', [JobController::class, 'show']);

// --- Guest Job Posting (no auth required) ---
Route::get('/guest-jobs/availability', [\App\Http\Controllers\Api\GuestJobController::class, 'checkAvailability']);
Route::post('/guest-jobs', [\App\Http\Controllers\Api\GuestJobController::class, 'store']);

// --- Deployment Tracker (auth required) ---
Route::get('/deployments', [\App\Http\Controllers\Api\DeploymentController::class, 'index'])->middleware('auth:sanctum');
Route::get('/deployments/{id}', [\App\Http\Controllers\Api\DeploymentController::class, 'show'])->middleware('auth:sanctum');
Route::post('/deployments', [\App\Http\Controllers\Api\DeploymentController::class, 'store'])->middleware('auth:sanctum');
Route::put('/deployments/stages/{stageId}', [\App\Http\Controllers\Api\DeploymentController::class, 'updateStage'])->middleware('auth:sanctum');
Route::post('/deployments/{deploymentId}/stages', [\App\Http\Controllers\Api\DeploymentController::class, 'addStage'])->middleware('auth:sanctum');
Route::delete('/deployments/stages/{stageId}', [\App\Http\Controllers\Api\DeploymentController::class, 'deleteStage'])->middleware('auth:sanctum');

// --- Skill Center (AMCO Training Hub) ---
Route::get('/skills', [\App\Http\Controllers\Api\SkillCenterController::class, 'index']);
Route::get('/skills/categories', [\App\Http\Controllers\Api\SkillCenterController::class, 'categories']);
Route::get('/skills/{id}', [\App\Http\Controllers\Api\SkillCenterController::class, 'show']);
Route::post('/skills/{id}/enroll', [\App\Http\Controllers\Api\SkillCenterController::class, 'enroll'])->middleware('auth:sanctum');
Route::put('/skills/enrollments/{enrollmentId}/progress', [\App\Http\Controllers\Api\SkillCenterController::class, 'updateProgress'])->middleware('auth:sanctum');
Route::get('/skills/my-courses', [\App\Http\Controllers\Api\SkillCenterController::class, 'myCourses'])->middleware('auth:sanctum');

// --- Assessment System ---
Route::get('/assessments', [\App\Http\Controllers\Api\AssessmentController::class, 'index']);
Route::get('/assessments/{id}', [\App\Http\Controllers\Api\AssessmentController::class, 'show']);
Route::post('/assessments/{assessmentId}/start', [\App\Http\Controllers\Api\AssessmentController::class, 'start'])->middleware('auth:sanctum');
Route::post('/assessments/attempts/{attemptId}/answer', [\App\Http\Controllers\Api\AssessmentController::class, 'submitAnswer'])->middleware('auth:sanctum');
Route::post('/assessments/attempts/{attemptId}/submit', [\App\Http\Controllers\Api\AssessmentController::class, 'submitAll'])->middleware('auth:sanctum');
Route::get('/assessments/attempts/{attemptId}/result', [\App\Http\Controllers\Api\AssessmentController::class, 'getAttemptResult'])->middleware('auth:sanctum');
Route::get('/assessments/{assessmentId}/my-attempts', [\App\Http\Controllers\Api\AssessmentController::class, 'myAttempts'])->middleware('auth:sanctum');
Route::post('/assessments/{assessmentId}/questions', [\App\Http\Controllers\Api\AssessmentController::class, 'storeQuestion'])->middleware('auth:sanctum');
Route::put('/assessments/questions/{questionId}', [\App\Http\Controllers\Api\AssessmentController::class, 'updateQuestion'])->middleware('auth:sanctum');
Route::delete('/assessments/questions/{questionId}', [\App\Http\Controllers\Api\AssessmentController::class, 'deleteQuestion'])->middleware('auth:sanctum');

// --- Certificate System ---
Route::get('/certificates/my', [\App\Http\Controllers\Api\CertificateController::class, 'myCertificates'])->middleware('auth:sanctum');
Route::post('/certificates/generate', [\App\Http\Controllers\Api\CertificateController::class, 'generate'])->middleware('auth:sanctum');
Route::get('/certificates/{id}', [\App\Http\Controllers\Api\CertificateController::class, 'show'])->middleware('auth:sanctum');
Route::get('/certificates/{id}/download', [\App\Http\Controllers\Api\CertificateController::class, 'download'])->middleware('auth:sanctum');
Route::delete('/certificates/{id}', [\App\Http\Controllers\Api\CertificateController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/certificates/verify/{number}', [\App\Http\Controllers\Api\CertificateController::class, 'verify']);
Route::get('/certificates/verify/{number}/download', [\App\Http\Controllers\Api\CertificateController::class, 'downloadByNumber']);
Route::get('/certificate-templates', [\App\Http\Controllers\Api\CertificateController::class, 'templates']);

// --- SSL Certificate Configuration ---
Route::get('/ssl-configs', [\App\Http\Controllers\Api\SslConfigController::class, 'index'])->middleware('auth:sanctum');
Route::post('/ssl-configs', [\App\Http\Controllers\Api\SslConfigController::class, 'store'])->middleware('auth:sanctum');
Route::get('/ssl-configs/{id}', [\App\Http\Controllers\Api\SslConfigController::class, 'show'])->middleware('auth:sanctum');
Route::put('/ssl-configs/{id}', [\App\Http\Controllers\Api\SslConfigController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/ssl-configs/{id}', [\App\Http\Controllers\Api\SslConfigController::class, 'destroy'])->middleware('auth:sanctum');
Route::post('/ssl-configs/{id}/validate', [\App\Http\Controllers\Api\SslConfigController::class, 'validate'])->middleware('auth:sanctum');
Route::get('/ssl-configs/expiring', [\App\Http\Controllers\Api\SslConfigController::class, 'expiring'])->middleware('auth:sanctum');
Route::get('/ssl-configs/{id}/export', [\App\Http\Controllers\Api\SslConfigController::class, 'export'])->middleware('auth:sanctum');

// --- CV Database Bulk Download (employer only) ---
Route::get('/cv-database/search', [\App\Http\Controllers\Api\CvBulkDownloadController::class, 'search'])->middleware('auth:sanctum');
Route::get('/cv-database/export', [\App\Http\Controllers\Api\CvBulkDownloadController::class, 'exportCsv'])->middleware('auth:sanctum');

Route::get('/profile/{username}', [PublicProfileController::class, 'show']);
Route::get('/candidates/public', [PublicProfileController::class, 'publicSearchCandidates']);

// --- Health Check (public, no auth) ---
Route::get('/health', [HealthController::class, 'check']);

// --- Public Subscription Plans (no auth needed for pricing page) ---
Route::get('/subscriptions/plans', [SubscriptionController::class, 'getPlans']);
Route::get('/leaderboard', [LeaderboardController::class, 'index']);

// --- Public Content Pages ---
Route::get('/pages/{slug}', [\App\Http\Controllers\Api\PageController::class, 'show']);

// --- Public FAQs ---
Route::get('/faqs', function () {
    $faqs = \App\Models\Faq::where('status', 'active')->orderByDesc('created_at')->get(['id', 'title', 'content']);
    return response()->json(['status' => true, 'data' => $faqs]);
});

// --- Public Currencies ---
Route::get('/currencies', function () {
    $currencies = \App\Models\Currency::where('enabled', true)->get(['id', 'code', 'symbol', 'rate']);
    return response()->json(['status' => true, 'data' => $currencies]);
});

// --- Newsletter Subscriber ---
Route::post('/subscribers', function (\Illuminate\Http\Request $request) {
    $request->validate(['email' => 'required|email']);
    $exists = \Illuminate\Support\Facades\DB::table('subscribers')->where('email', $request->email)->first();
    if ($exists) {
        return response()->json(['status' => true, 'message' => 'Already subscribed']);
    }
    \Illuminate\Support\Facades\DB::table('subscribers')->insert([
        'email' => $request->email,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    return response()->json(['status' => true, 'message' => 'Subscribed successfully']);
});

// --- Contact Form ---
Route::post('/contact', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'message' => 'required|string|max:5000',
    ]);

    \Illuminate\Support\Facades\DB::table('contact_messages')->insert([
        'name' => $request->name,
        'email' => $request->email,
        'message' => $request->message,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return response()->json(['status' => true, 'message' => 'Message sent successfully. We will get back to you soon.']);
});

// --- Public Invoice (guest lookup by token) ---

// --- Smart Search Subsystem ---
Route::get('/search', [SearchController::class, 'search']);
Route::get('/search/suggestions', [SearchController::class, 'suggestions']);
Route::get('/search/trending', [SearchController::class, 'trending']);

// --- Dynamic SEO Subsystem ---
Route::get('/seo', [SeoController::class, 'getMeta']);

// --- AI Assistant ---
Route::post('/ai/chat', [AiAssistantController::class, 'chat'])->middleware('throttle:20,1');

// --- AI Interactive Assistant Paths ---
Route::post('/ai/career-assistant', [AiCareerAssistantController::class, 'chatbot'])->middleware('throttle:20,1');
Route::get('/ai/job-match', [AiCareerAssistantController::class, 'jobMatch'])->middleware('throttle:30,1');
Route::get('/ai/salary-predict', [AiCareerAssistantController::class, 'salaryPredict'])->middleware('throttle:30,1');
Route::get('/ai/career-roadmap', [AiCareerAssistantController::class, 'careerRoadmap']);
Route::post('/ai/interview/start', [AiCareerAssistantController::class, 'startInterview']);
Route::post('/ai/interview/evaluate', [AiCareerAssistantController::class, 'evaluateInterview']);

// --- AI Cover Letter ---
Route::post('/ai/generate-cover-letter', [\App\Http\Controllers\Api\AiCoverLetterController::class, 'generate'])->middleware('auth:sanctum', 'throttle:30,1');

// --- Ads Management System ---
Route::get('/ads/serve', [\App\Http\Controllers\Api\AdController::class, 'serve']);
Route::post('/ads/{id}/impression', [\App\Http\Controllers\Api\AdController::class, 'logImpression']);
Route::post('/ads/{id}/click', [\App\Http\Controllers\Api\AdController::class, 'logClick']);

// --- Payment Webhook ---
Route::post('/payment/onipay/callback', [WalletController::class, 'oniPayCallback']);

// --- bKash Merchant Payment ---
Route::post('/payment/bkash/execute', [WalletController::class, 'executeBkashPayment']);
Route::get('/payment/bkash/callback', [WalletController::class, 'bkashCallback']);
Route::post('/payment/bkash/callback', [WalletController::class, 'bkashCallback']);

// --- Nagad Merchant Payment ---
Route::post('/payment/nagad/execute', [WalletController::class, 'executeNagadPayment']);

// --- Rocket Merchant Payment ---
Route::post('/payment/rocket/execute', [WalletController::class, 'executeRocketPayment']);

// --- SSLCommerz Payment Callback ---
Route::post('/payment/sslcommerz/callback', [WalletController::class, 'sslCommerzCallback']);
Route::get('/payment/sslcommerz/callback', [WalletController::class, 'sslCommerzCallback']);

// --- EPS Payment Callback ---
Route::post('/payment/eps/callback', [WalletController::class, 'epsCallback']);
Route::get('/payment/eps/callback', [WalletController::class, 'epsCallback']);

// --- Profile Intelligence Weights ---
Route::get('/settings/profile-intelligence', function () {
    $keys = [
        'weight_basic_info' => 10,
        'weight_resume' => 15,
        'weight_skills' => 10,
        'weight_experience' => 15,
        'weight_education' => 10,
        'weight_certifications' => 10,
        'weight_avatar' => 10,
        'weight_portfolio' => 10,
        'weight_social_links' => 5,
        'weight_bio' => 5,
        'min_required_strength' => 45,
        'restrict_low_strength_apply' => false,
    ];
    $settings = \App\Models\Setting::whereIn('key', array_keys($keys))->pluck('value', 'key')->toArray();
    foreach ($keys as $key => $default) {
        if (!isset($settings[$key])) $settings[$key] = $default;
    }
    return response()->json(['status' => true, 'data' => $settings]);
});


// ==========================================
// 2. AUTHENTICATED ROUTES (Login Required)
// ==========================================
Route::middleware(['auth:sanctum', 'security_monitor'])->group(function () {

    // --- Company Follow System (any authenticated user: candidate or employer) ---
    Route::post('/companies/{id}/follow', [CompanyController::class, 'toggleFollow']);
    Route::get('/companies/{companyId}/followers', [CompanyController::class, 'getFollowers']);
    Route::get('/companies/{companyId}/followers/check', [CompanyController::class, 'checkFollowStatus']);

    // --- Freelance Workspace Additions ---
    Route::get('/workspace/{jobId}/credentials', [WorkspaceController::class, 'getSecureCredentials'])->middleware('verification:escrow');
    Route::post('/workspace/{jobId}/credentials', [WorkspaceController::class, 'storeSecureCredential']);
    Route::delete('/workspace/{jobId}/credentials/{credentialId}', [WorkspaceController::class, 'deleteSecureCredential']);
    Route::get('/workspace/{jobId}/dispute', [WorkspaceController::class, 'getDispute']);

    // --- Work Diary / Hours Tracking ---
    Route::get('/workspace/{jobId}/diary', [WorkDiaryController::class, 'index']);
    Route::post('/workspace/{jobId}/diary', [WorkDiaryController::class, 'store']);
    Route::get('/employer/workspace/{jobId}/diary', [WorkDiaryController::class, 'employerIndex'])->middleware('role:employer|admin|super_admin|super-admin');
    Route::post('/employer/workspace/{jobId}/diary/{diaryId}/review', [WorkDiaryController::class, 'review'])->middleware('role:employer|admin|super_admin|super-admin');

    // --- Milestone Tracking ---
    Route::get('/workspace/{jobId}/milestones', [MilestoneController::class, 'index']);
    Route::post('/employer/workspace/{jobId}/milestones', [MilestoneController::class, 'store'])->middleware('role:employer|admin|super_admin|super-admin');
    Route::post('/workspace/{jobId}/milestones/{milestoneId}/submit', [MilestoneController::class, 'submit']);
    Route::post('/employer/workspace/{jobId}/milestones/{milestoneId}/review', [MilestoneController::class, 'review'])->middleware('role:employer|admin|super_admin|super-admin');

    // --- Freelance Financial Settings ---
    Route::post('/settings/financial', [FinancialSettingController::class, 'updateSettings']);

    // --- User Billing Profile ---
    Route::get('/billing/profile', [BillingProfileController::class, 'getProfile']);
    Route::post('/billing/profile', [BillingProfileController::class, 'updateProfile']);

    // --- Currency Preference Switcher ---
    Route::post('/user/currency', function (\Illuminate\Http\Request $request) {
        $request->validate(['currency' => 'required|in:BDT,USD']);
        Auth::user()->update(['currency_preference' => $request->currency]);
        return response()->json(['status' => true, 'message' => 'Currency preference updated successfully.']);
    });

    // --- Support Ticket System ---
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::get('/tickets/{id}', [TicketController::class, 'show']);
    Route::post('/tickets/{id}/reply', [TicketController::class, 'reply']);

    // --- Support Ticket System (v2) ---
    Route::get('/support/tickets', [SupportTicketController::class, 'index']);
    Route::post('/support/tickets', [SupportTicketController::class, 'store']);
    Route::get('/support/tickets/{id}', [SupportTicketController::class, 'show']);
    Route::post('/support/tickets/{id}/reply', [SupportTicketController::class, 'reply']);
    Route::post('/support/tickets/{id}/close', [SupportTicketController::class, 'close']);

    // --- Subscription System ---
    Route::get('/subscriptions/plans', [SubscriptionController::class, 'getPlans']);
    Route::get('/subscriptions/my-subscription', [SubscriptionController::class, 'getMySubscription']);
    Route::post('/subscriptions/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::post('/subscriptions/cancel', [SubscriptionController::class, 'cancel']);

    // --- Purchases History ---
    Route::get('/purchases', [PurchaseController::class, 'index']);

    // --- Verification System ---
    Route::post('/verifications/nid', [VerificationController::class, 'submitNid']);
    Route::post('/verifications/phone/request', [VerificationController::class, 'requestPhoneOtp'])->middleware('throttle:5,1');
    Route::post('/verifications/phone/confirm', [VerificationController::class, 'confirmPhoneOtp'])->middleware('throttle:10,1');
    Route::post('/verifications/email/request', [VerificationController::class, 'requestEmailOtp'])->middleware('throttle:5,1');
    Route::post('/verifications/email/confirm', [VerificationController::class, 'confirmEmailOtp'])->middleware('throttle:10,1');
    Route::post('/verifications/employer', [VerificationController::class, 'submitEmployer']);
    Route::get('/verifications/status', [VerificationController::class, 'status']);

    // --- Report System ---
    Route::post('/reports', [\App\Http\Controllers\Api\ReportController::class, 'store']);

    // --- AI Algorithm & Behavior Tracking ---
    Route::post('/behavior/track', [AiFeatureController::class, 'trackBehavior']);
    Route::get('/jobs/{id}/ai-insights', [AiFeatureController::class, 'getJobAiInsights']);
    Route::get('/candidates/{username}/ai-insights', [AiFeatureController::class, 'getCandidateAiInsights']);
    Route::post('/admin/overrides', [AiFeatureController::class, 'submitManualOverride'])->middleware('role:admin');

    // --- AI Career & Employer Insights Subsystem ---
    Route::get('/ai/coach/insights', [AiFeatureController::class, 'getCandidateCoachInsights']);
    Route::get('/ai/employer/candidate-insights/{candidateId}', [AiFeatureController::class, 'getEmployerCandidateInsights']);
    Route::get('/admin/ai/analytics', [AiFeatureController::class, 'getAdminAiAnalytics'])->middleware('role:admin');

    // --- Profile Views & Candidate Intelligence ---
    Route::post('/candidate/views/log', [CompanyEcosystemController::class, 'logProfileView']);
    Route::get('/candidate/profile-strength', [CompanyEcosystemController::class, 'getProfileStrength']);
    Route::get('/candidate/views', [CompanyEcosystemController::class, 'getProfileViews']);
    Route::get('/candidate/ai-insights', [CompanyEcosystemController::class, 'getAiInsights']);

    // --- Company Ecosystem Shared Operations ---
    Route::post('/company/brochures/{id}/download', [CompanyEcosystemController::class, 'downloadBrochure']);
    Route::post('/company/updates/{id}/react', [CompanyEcosystemController::class, 'reactToUpdate']);
    Route::post('/company/updates/{id}/comment', [CompanyEcosystemController::class, 'commentOnUpdate']);

    // --- Core Auth & User ---
    Route::post('/admin/impersonate/{userId}', [AuthController::class, 'impersonate'])->middleware('role:admin');
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (\Illuminate\Http\Request $request) {
        $user = $request->user()->load(['profile', 'company', 'wallet']); // Eager load profile, company, and wallet
        $user->role = $user->getRoleNames()->first() ?? 'candidate';
        $user->active_badges = $user->activeBadges();
        $user->is_verified = $user->hasBadge('verified');
        // Ensure wallet balance is available instantly
        if (!$user->wallet) {
            $user->wallet = \App\Models\Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'locked_balance' => 0, 'withdrawable_balance' => 0]
            );
        }
        // Use cached balance for instant loading
        $user->wallet_balance = Cache::remember("wallet_balance_{$user->id}", 300, function () use ($user) {
            return (float) $user->wallet->balance;
        });
        return response()->json($user);
    });

    // --- Notifications ---
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/bulk-read', [NotificationController::class, 'bulkRead']);

    // --- Saved Searches ---
    Route::post('/search/saved', [SearchController::class, 'saveSearch']);
    Route::get('/search/saved', [SearchController::class, 'getSavedSearches']);
    Route::delete('/search/saved/{id}', [SearchController::class, 'deleteSavedSearch']);

    // --- Messaging System ---
    Route::get('/messages/inbox', [MessageController::class, 'getConversations']);
    Route::get('/messages/unread-count', [MessageController::class, 'getUnreadCount']);
    Route::get('/messages/direct/{targetUserId}', [MessageController::class, 'fetchDirectMessages']);
    Route::post('/messages/direct/{targetUserId}', [MessageController::class, 'sendDirectMessage'])->middleware('ai_moderation:messages');
    Route::get('/messages/attachment/{path}', [MessageController::class, 'downloadAttachment'])->where('path', '.*')->middleware('auth:sanctum');

    // UUID Secure Messaging & Actions
    Route::get('/messages/conversation/{uuid}', [MessageController::class, 'fetchMessagesByUuid']);
    Route::post('/messages/conversation/{uuid}', [MessageController::class, 'sendMessageByUuid'])->middleware('ai_moderation:messages');
    Route::post('/messages/conversation/{uuid}/block', [MessageController::class, 'blockUser']);
    Route::post('/messages/conversation/{uuid}/report', [MessageController::class, 'reportUser']);
    Route::delete('/messages/conversation/{uuid}', [MessageController::class, 'deleteConversation']);
    Route::delete('/messages/conversation/{uuid}/message/{messageId}', [MessageController::class, 'deleteMessage']);
    Route::post('/messages/conversation/{uuid}/mute', [MessageController::class, 'toggleMute']);

    // --- Company Reviews (authenticated) ---
    Route::post('/companies/{companyId}/reviews', [CompanyReviewController::class, 'store']);

    // --- User Follow System (any authenticated user may follow others) ---
    Route::prefix('candidate/profile')->group(function () {
        Route::post('/{username}/follow', [PublicProfileController::class, 'toggleFollow']);
        Route::get('/{username}/follow-status', [PublicProfileController::class, 'getFollowStatus']);
        Route::get('/{username}/follow/check', [PublicProfileController::class, 'getFollowStatus']);
    });

    // ==========================================
    // 3. CANDIDATE ONLY ROUTES
    // ==========================================
    Route::prefix('candidate')->middleware('role:candidate|admin|super_admin|super-admin|employer')->group(function () {
        Route::get('/dashboard', [CandidateController::class, 'dashboard']);
        Route::get('/cv-data', [CvProfileController::class, 'getProfile']);
        Route::get('/profile-views', [CandidateController::class, 'profileViews']);
        Route::post('/profile-update', [CandidateController::class, 'updateProfile']);
        Route::post('/profile/educations', [CandidateController::class, 'saveEducations']);
        Route::post('/profile/experiences', [CandidateController::class, 'saveExperiences']);
        Route::post('/profile/trainings', [CandidateController::class, 'saveTrainings']);
        Route::post('/profile/certifications', [CandidateController::class, 'saveCertifications']);
        Route::post('/profile/references', [CandidateController::class, 'saveReferences']);
        Route::post('/profile/documents', [CandidateController::class, 'saveDocuments']);
        Route::post('/password-update', [CandidateController::class, 'updatePassword']);
        Route::post('/resume-upload', [CandidateController::class, 'uploadResume']);
        Route::get('/recommended-jobs', [CandidateController::class, 'recommendedJobs']);

        // Job Interactivity
        Route::get('/applied-jobs', [CandidateController::class, 'appliedJobs']);
        Route::get('/accepted-jobs', [CandidateController::class, 'acceptedJobs']);
        Route::get('/saved-jobs', [CandidateController::class, 'savedJobs']);
        Route::post('/toggle-save/{jobId}', [CandidateController::class, 'toggleSaveJob']);

        // Job Alerts
        Route::get('/job-alerts', [\App\Http\Controllers\Api\JobAlertController::class, 'index']);
        Route::post('/job-alerts', [\App\Http\Controllers\Api\JobAlertController::class, 'store']);
        Route::get('/job-alerts/preview', [\App\Http\Controllers\Api\JobAlertController::class, 'preview']);
        Route::get('/job-alerts/{jobAlert}', [\App\Http\Controllers\Api\JobAlertController::class, 'show']);
        Route::put('/job-alerts/{jobAlert}', [\App\Http\Controllers\Api\JobAlertController::class, 'update']);
        Route::delete('/job-alerts/{jobAlert}', [\App\Http\Controllers\Api\JobAlertController::class, 'destroy']);
        Route::post('/job-alerts/{jobAlert}/toggle', [\App\Http\Controllers\Api\JobAlertController::class, 'toggleActive']);
        Route::post('/jobs/{id}/apply', [JobController::class, 'apply'])->middleware('verification:apply');

        // Workspace Operations
        Route::get('/workspace', [WorkspaceController::class, 'candidateWorkspace']);
        Route::post('/workspace/{jobId}/submit', [WorkspaceController::class, 'submitWork']);
        Route::post('/workspace/{jobId}/dispute', [WorkspaceController::class, 'openDispute']);

        // Interview Schedule Management
        Route::get('/interviews', [CandidateController::class, 'getInterviews']);
        Route::post('/interviews/{interviewId}/respond', [CandidateController::class, 'respondToInterview']);

        // Wallet & Finances
        Route::get('/wallet', [CandidateController::class, 'wallet']);
        Route::post('/deposit', [WalletController::class, 'deposit']); // Shared endpoint setup
        Route::post('/withdraw', [CandidateController::class, 'withdraw'])->middleware('verification:wallet');
    });

    // ==========================================
    // 3.5. CV BUILDER & RESUME ROUTES (All Authenticated Users)
    // ==========================================
    Route::group([], function () {
        Route::get('/candidate/cv/profile', [CvProfileController::class, 'getProfile']);
        Route::post('/candidate/cv/profile/update', [CvProfileController::class, 'updateProfile']);
        Route::post('/candidate/cv/profile/upload-photo', [CvProfileController::class, 'uploadPhoto']);
        Route::get('/candidate/cv/resumes', [CvResumeController::class, 'index']);
        Route::post('/candidate/cv/resumes', [CvResumeController::class, 'create']);
        Route::get('/candidate/cv/resumes/{uuid}', [CvResumeController::class, 'show']);
        Route::put('/candidate/cv/resumes/{uuid}', [CvResumeController::class, 'update']);
        Route::get('/candidate/cv/resumes/{uuid}/preview', [CvResumeController::class, 'renderPreview']);
        Route::get('/candidate/cv/resumes/{uuid}/download', [CvResumeController::class, 'download']);
        Route::post('/candidate/cv/resumes/{uuid}/duplicate', [CvResumeController::class, 'duplicate']);
        Route::get('/candidate/cv/resumes/{uuid}/versions', [CvResumeController::class, 'getVersions']);
        Route::post('/candidate/cv/resumes/{uuid}/restore/{version_id}', [CvResumeController::class, 'restoreVersion']);
        Route::post('/candidate/cv/resumes/{uuid}/share', [CvResumeController::class, 'updateShareSettings']);
        Route::delete('/candidate/cv/resumes/{uuid}', [CvResumeController::class, 'destroy']);

        // Base /cv alias routes
        Route::get('/cv/profile', [CvProfileController::class, 'getProfile']);
        Route::post('/cv/profile/update', [CvProfileController::class, 'updateProfile']);
        Route::post('/cv/profile/upload-photo', [CvProfileController::class, 'uploadPhoto']);
        Route::get('/cv/resumes', [CvResumeController::class, 'index']);
        Route::post('/cv/resumes', [CvResumeController::class, 'create']);
        Route::post('/cv/create', [CvResumeController::class, 'create']);
        Route::get('/cv/resumes/{uuid}', [CvResumeController::class, 'show']);
        Route::put('/cv/resumes/{uuid}', [CvResumeController::class, 'update']);
        Route::get('/cv/resumes/{uuid}/preview', [CvResumeController::class, 'renderPreview']);
        Route::get('/cv/resumes/{uuid}/download', [CvResumeController::class, 'download']);
        Route::post('/cv/resumes/{uuid}/duplicate', [CvResumeController::class, 'duplicate']);
        Route::get('/cv/resumes/{uuid}/versions', [CvResumeController::class, 'getVersions']);
        Route::post('/cv/resumes/{uuid}/restore/{version_id}', [CvResumeController::class, 'restoreVersion']);
        Route::post('/cv/resumes/{uuid}/share', [CvResumeController::class, 'updateShareSettings']);
        Route::delete('/cv/resumes/{uuid}', [CvResumeController::class, 'destroy']);

        // AI CV Generation (subscription-gated)
        Route::post('/candidate/cv/generate-ai', [CvResumeController::class, 'generateWithAi']);
        Route::get('/candidate/cv/{uuid}/share', [CvResumeController::class, 'share']);
        Route::get('/candidate/cv/{uuid}/download-pdf', [CvResumeController::class, 'downloadPdf']);
        Route::post('/candidate/cv/ai/rewrite-achievement', [\App\Http\Controllers\Api\CvAiAssistantController::class, 'rewriteAchievement']);
        Route::post('/candidate/cv/ai/ats-score', [\App\Http\Controllers\Api\CvAiAssistantController::class, 'computeAtsScore']);

        Route::post('/cv/generate-ai', [CvResumeController::class, 'generateWithAi']);
        Route::get('/cv/{uuid}/share', [CvResumeController::class, 'share']);
        Route::get('/cv/{uuid}/download-pdf', [CvResumeController::class, 'downloadPdf']);
        Route::post('/cv/ai/rewrite-achievement', [\App\Http\Controllers\Api\CvAiAssistantController::class, 'rewriteAchievement']);
        Route::post('/cv/ai/ats-score', [\App\Http\Controllers\Api\CvAiAssistantController::class, 'computeAtsScore']);
    });

    Route::prefix('candidate')->middleware('role:candidate|admin|super_admin|super-admin|employer')->group(function () {

        // Resume Score Checker
        Route::post('/resume-score', [\App\Http\Controllers\Api\ResumeScoreController::class, 'scoreResume']);
        Route::post('/resume-score/pdf', [\App\Http\Controllers\Api\ResumeScoreController::class, 'scorePdf']);

        Route::get('/jobs/{id}/ai-match', [AiFeatureController::class, 'getMatchScore']);
        Route::post('/ai/generate-cv-profile', [AiFeatureController::class, 'generateCvProfile']);

        // Candidate Analytics
        Route::get('/analytics', [CandidateController::class, 'analytics']);

        // Company Reviews
        // (moved to public auth section below)

        // --- Contract/Agreement System ---
        Route::get('/contracts', [ContractController::class, 'index']);
        Route::get('/contracts/{id}', [ContractController::class, 'show']);
        Route::post('/contracts', [ContractController::class, 'store']);
        Route::put('/contracts/{id}', [ContractController::class, 'update']);
        Route::post('/contracts/{id}/request-otp', [ContractController::class, 'requestOtp'])->middleware('throttle:5,1');
        Route::post('/contracts/{id}/sign', [ContractController::class, 'sign'])->middleware('throttle:5,1');
        Route::post('/contracts/{id}/terminate', [ContractController::class, 'terminate']);
        Route::get('/applications/{applicationId}/contract', [ContractController::class, 'fromApplication']);

        // Candidate: Accept/Reject custom hire offers
        Route::post('/contracts/{id}/accept', [CustomHireController::class, 'acceptOffer']);
        Route::post('/contracts/{id}/reject', [CustomHireController::class, 'rejectOffer']);

        // --- Bidirectional Rating System ---
        Route::post('/ratings', [\App\Http\Controllers\Api\ProjectRatingController::class, 'store']);
        Route::get('/ratings/user/{userId}', [\App\Http\Controllers\Api\ProjectRatingController::class, 'getUserRatings']);
        Route::get('/ratings/job/{jobId}', [\App\Http\Controllers\Api\ProjectRatingController::class, 'getJobRatings']);
        Route::get('/ratings/eligibility/{jobId}', [\App\Http\Controllers\Api\ProjectRatingController::class, 'checkEligibility']);
    });


    // ==========================================
    // 4. EMPLOYER ONLY ROUTES
    // ==========================================
    Route::prefix('employer')->middleware('role:employer|admin|super_admin|super-admin')->group(function () {
        Route::get('/dashboard', [EmployerController::class, 'dashboard']);
        Route::get('/profile', [CompanyController::class, 'getProfile']);
        Route::post('/profile-update', [CompanyController::class, 'update']);
        Route::post('/hr-team', [CompanyController::class, 'saveHrTeam']);
        Route::get('/candidates', [EmployerController::class, 'searchCandidates']);

        // Job postings & Pipeline Mechanics
        Route::get('/jobs', [EmployerController::class, 'getJobs']);
        Route::post('/jobs', [EmployerController::class, 'storeJob'])->middleware('verification:post');
        Route::put('/jobs/{id}', [EmployerController::class, 'updateJob']);
        Route::delete('/jobs/{id}', [EmployerController::class, 'deleteJob']);
        Route::patch('/jobs/{id}/toggle', [EmployerController::class, 'toggleStatus']);
        Route::post('/ai/generate-description', [AiFeatureController::class, 'generateJobDescription']);

        // Applicant Lifecycle Updates
        Route::get('/applicants', [EmployerController::class, 'getApplicants']);
        Route::get('/applicants/{id}', [EmployerController::class, 'getApplicant']);
        Route::match(['post', 'patch'], '/applicants/{id}', [EmployerController::class, 'updateStatus']);
        Route::post('/applicants/{id}/status', [EmployerController::class, 'updateStatus']);
        Route::post('/applicants/{id}/hire', [EmployerController::class, 'hireApplicant']);
        Route::post('/projects/{jobId}/assign/{applicationId}', [EmployerController::class, 'assignProjectCandidate']);

        // Custom Hire / Contract Offers
        Route::post('/custom-hire/preview', [CustomHireController::class, 'preview']);
        Route::post('/custom-hire/send', [CustomHireController::class, 'sendOffer']);
        Route::patch('/custom-hire/{contractId}/edit', [CustomHireController::class, 'editOffer']);
        Route::post('/custom-hire/{contractId}/sign', [CustomHireController::class, 'sign'])->middleware('throttle:5,1');
        Route::post('/custom-hire/{contractId}/request-otp', [CustomHireController::class, 'requestOtp'])->middleware('throttle:5,1');
        Route::get('/contracts', [CustomHireController::class, 'employerContracts']);
        Route::get('/contracts/{id}', [CustomHireController::class, 'employerShow']);

        // Bulk Email/SMS + delivery monitor
        Route::post('/bulk-message', [EmployerController::class, 'sendBulkMessage']);
        Route::get('/bulk-messages', [EmployerController::class, 'bulkMessageBatches']);
        Route::get('/bulk-messages/{batchId}/recipients', [EmployerController::class, 'bulkMessageRecipients']);
        Route::post('/bulk-messages/{batchId}/recipients/{recipientId}/retry', [EmployerController::class, 'bulkMessageRetry']);

        // Project Performance Workspaces
        Route::get('/workspace', [WorkspaceController::class, 'employerWorkspace']);
        Route::post('/workspace/{jobId}/release', [WorkspaceController::class, 'releasePayment']);
        Route::post('/workspace/{jobId}/revision', [WorkspaceController::class, 'requestRevision']);
        Route::post('/workspace/{jobId}/reject', [WorkspaceController::class, 'rejectWork']);
        Route::post('/workspace/{jobId}/dispute', [WorkspaceController::class, 'openDispute']);
        Route::post('/workspace/{jobId}/milestones/{milestoneId}/release', [WorkspaceController::class, 'releaseMilestonePayment']);

        // Interview Schedule Management
        Route::post('/interviews/{applicationId}', [EmployerController::class, 'scheduleInterview']);
        Route::get('/interviews', [EmployerController::class, 'getInterviews']);
        Route::put('/interviews/{interviewId}/status', [EmployerController::class, 'updateInterviewStatus']);
        Route::get('/accepted-jobs', [EmployerController::class, 'getAcceptedJobs']);

        // Job Offers
        Route::post('/applications/{id}/offer', [EmployerController::class, 'offerApplication']);

        // Finance ledger & Ad Marketing Engines
        Route::get('/wallet', [WalletController::class, 'index']);
        Route::post('/deposit', [WalletController::class, 'deposit']);
        Route::get('/promotions', [PromotionController::class, 'index']);
        Route::post('/promotions', [PromotionController::class, 'store'])->middleware('verification:wallet');
        Route::put('/promotions/{id}', [PromotionController::class, 'update']);
        Route::patch('/promotions/{id}/toggle', [PromotionController::class, 'toggleStatus']);
        Route::get('/promotions/{id}/analytics', [PromotionController::class, 'analytics']);
        Route::get('/promotions/{id}/ai-suggestions', [PromotionController::class, 'getAiSuggestions']);

        // --- Company Ecosystem Admin Operations ---
        Route::post('/benefits', [CompanyEcosystemController::class, 'saveBenefits']);
        Route::post('/brochures', [CompanyEcosystemController::class, 'uploadBrochure']);
        Route::post('/gallery', [CompanyEcosystemController::class, 'uploadCulturePhoto']);
        Route::post('/awards', [CompanyEcosystemController::class, 'addAward']);
        Route::post('/updates', [CompanyEcosystemController::class, 'storeUpdate']);
        Route::get('/analytics', [CompanyEcosystemController::class, 'getEmployerAnalytics']);
    });

    // --- Admin Security & Audit Desk ---
    Route::prefix('admin/security')->middleware('role:admin')->group(function () {
        Route::get('/logs', [\App\Http\Controllers\Api\Admin\SecurityController::class, 'getLogs']);
        Route::post('/users/{userId}/restrict', [\App\Http\Controllers\Api\Admin\SecurityController::class, 'updateRestriction']);
        Route::get('/users/{userId}/details', [\App\Http\Controllers\Api\Admin\SecurityController::class, 'getUserSecurityDetails']);
    });

    // --- Custom User Security & 2FA Management ---
    Route::prefix('two-factor')->group(function () {
        Route::post('/setup', [\App\Http\Controllers\Api\TwoFactorApiController::class, 'setup']);
        Route::post('/confirm', [\App\Http\Controllers\Api\TwoFactorApiController::class, 'confirm']);
        Route::post('/disable', [\App\Http\Controllers\Api\TwoFactorApiController::class, 'disable']);
        Route::get('/sessions', [\App\Http\Controllers\Api\TwoFactorApiController::class, 'getSessions']);
        Route::post('/sessions/logout', [\App\Http\Controllers\Api\TwoFactorApiController::class, 'logoutSession']);
        Route::post('/sessions/logout-all', [\App\Http\Controllers\Api\TwoFactorApiController::class, 'logoutAllSessions']);
        Route::get('/history', [\App\Http\Controllers\Api\TwoFactorApiController::class, 'getHistoryLogs']);
        Route::post('/change-password', [\App\Http\Controllers\Api\TwoFactorApiController::class, 'changePassword']);
        Route::get('/status', [\App\Http\Controllers\Api\TwoFactorApiController::class, 'getStatus']);
        Route::post('/send-otp', [\App\Http\Controllers\Api\TwoFactorApiController::class, 'sendOtpSetup']);
        Route::post('/confirm-otp', [\App\Http\Controllers\Api\TwoFactorApiController::class, 'confirmOtpSetup']);
        Route::post('/send-login-otp', [\App\Http\Controllers\Api\TwoFactorApiController::class, 'sendLoginOtp']);
        Route::post('/verify-login-otp', [\App\Http\Controllers\Api\TwoFactorApiController::class, 'verifyLoginOtp']);
    });

    // --- Admin CV Templates Management Panel ---
    Route::prefix('admin/cv-templates')->middleware('role:admin')->group(function () {
        Route::post('/editor-save', [\App\Http\Controllers\Api\CvTemplateAdminController::class, 'saveEditorTemplate']);
        Route::post('/zip-upload', [\App\Http\Controllers\Api\CvTemplateAdminController::class, 'uploadZipTemplate']);
        Route::get('/usage-analytics', [\App\Http\Controllers\Api\CvTemplateAdminController::class, 'getUsageAnalytics']);
    });

    // --- Admin Image Settings & Analytics ---
    Route::prefix('admin/images')->middleware('role:admin')->group(function () {
        Route::get('/settings', [ImageSettingsController::class, 'getSettings']);
        Route::post('/settings', [ImageSettingsController::class, 'updateSettings']);
        Route::get('/analytics', [ImageSettingsController::class, 'getAnalytics']);
    });

    // --- Image AI Moderation ---
    Route::post('/images/moderate', [ImageModerationController::class, 'moderateImage']);
});
