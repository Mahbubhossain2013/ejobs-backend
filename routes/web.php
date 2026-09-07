<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\CvResumeController;
use App\Http\Controllers\Candidate\JobController as CandidateJobController;
use App\Http\Controllers\Employer\JobController as EmployerJobController;
use App\Http\Controllers\Api\SeoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- Public Base Routes ---
Route::get('/', fn () => redirect('/admin'));



// --- Dynamic SEO Assets ---
Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);
Route::get('/robots.txt', [SeoController::class, 'robots']);

// --- Payment Callbacks (Web/Redirect routes) ---
Route::match(['get', 'post'], '/payment/eps/callback', [\App\Http\Controllers\Api\WalletController::class, 'epsCallback']);
Route::match(['get', 'post'], '/payment/bkash/callback', [\App\Http\Controllers\Api\WalletController::class, 'bkashCallback']);
Route::match(['get', 'post'], '/payment/sslcommerz/callback', [\App\Http\Controllers\Api\WalletController::class, 'sslCommerzCallback']);
Route::match(['get', 'post'], '/payment/onipay/callback', [\App\Http\Controllers\Api\WalletController::class, 'oniPayCallback']);

// CRITICAL FIX: The iframe layout engine preview route sits here publicly
Route::get('/cv/preview/{uuid}', [CvResumeController::class, 'renderPreview'])->name('cv.preview');
Route::match(['get', 'post'], '/cv/share/{uuid}', [CvResumeController::class, 'renderSharedResume'])->name('cv.share');

// Public template demo preview (no auth required) — for template browsing
Route::get('/cv/demo/{slug}', [CvResumeController::class, 'previewDemo'])->name('cv.demo');
Route::match(['get', 'post'], '/cv/live-preview/{slug}', [CvResumeController::class, 'livePreview'])->name('cv.live-preview');

// Functional Web Cron API
Route::get('/cron/run/{token}', function ($token) {
    $expectedToken = env('CRON_SECRET_TOKEN', '');
    if (!$expectedToken || !hash_equals($expectedToken, $token)) {
        return response()->json(['status' => false, 'message' => 'Unauthorized token.'], 403);
    }
    try {
        \Illuminate\Support\Facades\Artisan::call('schedule:run');
        \App\Models\Setting::updateOrCreate(
            ['key' => 'last_cron_run_at'],
            ['value' => \Carbon\Carbon::now()->toDateTimeString()]
        );
        return response()->json([
            'status' => true,
            'message' => 'Cron executed successfully.',
            'timestamp' => \Carbon\Carbon::now()->toIso8601String(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Execution failed: ' . $e->getMessage()
        ], 500);
    }
});


/**
 * Employer Dashboard & Posting Routes
 */
Route::middleware(['auth', 'role:employer'])
    ->prefix('employer')
    ->name('employer.')
    ->group(function () {
        
        Route::get('/dashboard', function () {
            return view('employer.dashboard');
        })->name('dashboard');

        Route::get('/jobs/create', [EmployerJobController::class, 'create'])->name('jobs.create');
        Route::post('/jobs', [EmployerJobController::class, 'store'])->name('jobs.store');
    });


/**
 * Candidate Discovery & Application Routes
 */
Route::middleware(['auth', 'role:candidate'])
    ->name('candidate.')
    ->group(function () {
        
        Route::get('/jobs', [CandidateJobController::class, 'index'])->name('jobs.index');
        Route::post('/jobs/{job}/apply', [CandidateJobController::class, 'apply'])->name('jobs.apply');
    });


// --- Filament Auth Routes ---
// Filament panel is now at /admin path, so auth routes don't conflict with Breeze.

// --- Filament Custom Two-Factor Challenge Screen ---
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/two-factor-challenge', \App\Filament\Pages\TwoFactorChallenge::class)->name('filament.admin.two-factor-challenge');
});

// Direct GET Admin Logout route for quick session clearance
Route::middleware('web')->get('/admin/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/admin/login');
})->name('admin.logout.get');

// --- Auth Scaffold Subsystem ---
require __DIR__.'/auth.php';