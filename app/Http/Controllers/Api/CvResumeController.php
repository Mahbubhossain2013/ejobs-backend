<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CvTemplate;
use App\Models\Resume;
use App\Models\CandidateData;
use App\Services\Ai\AiManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use Barryvdh\DomPDF\Facade\Pdf;

class CvResumeController extends Controller
{
    /**
     * Retrieve all resumes created by the candidate
     */
    public function index()
    {
        $resumes = Resume::where('user_id', Auth::id())->latest()->get();
        return response()->json(['status' => true, 'data' => $resumes]);
    }

    /**
     * Create a new resume instance from profile data
     */
    public function create(Request $request)
    {
        try {
            $request->validate(['template_slug' => 'required|string']);

            $user = Auth::user();
            $profileData = CandidateData::where('user_id', $user->id)->first();

            // Extract incoming snapshot from request if provided by wizard
            $incoming = $request->input('data_snapshot') ?? $request->input('data') ?? [];

            $personalInfo = $incoming['personal'] ?? $incoming['personal_info'] ?? ($profileData ? $profileData->personal_info : []) ?? [
                'full_name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'title' => '',
                'phone' => ''
            ];

            $summary = $incoming['summary'] 
                ?? $incoming['resume_objective']['description'] 
                ?? (is_string($incoming['resume_objective'] ?? null) ? $incoming['resume_objective'] : null)
                ?? ($profileData ? ($profileData->summary ?? $profileData->personal_info['summary'] ?? '') : '');

            $experience = $incoming['experience'] ?? $incoming['work_experience'] ?? ($profileData ? $profileData->experience : []) ?? [];
            $education = $incoming['education'] ?? ($profileData ? $profileData->education : []) ?? [];
            $skills = $incoming['skills'] ?? ($profileData ? $profileData->skills : []) ?? [];
            $languages = $incoming['languages'] ?? ($profileData ? $profileData->languages : []) ?? [];
            $projects = $incoming['projects'] ?? ($profileData ? $profileData->projects : []) ?? [];
            $certifications = $incoming['certifications'] ?? ($profileData ? $profileData->certifications : []) ?? [];
            $awards = $incoming['awards'] ?? $incoming['achievements'] ?? ($profileData ? $profileData->awards : []) ?? [];
            $hobbies = $incoming['hobbies'] ?? $incoming['interests'] ?? ($profileData ? $profileData->hobbies : []) ?? [];
            $socialLinks = $incoming['social_links'] ?? ($profileData ? $profileData->social_links : []) ?? [];
            $references = $incoming['references'] ?? ($profileData ? $profileData->references : []) ?? [];
            $training = $incoming['training'] ?? ($profileData ? $profileData->training : []) ?? [];

            // Auto-create or update CandidateData in DB
            if (!$profileData) {
                $profileData = CandidateData::create([
                    'user_id' => $user->id,
                    'personal_info' => $personalInfo,
                    'skills' => $skills,
                    'experience' => $experience,
                    'education' => $education,
                    'projects' => $projects,
                    'certifications' => $certifications,
                    'languages' => $languages,
                    'awards' => $awards,
                    'hobbies' => $hobbies,
                    'social_links' => $socialLinks,
                    'references' => $references,
                    'training' => $training,
                ]);
            } else {
                $profileData->update([
                    'personal_info' => !empty($personalInfo) ? $personalInfo : $profileData->personal_info,
                    'skills' => !empty($skills) ? $skills : $profileData->skills,
                    'experience' => !empty($experience) ? $experience : $profileData->experience,
                    'education' => !empty($education) ? $education : $profileData->education,
                    'projects' => !empty($projects) ? $projects : $profileData->projects,
                    'certifications' => !empty($certifications) ? $certifications : $profileData->certifications,
                    'languages' => !empty($languages) ? $languages : $profileData->languages,
                    'awards' => !empty($awards) ? $awards : $profileData->awards,
                    'hobbies' => !empty($hobbies) ? $hobbies : $profileData->hobbies,
                    'social_links' => !empty($socialLinks) ? $socialLinks : $profileData->social_links,
                    'references' => !empty($references) ? $references : $profileData->references,
                    'training' => !empty($training) ? $training : $profileData->training,
                ]);
            }

            // Step 4: Handle premium template purchase - deduct wallet (one-time only)
            $template = \App\Models\CvTemplate::where('slug', $request->template_slug)->first();
            $isPremiumPurchase = $template && $template->is_premium && $template->price > 0;

            if ($isPremiumPurchase) {
                $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();
                if ($wallet) {
                    $alreadyPurchased = \App\Models\WalletTransaction::where('wallet_id', $wallet->id)
                        ->where('reference_type', 'cv_template_purchase')
                        ->where('description', "CV Template Purchase: {$template->name}")
                        ->where('status', 'completed')
                        ->exists();

                    if ($alreadyPurchased) {
                        $isPremiumPurchase = false;
                    }
                }

                if ($isPremiumPurchase) {
                    $wallet = \App\Models\Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                    if (!$wallet || $wallet->balance < $template->price) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Insufficient wallet balance to purchase this template.'
                        ], 402);
                    }

                    $wallet->debit(
                        $template->price,
                        'cv_template_purchase',
                        null,
                        "CV Template Purchase: {$template->name}"
                    );
                }
            }

            // Step 5: Create the Resume Record with 100% full snapshot
            $resume = Resume::create([
                'user_id' => $user->id,
                'title' => $request->title ?? ('My CV - ' . $request->template_slug),
                'template_slug' => $request->template_slug,
                'uuid' => (string) Str::uuid(),
                'data_snapshot' => [
                    'personal' => $personalInfo,
                    'summary' => $summary,
                    'skills' => $skills,
                    'experience' => $experience,
                    'education' => $education,
                    'projects' => $projects,
                    'certifications' => $certifications,
                    'languages' => $languages,
                    'awards' => $awards,
                    'hobbies' => $hobbies,
                    'social_links' => $socialLinks,
                    'references' => $references,
                    'training' => $training,
                ]
            ]);

            Log::info("Resume created for user {$user->id} with UUID {$resume->uuid}");
            return response()->json(['status' => true, 'data' => $resume]);
        } catch (\Throwable $e) {
            Log::error("CV Create Crash: " . $e->getMessage(), ['user_id' => Auth::id()]);
            return response()->json([
                'status' => false,
                'message' => 'Error creating resume. Please try again.'
            ], 500);
        }
    }

    /**
     * Get a specific resume's data for the editor
     */
    public function show($uuid)
    {
        try {
            $resume = Resume::where('uuid', $uuid)->where('user_id', Auth::id())->firstOrFail();
            return response()->json(['status' => true, 'data' => $resume]);
        } catch (\Exception $e) {
            Log::error("Resume fetch error: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Resume not found'
            ], 404);
        }
    }
    public function renderPreview($uuid)
    {
        try {
            $resume = Resume::where('uuid', $uuid)->firstOrFail();

            // Identify owner via session or Bearer token
            $userId = Auth::id();
            if (!$userId && request()->bearerToken()) {
                $token = \Laravel\Sanctum\PersonalAccessToken::findToken(request()->bearerToken());
                if ($token) $userId = $token->tokenable_id;
            }
            $isOwner = $userId && $resume->user_id == $userId;
            if (!$isOwner && !$resume->is_public) {
                return response("This resume is private.", 403);
            }

            $template = CvTemplate::where('slug', $resume->template_slug)->first();

            if (!$template) {
                $template = CvTemplate::where('slug', 'minimalist-free')->first();
            }

            if (!$template) {
                return response("Template not found.", 404);
            }

            $renderer = app(\App\Services\Cv\CvRenderingService::class);
            $themeSettings = $resume->theme_settings ?? [];

            if (!$isOwner) {
                $resume->increment('views_count');
            }

            $html = $renderer->render($template, $resume->data_snapshot ?? [], $themeSettings);
            return response($html);
        } catch (\Exception $e) {
            Log::error("Preview Render Crash: " . $e->getMessage());
            return response("Failed to load CV preview.", 500);
        }
    }

    /**
     * Render a public demo preview of a template with sample data.
     * No auth required — used for template browsing before purchase.
     */
    public function previewDemo($slug)
    {
        try {
            $template = CvTemplate::where('slug', $slug)->where('is_active', true)->firstOrFail();

            $demoData = [
                'personal' => [
                    'full_name' => 'Sarah Johnson',
                    'title' => 'Senior Product Designer',
                    'email' => 'sarah.johnson@email.com',
                    'phone' => '+1 (555) 987-6543',
                    'location' => 'San Francisco, CA',
                    'summary' => 'Creative and detail-oriented product designer with 8+ years of experience crafting intuitive digital experiences. Passionate about user-centered design, design systems, and bridging the gap between business goals and user needs.',
                    'bio' => 'Creative and detail-oriented product designer with 8+ years of experience crafting intuitive digital experiences. Passionate about user-centered design, design systems, and bridging the gap between business goals and user needs.',
                    'linkedin' => 'linkedin.com/in/sarahjohnson',
                    'github' => 'github.com/sarahj',
                    'website' => 'sarahjohnson.design',
                ],
                'skills' => [
                    ['name' => 'UI/UX Design', 'level' => 95],
                    ['name' => 'Figma', 'level' => 92],
                    ['name' => 'Design Systems', 'level' => 88],
                    ['name' => 'Prototyping', 'level' => 90],
                    ['name' => 'User Research', 'level' => 85],
                    ['name' => 'HTML/CSS', 'level' => 82],
                    ['name' => 'JavaScript', 'level' => 65],
                    ['name' => 'Motion Design', 'level' => 75],
                ],
                'experience' => [
                    [
                        'company' => 'TechCorp Inc.',
                        'position' => 'Senior Product Designer',
                        'start_date' => '2021-03',
                        'end_date' => null,
                        'location' => 'San Francisco, CA',
                        'description' => 'Leading the design team in creating next-generation SaaS products used by 2M+ active users worldwide.',
                        'achievements' => [
                            'Redesigned the core platform experience, increasing user engagement by 34%',
                            'Built and maintained a design system serving 12 product teams',
                            'Mentored 4 junior designers and established design critique processes',
                        ],
                    ],
                    [
                        'company' => 'DesignStudio Co.',
                        'position' => 'Product Designer',
                        'start_date' => '2018-06',
                        'end_date' => '2021-02',
                        'location' => 'New York, NY',
                        'description' => 'Designed end-to-end user experiences for mobile and web applications across fintech and e-commerce domains.',
                        'achievements' => [
                            'Led the redesign of a payment flow that reduced drop-off by 28%',
                            'Conducted 50+ user interviews to inform product strategy',
                            'Collaborated with engineering to implement pixel-perfect interfaces',
                        ],
                    ],
                ],
                'education' => [
                    [
                        'institution' => 'Rhode Island School of Design',
                        'degree' => 'Bachelor of Fine Arts',
                        'field_of_study' => 'Graphic Design',
                        'start_date' => '2013-09',
                        'end_date' => '2017-05',
                    ],
                ],
                'projects' => [
                    [
                        'name' => 'DesignOps Dashboard',
                        'description' => 'An internal tool for tracking design system adoption, component usage, and team velocity across multiple product squads.',
                        'url' => '',
                        'role' => 'Lead Designer',
                        'technologies' => ['Figma', 'React', 'D3.js'],
                    ],
                    [
                        'name' => 'Mobile Banking App',
                        'description' => 'Complete redesign of a mobile banking experience serving 500K+ users with focus on accessibility and simplicity.',
                        'url' => '',
                        'role' => 'UX Lead',
                        'technologies' => ['Figma', 'Swift', 'Prototype'],
                    ],
                ],
                'certifications' => [
                    [
                        'name' => 'Google UX Design Professional',
                        'issuer' => 'Google',
                        'date' => '2022-08',
                    ],
                ],
                'languages' => [
                    ['name' => 'English', 'proficiency' => 'Native'],
                    ['name' => 'Spanish', 'proficiency' => 'Conversational'],
                    ['name' => 'Japanese', 'proficiency' => 'Basic'],
                ],
            ];

            $renderer = app(\App\Services\Cv\CvRenderingService::class);
            $html = $renderer->render($template, $demoData, []);
            return response($html)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('X-Frame-Options', 'ALLOWALL')
                ->header('Content-Security-Policy', 'frame-ancestors *')
                ->header('Access-Control-Allow-Origin', '*');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response("Template not found.", 404);
        } catch (\Exception $e) {
            Log::error("Template Demo Preview Crash: " . $e->getMessage());
            return response("Failed to render demo.", 500);
        }
    }

    /**
     * Render a live preview of a template with the user's actual CV profile data.
     * Requires auth — used by the inline editor to show real-time previews.
     */
    public function livePreview(Request $request, $slug)
    {
        try {
            $template = CvTemplate::where('slug', $slug)->first();
            if (!$template) {
                $template = new CvTemplate();
                $template->slug = $slug;
                $template->name = Str::headline($slug);
            }

            $user = Auth::user();
            $profileData = $user ? CandidateData::where('user_id', $user->id)->first() : null;

            $data = [];
            if ($profileData) {
                $data = [
                    'personal' => $profileData->personal_info ?? [],
                    'skills' => $profileData->skills ?? [],
                    'experience' => $profileData->experience ?? [],
                    'education' => $profileData->education ?? [],
                    'projects' => $profileData->projects ?? [],
                    'social_links' => $profileData->social_links ?? [],
                    'certifications' => $profileData->certifications ?? [],
                    'languages' => $profileData->languages ?? [],
                    'awards' => $profileData->awards ?? [],
                    'hobbies' => $profileData->hobbies ?? [],
                    'references' => $profileData->references ?? [],
                    'training' => $profileData->training ?? [],
                ];
            }

            $editorData = $request->input('data') ?? $request->all();
            if (!empty($editorData) && is_array($editorData)) {
                $data = array_merge($data, $editorData);
                if (isset($editorData['personal_info'])) $data['personal'] = $editorData['personal_info'];
                if (isset($editorData['work_experience'])) $data['experience'] = $editorData['work_experience'];
                if (isset($editorData['experiences'])) $data['experience'] = $editorData['experiences'];
                if (isset($editorData['educations'])) $data['education'] = $editorData['educations'];
                if (isset($editorData['interests'])) $data['hobbies'] = $editorData['interests'];
                if (isset($editorData['achievements'])) $data['awards'] = $editorData['achievements'];
                if (isset($editorData['resume_objective'])) $data['summary'] = is_array($editorData['resume_objective']) ? ($editorData['resume_objective']['description'] ?? '') : $editorData['resume_objective'];
            }

            $renderer = app(\App\Services\Cv\CvRenderingService::class);
            $html = $renderer->render($template, $data, []);

            return response($html)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('X-Frame-Options', 'ALLOWALL')
                ->header('Content-Security-Policy', "frame-ancestors *")
                ->header('Access-Control-Allow-Origin', '*');
        } catch (\Throwable $e) {
            Log::error("Live Preview Crash: " . $e->getMessage());
            return response("Failed to render preview: " . $e->getMessage(), 500);
        }
    }

    /**
     * Save the edited resume data snapshot
     */
    public function update(Request $request, $uuid)
    {
        try {
            $request->validate([
                'title' => 'nullable|string',
                'data_snapshot' => 'required|array',
                'theme_settings' => 'nullable|array',
                'create_version' => 'nullable|boolean'
            ]);

            $resume = Resume::where('uuid', $uuid)->where('user_id', Auth::id())->firstOrFail();

            // 1. Version Archiving Check: if explicit flag is passed, store history state
            if ($request->create_version) {
                Resume::create([
                    'user_id' => Auth::id(),
                    'parent_id' => $resume->id,
                    'title' => 'Version Backup - ' . now()->format('M d, Y h:i A'),
                    'template_slug' => $resume->template_slug,
                    'uuid' => (string) Str::uuid(),
                    'data_snapshot' => $resume->data_snapshot,
                    'theme_settings' => $resume->theme_settings
                ]);
            }

            // 2. Perform main update
            $updateData = [
                'data_snapshot' => $request->data_snapshot
            ];

            if ($request->has('title')) {
                $updateData['title'] = $request->title;
            }
            if ($request->has('theme_settings')) {
                $updateData['theme_settings'] = $request->theme_settings;
            }

            $resume->update($updateData);

            Log::info("Resume {$uuid} updated for user " . Auth::id());
            return response()->json(['status' => true, 'message' => 'Resume saved!', 'data' => $resume]);
        } catch (\Exception $e) {
            Log::error("Resume save error: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to save resume.'
            ], 500);
        }
    }

    /**
     * Duplicate an existing resume
     */
    public function duplicate($uuid)
    {
        try {
            $original = Resume::where('uuid', $uuid)->where('user_id', Auth::id())->firstOrFail();

            $duplicate = Resume::create([
                'user_id' => Auth::id(),
                'title' => $original->title . ' (Copy)',
                'template_slug' => $original->template_slug,
                'uuid' => (string) Str::uuid(),
                'data_snapshot' => $original->data_snapshot,
                'theme_settings' => $original->theme_settings,
                'parent_id' => null, // Copies are top-level independent assets
                'is_public' => false
            ]);

            Log::info("Resume {$uuid} duplicated as {$duplicate->uuid}");
            return response()->json(['status' => true, 'message' => 'Resume duplicated successfully!', 'data' => $duplicate]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to duplicate resume.'], 500);
        }
    }

    /**
     * Retrieve version history list
     */
    public function getVersions($uuid)
    {
        try {
            $resume = Resume::where('uuid', $uuid)->where('user_id', Auth::id())->firstOrFail();
            $versions = $resume->versions()->get();

            return response()->json(['status' => true, 'data' => $versions]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to retrieve version history.'], 500);
        }
    }

    /**
     * Restore old version snapshot
     */
    public function restoreVersion(Request $request, $uuid, $version_id)
    {
        try {
            $resume = Resume::where('uuid', $uuid)->where('user_id', Auth::id())->firstOrFail();
            $version = Resume::where('id', $version_id)->where('parent_id', $resume->id)->firstOrFail();

            // Store current snapshot before reverting so the restore itself is revertable
            Resume::create([
                'user_id' => Auth::id(),
                'parent_id' => $resume->id,
                'title' => 'Backup before restoring old state - ' . now()->format('M d, Y h:i A'),
                'template_slug' => $resume->template_slug,
                'uuid' => (string) Str::uuid(),
                'data_snapshot' => $resume->data_snapshot,
                'theme_settings' => $resume->theme_settings
            ]);

            // Restore
            $resume->update([
                'data_snapshot' => $version->data_snapshot,
                'theme_settings' => $version->theme_settings
            ]);

            return response()->json(['status' => true, 'message' => 'Version restored successfully!', 'data' => $resume]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to restore old version.'], 500);
        }
    }

    /**
     * Update public sharing and password requirements
     */
    public function updateShareSettings(Request $request, $uuid)
    {
        try {
            $request->validate([
                'is_public' => 'required|boolean',
                'password' => 'nullable|string',
                'expires_at' => 'nullable|date'
            ]);

            $resume = Resume::where('uuid', $uuid)->where('user_id', Auth::id())->firstOrFail();

            $updateData = [
                'is_public' => $request->is_public,
                'expires_at' => $request->expires_at
            ];

            if ($request->has('password')) {
                // Store password as plain string or hashed if preferred; storing securely with bcrypt is recommended if password is set
                $updateData['password'] = $request->password ? bcrypt($request->password) : null;
            }

            $resume->update($updateData);

            return response()->json([
                'status' => true, 
                'message' => 'Sharing configurations updated!', 
                'data' => [
                    'is_public' => $resume->is_public,
                    'has_password' => !empty($resume->password),
                    'expires_at' => $resume->expires_at ? $resume->expires_at->toIso8601String() : null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to update sharing.'], 500);
        }
    }

    /**
     * Permanently delete a resume
     */
    public function destroy($uuid)
    {
        try {
            $resume = Resume::where('uuid', $uuid)->where('user_id', Auth::id())->firstOrFail();
            $resume->delete();
            return response()->json(['status' => true, 'message' => 'Resume deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to delete resume.'], 500);
        }
    }

    /**
     * Generate and download the resume as a PDF
     */
    public function download($uuid)
    {
        try {
            $resume = Resume::where('uuid', $uuid)->where('user_id', Auth::id())->firstOrFail();
            $template = CvTemplate::where('slug', $resume->template_slug)->first();

            if (!$template) {
                $template = CvTemplate::where('slug', 'minimalist-free')->first();
            }

            $renderer = app(\App\Services\Cv\CvRenderingService::class);
            $themeSettings = $resume->theme_settings ?? [];

            $html = $renderer->render($template, $resume->data_snapshot ?? [], $themeSettings);

            // Simple layout flag check to support direct HTML download or PDF compilation
            if (request()->query('format') === 'html') {
                return response($html)
                    ->header('Content-Type', 'text/html')
                    ->header('Content-Disposition', 'attachment; filename="' . Str::slug($resume->title) . '.html"');
            }

            // Generate PDF using Browsershot → DomPDF fallback chain
            $pdf = $this->generatePdf($html);
            if ($pdf) {
                Log::info("PDF generated for resume {$uuid}");
                return response($pdf)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="' . Str::slug($resume->title) . '.pdf"');
            }

            // Last resort: return HTML with clear content-type so frontend knows it's not PDF
            return response($html)
                ->header('Content-Type', 'text/html')
                ->header('Content-Disposition', 'attachment; filename="' . Str::slug($resume->title) . '.html"');

        } catch (\Exception $e) {
            Log::error("PDF Download Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to generate PDF.'], 500);
        }
    }

    public function getTemplates()
    {
        // 1. Fetch from the CvTemplate model where they are active
        $templates = CvTemplate::where('is_active', true)->get();

        // 2. Return the data
        return response()->json([
            'status' => true,
            'data' => $templates,
        ]);
    }

    /**
     * Render the public shared CV Resume page with password wall and link expiration validation
     */
    public function renderSharedResume(Request $request, $uuid)
    {
        try {
            $resume = Resume::where('uuid', $uuid)->firstOrFail();

            // 1. Verify sharing state
            if (!$resume->is_public) {
                return response("<div style='padding:40px;text-align:center;font-family:sans-serif;'><h2>🔒 Protected Asset</h2><p>This resume is set to private by the owner.</p></div>", 403);
            }

            // 2. Verify link expiration
            if ($resume->expires_at && $resume->expires_at->isPast()) {
                return response("<div style='padding:40px;text-align:center;font-family:sans-serif;color:#ef4444;'><h2>⏳ Link Expired</h2><p>This shared resume link has expired.</p></div>", 410);
            }

            // 3. Verify password challenge wall
            if (!empty($resume->password)) {
                $sessionKey = 'cv_unlock_' . $resume->id;
                $unlocked = session()->get($sessionKey, false);

                if ($request->isMethod('POST')) {
                    $request->validate(['password' => 'required|string']);
                    if (\Illuminate\Support\Facades\Hash::check($request->password, $resume->password)) {
                        session()->put($sessionKey, true);
                        return redirect()->to(url()->current());
                    } else {
                        $error = "Incorrect password. Access denied.";
                    }
                } else if (!$unlocked) {
                    // Render premium password prompt wall html
                    $errorHtml = isset($error) ? "<p style='color:#ef4444;font-weight:bold;'>{$error}</p>" : "";
                    $csrf = csrf_field();
                    return response("
                        <html>
                        <head>
                            <title>Enter Password to View Resume</title>
                            <script src='https://cdn.tailwindcss.com'></script>
                        </head>
                        <body class='bg-slate-50 flex items-center justify-center min-h-screen p-4'>
                            <div class='bg-white p-8 rounded-2xl shadow-xl max-w-md w-full border text-center space-y-6'>
                                <div class='w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto text-2xl'>🔒</div>
                                <div class='space-y-2'>
                                    <h2 class='text-2xl font-black text-slate-800 tracking-tight'>Password Protected</h2>
                                    <p class='text-sm text-slate-500'>The candidate has password-protected this resume link. Please enter the password to confirm.</p>
                                </div>
                                <form method='POST' action='" . url()->current() . "' class='space-y-4'>
                                    {$csrf}
                                    <input type='password' name='password' placeholder='••••••••' required class='w-full px-4 h-12 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 font-bold text-center' />
                                    {$errorHtml}
                                    <button type='submit' class='w-full h-12 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all shadow-md shadow-blue-500/10'>Confirm & Unlock</button>
                                </form>
                            </div>
                        </body>
                        </html>
                    ");
                }
            }

            // 4. Increment view metrics
            $resume->increment('views_count');

            // 5. Render
            $template = CvTemplate::where('slug', $resume->template_slug)->first();
            if (!$template) {
                $template = CvTemplate::where('slug', 'minimalist-free')->first();
            }

            $renderer = app(\App\Services\Cv\CvRenderingService::class);
            $html = $renderer->render($template, $resume->data_snapshot ?? [], $resume->theme_settings ?? []);

            return response($html);
        } catch (\Exception $e) {
            Log::error("Shared preview failed: " . $e->getMessage());
            return response("Shared preview failed to load.", 500);
        }
    }

    /**
     * AI-powered CV generation from user prompt.
     * Requires ai_cv_builder subscription feature.
     */
    public function generateWithAi(Request $request)
    {
        try {
            $request->validate([
                'prompt' => 'required|string|min:10|max:2000',
            ]);

            $user = Auth::user();

            if (!$user->hasFeature('ai_cv_builder')) {
                return response()->json([
                    'status' => false,
                    'action' => 'upgrade',
                    'message' => 'AI CV Builder requires a paid subscription.',
                    'feature_key' => 'ai_cv_builder',
                ], 403);
            }

            $userPrompt = $request->input('prompt');

            $systemPrompt = "You are an expert professional CV/resume writer. Based on the user's description below, generate a complete, ATS-friendly CV profile in valid JSON format. The JSON must have this exact structure:\n{\n  \"personal_info\": { \"full_name\": \"\", \"title\": \"\", \"email\": \"\", \"phone\": \"\", \"location\": \"\", \"summary\": \"\" },\n  \"experience\": [{ \"company\": \"\", \"position\": \"\", \"start_date\": \"\", \"end_date\": \"\", \"description\": \"\" }],\n  \"education\": [{ \"institution\": \"\", \"degree\": \"\", \"field\": \"\", \"year\": \"\" }],\n  \"skills\": [{ \"name\": \"\", \"level\": \"intermediate\" }],\n  \"certifications\": [],\n  \"languages\": [],\n  \"projects\": [],\n  \"awards\": [],\n  \"hobbies\": [],\n  \"social_links\": {},\n  \"references\": [{ \"name\": \"\", \"designation\": \"\", \"organization\": \"\", \"phone\": \"\", \"email\": \"\" }],\n  \"training\": [{ \"title\": \"\", \"institute\": \"\", \"duration\": \"\" }]\n}\n\nRules:\n- Use the information provided by the user to fill in the fields\n- Write a compelling professional summary (2-3 sentences)\n- For skills, use levels: beginner, intermediate, advanced, expert\n- Dates should be in YYYY-MM format\n- Return ONLY the JSON object, no markdown, no explanation\n- If information is insufficient, create reasonable professional defaults";

            $fullPrompt = $systemPrompt . "\n\nUser description:\n" . $userPrompt;

            try {
                $aiResponse = AiManagerService::ask($fullPrompt, temperature: 0.3, maxTokens: 2000);
                $cleanJson = trim(str_replace(['```json', '```'], '', $aiResponse));
                $generatedCv = json_decode($cleanJson, true);

                if (!$generatedCv || !isset($generatedCv['personal_info'])) {
                    throw new \RuntimeException("AI returned invalid CV structure");
                }
            } catch (\Exception $e) {
                $generatedCv = $this->buildCvFromProfile($user, $userPrompt);
            }

            Log::info("AI CV generated for user {$user->id} with prompt: " . Str::limit($userPrompt, 80));

            return response()->json([
                'status' => true,
                'data' => $generatedCv,
            ]);
        } catch (\Throwable $e) {
            Log::error("AI CV Generation Crash: " . $e->getMessage(), ['user_id' => Auth::id()]);
            return response()->json([
                'status' => false,
                'message' => 'AI generation failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Generate a public shareable link for a resume.
     * Toggles is_public and returns the share URL.
     */
    public function share($uuid)
    {
        try {
            $resume = Resume::where('uuid', $uuid)->where('user_id', Auth::id())->firstOrFail();

            // Enable public sharing if not already
            if (!$resume->is_public) {
                $resume->update(['is_public' => true]);
            }

            $shareUrl = rtrim(config('app.frontend_url', config('app.url')), '/') . "/cv/share/{$resume->uuid}";

            Log::info("Share link generated for resume {$uuid} by user " . Auth::id());

            return response()->json([
                'status' => true,
                'data' => [
                    'share_url' => $shareUrl,
                    'uuid' => $resume->uuid,
                    'is_public' => true,
                ],
                'message' => 'Share link generated successfully.',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status' => false, 'message' => 'Resume not found.'], 404);
        } catch (\Exception $e) {
            Log::error("Share link error: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to generate share link.'
            ], 500);
        }
    }


    /**
     * Download resume as PDF via public UUID (no auth required for shared CVs).
     */
    public function downloadPdf($uuid)
    {
        try {
            $resume = Resume::where('uuid', $uuid)->firstOrFail();

            $userId = Auth::id();
            if (!$userId && request()->bearerToken()) {
                $token = \Laravel\Sanctum\PersonalAccessToken::findToken(request()->bearerToken());
                if ($token) $userId = $token->tokenable_id;
            }
            $isOwner = $userId && $resume->user_id == $userId;

            if (!$resume->is_public && !$isOwner) {
                return response()->json(['status' => false, 'message' => 'This resume is not publicly available.'], 403);
            }

            if (!$isOwner && $resume->expires_at && $resume->expires_at->isPast()) {
                return response()->json(['status' => false, 'message' => 'This share link has expired.'], 410);
            }

            $template = CvTemplate::where('slug', $resume->template_slug)->first();
            if (!$template) {
                $template = CvTemplate::where('slug', 'ats-professional')->first();
            }
            if (!$template) {
                $template = CvTemplate::where('slug', 'minimalist-free')->first();
            }

            $renderer = app(\App\Services\Cv\CvRenderingService::class);
            $themeSettings = $resume->theme_settings ?? [];
            $html = $renderer->render($template, $resume->data_snapshot ?? [], $themeSettings);

            $pdf = $this->generatePdf($html);
            if ($pdf) {
                Log::info("PDF downloaded for resume {$uuid}");
                return response($pdf)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="' . Str::slug($resume->title) . '.pdf"');
            }

            return response($html)
                ->header('Content-Type', 'text/html')
                ->header('Content-Disposition', 'attachment; filename="' . Str::slug($resume->title) . '.html"');
        } catch (\Exception $e) {
            Log::error("PDF Download Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to generate PDF.'], 500);
        }
    }

    /**
     * Build CV data from user's real profile as fallback when AI is unavailable.
     */
    private function buildCvFromProfile($user, string $prompt): array
    {
        $profile = $user->profile;
        $skills = $profile ? (array)($profile->skills ?? []) : [];
        $experiences = $profile && method_exists($profile, 'workExperiences') ? $profile->workExperiences->toArray() : [];
        $educations = $profile && method_exists($profile, 'educations') ? $profile->educations->toArray() : [];

        return [
            'personal_info' => [
                'full_name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'title' => $profile->current_position ?? '',
                'phone' => $profile->phone ?? '',
                'location' => $profile->city ?? '',
                'summary' => $profile->bio ?? '',
            ],
            'experience' => array_map(function ($exp) {
                return [
                    'company' => $exp['company'] ?? '',
                    'position' => $exp['position'] ?? $exp['title'] ?? '',
                    'start_date' => $exp['start_date'] ?? '',
                    'end_date' => $exp['end_date'] ?? null,
                    'description' => $exp['description'] ?? '',
                ];
            }, $experiences),
            'education' => array_map(function ($edu) {
                return [
                    'institution' => $edu['institution'] ?? $edu['school'] ?? '',
                    'degree' => $edu['degree'] ?? '',
                    'field_of_study' => $edu['field_of_study'] ?? '',
                    'start_date' => $edu['start_date'] ?? '',
                    'end_date' => $edu['end_date'] ?? '',
                ];
            }, $educations),
            'skills' => $skills,
            'certifications' => $profile->certifications ?? [],
            'languages' => $profile->languages ?? [],
            'awards' => $profile->awards ?? [],
            'hobbies' => $profile->hobbies ?? [],
            'references' => $profile->references ?? [],
            'training' => $profile->training ?? [],
            'projects' => $profile->projects ?? [],
            'social_links' => array_filter([
                'linkedin' => $profile->linkedin ?? '',
                'github' => $profile->github ?? '',
                'portfolio' => $profile->portfolio_url ?? $profile->website ?? '',
                'twitter' => $profile->twitter_url ?? '',
                'facebook' => $profile->facebook_url ?? '',
            ], fn($v) => !empty($v)),
        ];
    }

    /**
     * Try to generate a real PDF from HTML.
     * Tries Browsershot first, then falls back to DomPDF.
     * Returns raw PDF bytes on success, null on failure.
     */
    private function generatePdf(string $html): ?string
    {
        // 1. Try Browsershot via local Chrome/Chromium (best CSS support)
        if (class_exists(Browsershot::class)) {
            $chromePaths = [
                'C:\Program Files\Google\Chrome\Application\chrome.exe',
                'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
                config('services.chrome_binary_path', env('CHROME_BINARY_PATH', '')),
            ];
            $chromePath = null;
            foreach ($chromePaths as $path) {
                if ($path && file_exists($path)) {
                    $chromePath = $path;
                    break;
                }
            }

            if ($chromePath) {
                try {
                    $pdf = Browsershot::html($html)
                        ->setChromePath($chromePath)
                        ->noSandbox()
                        ->timeout(20)
                        ->format('A4')
                        ->margins(10, 10, 10, 10)
                        ->pdf();

                    if ($pdf && strlen($pdf) > 500) {
                        return $pdf;
                    }
                } catch (\Throwable $e) {
                    Log::warning("Browsershot failed: " . $e->getMessage());
                }
            }
        }

        // 2. Fallback: DomPDF (basic CSS, but produces valid PDF)
        try {
            $pdf = Pdf::loadHtml($html)
                ->setPaper('a4', 'portrait')
                ->setOption('isRemoteEnabled', false)
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('defaultFont', 'sans-serif')
                ->output();

            if ($pdf && strlen($pdf) > 500) {
                return $pdf;
            }
        } catch (\Throwable $e) {
            Log::warning("DomPDF failed: " . $e->getMessage());
        }

        return null;
    }
}
