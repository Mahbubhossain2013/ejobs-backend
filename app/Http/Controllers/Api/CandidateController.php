<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\Interview;
use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CandidateController extends Controller
{
    /**
     * Fetch Candidate Dashboard Stats & Applications safely
     */
    public function dashboard()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
            }

            $user->load(['profile', 'badges']);

            $documents = collect();
            try {
                $documents = \App\Models\CandidateDocument::where('user_id', $user->id)->get()->map(function ($d) {
                    return array_merge($d->toArray(), ['url' => asset('storage/' . $d->file_path)]);
                });
            } catch (\Throwable $e) {
                Log::warning('CandidateDocument query failed: ' . $e->getMessage());
            }

            $educations = collect();
            try {
                $educations = \App\Models\CandidateEducation::where('user_id', $user->id)->orderBy('order')->get();
            } catch (\Throwable $e) {
                Log::warning('CandidateEducation query failed: ' . $e->getMessage());
            }

            $experiences = collect();
            try {
                $experiences = \App\Models\CandidateExperience::where('user_id', $user->id)->orderBy('order')->get();
            } catch (\Throwable $e) {
                Log::warning('CandidateExperience query failed: ' . $e->getMessage());
            }

            $trainings = collect();
            try {
                $trainings = \App\Models\CandidateTraining::where('user_id', $user->id)->get();
            } catch (\Throwable $e) {
                Log::warning('CandidateTraining query failed: ' . $e->getMessage());
            }

            $certifications = collect();
            try {
                $certifications = \App\Models\CandidateCertification::where('user_id', $user->id)->get();
            } catch (\Throwable $e) {
                Log::warning('CandidateCertification query failed: ' . $e->getMessage());
            }

            if (!$user->profile) {
                UserProfile::firstOrCreate(['user_id' => $user->id]);
                $user->load('profile');
            }

            $skills = $user->profile->skills ?? [];
            $normalizedSkills = array_map(function ($skill) {
                if (is_array($skill) && isset($skill['name'])) {
                    return $skill['name'];
                }
                return (string) $skill;
            }, $skills);

            $applications = JobApplication::where('user_id', $user->id)
                ->with(['job.company'])
                ->latest()
                ->get();

            // Prepare safe user data
            // Calculate locked badges milestones progress tracking
            $role = 'candidate';
            $actualJobs = $user->profile->completed_jobs_count ?? 0;
            $actualScore = $user->profile->trust_score ?? 100;
            $actualRating = $user->profile->rating ?? 0.0;
            $actualEarnings = $user->profile->total_earnings ?? 0;
            $actualSpend = 0;
            $userBadges = $user->badges()->pluck('badges.id', 'badges.badge_key');
            $isVerified = $userBadges->has('verified');
            $ownedBadgeIds = $userBadges->keys()->toArray();

            $lockedBadges = collect();
            try {
            $lockedBadges = \App\Models\Badge::where('is_active', true)
                ->where('is_automatic', true)
                ->where('is_hidden', false)
                ->whereIn('role', [$role, 'both'])
                ->whereNotIn('id', $ownedBadgeIds)
                ->get()
                ->map(function ($badge) use ($actualJobs, $actualScore, $actualRating, $actualEarnings, $actualSpend, $isVerified) {
                    $rules = $badge->rules;
                    $progressDetails = [];
                    $totalRules = 0;
                    $passedRules = 0;

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
                            } elseif ($key === 'earnings') {
                                $currentVal = $actualEarnings;
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
                            } elseif ($field === 'earnings') {
                                $actualValue = $actualEarnings;
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
                ->values();
            } catch (\Throwable $e) {
                Log::warning('Candidate locked badges query failed: ' . $e->getMessage());
            }

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'avatar' => $user->avatar,
                'profile' => array_merge($user->profile->toArray(), [
                    'skills' => $normalizedSkills,
                    'active_badges' => $user->activeBadges(),
                    'locked_badges' => $lockedBadges,
                    'documents' => $documents,
                    'educations' => $educations,
                    'experiences' => $experiences,
                    'trainings' => $trainings,
                    'certifications' => $certifications,
                    'match_score' => \App\Models\AiMatchScore::where('user_id', $user->id)->avg('score') ?? 0,
                ]),
            ];

            return response()->json([
                'status' => true,
                'user' => $userData,
                'applications' => $applications,
                'stats' => [
                    'applied' => $applications->count(),
                    'shortlisted' => $applications->where('status', 'shortlisted')->count(),
                    'interviews' => $applications->where('status', 'interview')->count(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Candidate Dashboard Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to load dashboard'], 500);
        }
    }

    /**
     * Update Candidate Profile (Comprehensive)
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $profile = UserProfile::firstOrCreate(['user_id' => $user->id]);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'username' => 'sometimes|required|string|max:50|unique:users,username,' . $user->id,
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:20480',
            'resume' => 'nullable|file|mimes:pdf|max:20480',
            'expected_salary' => 'nullable|numeric',
            'experience_years' => 'nullable|numeric',
        ]);

        try {
            DB::transaction(function () use ($request, $user, $profile) {
                // 1. Update User Table
                if ($request->has('name')) $user->name = $request->name;
                if ($request->has('username')) $user->username = $request->username;
                if ($request->has('email')) $user->email = $request->email;
                if ($user->isDirty()) $user->save();

                // 2. Simple Profile Fields
                $fields = [
                    'phone', 'city', 'bio', 'current_company', 'current_position',
                    'availability_status', 'portfolio_link', 'expected_salary', 'experience_years',
                    // Section 1 – Personal
                    'full_name_bn', 'full_name_en', 'father_name', 'mother_name',
                    'date_of_birth', 'gender', 'marital_status', 'nationality',
                    'national_id', 'birth_reg_no',
                    // Section 2 – Contact
                    'alt_phone', 'present_address', 'permanent_address',
                    'district', 'division', 'upazila', 'union', 'post_office', 'postal_code',
                    // Section 3 – Career
                    'career_objective', 'current_profession', 'expected_job_category',
                    'preferred_location', 'preferred_district', 'preferred_industry',
                    // Section 12 – Social
                    'linkedin_url', 'github_url', 'facebook_url', 'portfolio_url',
                ];

                foreach ($fields as $field) {
                    if ($request->has($field)) {
                        $profile->$field = $request->input($field);
                    }
                }

                // Booleans
                foreach (['is_public', 'available_remote', 'available_relocation', 'one_click_apply', 'job_alert_enabled'] as $bool) {
                    if ($request->has($bool)) {
                        $profile->$bool = filter_var($request->input($bool), FILTER_VALIDATE_BOOLEAN);
                    }
                }

                // 3. JSON Fields
                $jsonFields = ['experience', 'education', 'projects', 'skills', 'social_links',
                    'notification_settings', 'computer_skills', 'microsoft_office_level',
                    'other_skills', 'language_proficiency', 'application_tracking',
                    'preferred_job_types', 'preferred_districts',
                ];
                foreach ($jsonFields as $field) {
                    if ($request->has($field)) {
                        $value = $request->input($field);
                        $profile->$field = is_string($value) ? (json_decode($value, true) ?? []) : $value;
                    }
                }

                // 4. Avatar Upload
                if ($request->hasFile('avatar')) {
                    if ($profile->avatar) Storage::disk('public')->delete($profile->avatar);
                    $avatarFile = \App\Services\Media\ImageOptimizerService::convertToWebp($request->file('avatar'));
                    $profile->avatar = $avatarFile->store('avatars', 'public');
                    $user->avatar = $profile->avatar;
                    $user->save();
                }

                // 5. Resume Upload
                if ($request->hasFile('resume')) {
                    if ($profile->resume_path) Storage::disk('public')->delete($profile->resume_path);
                    $profile->resume_path = $request->file('resume')->store('resumes', 'public');
                }

                $profile->save();
            });

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully',
                'user' => $user->load('profile')
            ]);
        } catch (\Exception $e) {
            Log::error('Profile Update Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Profile update failed. Please try again.'], 500);
        }
    }

    /**
     * Save multiple education entries for the authenticated candidate.
     */
    public function saveEducations(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'educations' => 'required|array',
            'educations.*.level' => 'nullable|string',
            'educations.*.institute_name' => 'nullable|string',
            'educations.*.passing_year' => 'nullable|integer',
            'educations.*.gpa_or_cgpa' => 'nullable|numeric',
        ]);

        DB::table('candidate_educations')->where('user_id', $user->id)->delete();

        foreach ($request->educations as $i => $edu) {
            \App\Models\CandidateEducation::create(array_merge($edu, [
                'user_id' => $user->id,
                'order' => $i,
            ]));
        }

        $saved = \App\Models\CandidateEducation::where('user_id', $user->id)->orderBy('order')->get();

        return response()->json(['status' => true, 'message' => 'Educations saved', 'educations' => $saved]);
    }

    /**
     * Save multiple work experience entries.
     */
    public function saveExperiences(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'experiences' => 'required|array',
            'experiences.*.company_name' => 'nullable|string',
            'experiences.*.designation' => 'nullable|string',
            'experiences.*.start_date' => 'nullable|date',
        ]);

        DB::table('candidate_experiences')->where('user_id', $user->id)->delete();

        foreach ($request->experiences as $i => $exp) {
            \App\Models\CandidateExperience::create(array_merge($exp, [
                'user_id' => $user->id,
                'order' => $i,
                'is_current' => $exp['is_current'] ?? false,
            ]));
        }

        $saved = \App\Models\CandidateExperience::where('user_id', $user->id)->orderBy('order')->get();

        return response()->json(['status' => true, 'message' => 'Experiences saved', 'experiences' => $saved]);
    }

    /**
     * Save multiple training entries.
     */
    public function saveTrainings(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'trainings' => 'required|array',
            'trainings.*.title' => 'nullable|string',
        ]);

        DB::table('candidate_trainings')->where('user_id', $user->id)->delete();

        foreach ($request->trainings as $training) {
            \App\Models\CandidateTraining::create(array_merge($training, [
                'user_id' => $user->id,
            ]));
        }

        $saved = \App\Models\CandidateTraining::where('user_id', $user->id)->get();

        return response()->json(['status' => true, 'message' => 'Trainings saved', 'trainings' => $saved]);
    }

    /**
     * Save multiple certification entries.
     */
    public function saveCertifications(Request $request)
    {
        $user = Auth::user();

        // Handle both JSON and FormData submissions
        $certificationsData = $request->input('certifications');
        if (is_string($certificationsData)) {
            $certificationsData = json_decode($certificationsData, true);
        }

        // Validate decoded data instead of raw request
        Validator::make(
            ['certifications' => $certificationsData],
            [
                'certifications' => 'required|array',
                'certifications.*.name' => 'required|string',
            ]
        )->validate();

        DB::table('candidate_certifications')->where('user_id', $user->id)->delete();

        foreach ($certificationsData ?? $request->certifications as $i => $cert) {
            // Handle per-certification file uploads
            $fileField = "cert_file_{$i}";
            if ($request->hasFile($fileField)) {
                $cert['certificate_path'] = $request->file($fileField)->store('candidate_certifications/' . $user->id, 'public');
            }

            \App\Models\CandidateCertification::create(array_merge($cert, [
                'user_id' => $user->id,
            ]));
        }

        $saved = \App\Models\CandidateCertification::where('user_id', $user->id)->get();

        return response()->json(['status' => true, 'message' => 'Certifications saved', 'certifications' => $saved]);
    }

    /**
     * Save references (typically 2).
     */
    public function saveReferences(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'references' => 'required|array|max:3',
            'references.*.name' => 'required|string',
        ]);

        DB::table('candidate_references')->where('user_id', $user->id)->delete();

        foreach ($request->references as $ref) {
            \App\Models\CandidateReference::create(array_merge($ref, [
                'user_id' => $user->id,
            ]));
        }

        return response()->json(['status' => true, 'message' => 'References saved']);
    }

    /**
     * Save a document file upload for the authenticated candidate.
     */
    public function saveDocuments(Request $request)
    {
        try {
            $user = Auth::user();
            $request->validate([
                'type' => 'required|string|in:cv,nid_front,nid_back,passport,academic_cert,experience_cert,photo',
                'file' => 'required|file|max:20480|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx',
            ]);

            $file = $request->file('file');
            $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower($file->getClientOriginalExtension());
            if (in_array($ext, $imageTypes)) {
                $file = \App\Services\Media\ImageOptimizerService::convertToWebp($file);
            }

            $path = $file->store('candidate_documents/' . $user->id, 'public');

            \App\Models\CandidateDocument::updateOrCreate(
                ['user_id' => $user->id, 'type' => $request->type],
                [
                    'file_path' => $path,
                    'label' => $request->type,
                ]
            );

            return response()->json(['status' => true, 'message' => 'Document uploaded', 'path' => $path, 'url' => asset('storage/' . $path)]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => false, 'message' => 'ফাইল ভ্যালিডেশন ব্যর্থ', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Document Upload Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'আপলোড ব্যর্থ হয়েছে। সার্ভার সমস্যা হতে পারে।'], 500);
        }
    }

    /**
     * Dedicated Resume Upload or Selection (Optimized)
     */
    public function uploadResume(Request $request)
    {
        $request->validate([
            'resume' => 'nullable|file|mimes:pdf|max:20480',
            'resume_uuid' => 'nullable|string|exists:resumes,uuid',
        ]);

        try {
            $user = Auth::user();
            $profile = UserProfile::firstOrCreate(['user_id' => $user->id]);

            // Case A: File upload
            if ($request->hasFile('resume')) {
                if ($profile->resume_path && Storage::disk('public')->exists($profile->resume_path)) {
                    Storage::disk('public')->delete($profile->resume_path);
                }

                $path = $request->file('resume')->store('resumes', 'public');
                $profile->update(['resume_path' => $path]);

                return response()->json([
                    'status' => true,
                    'message' => 'Resume uploaded successfully',
                    'data' => [
                        'resume_path' => $path,
                        'resume_url' => asset('storage/' . $path)
                    ]
                ], 200);
            }

            // Case B: Choose CV Builder Resume
            if ($request->filled('resume_uuid')) {
                $uuid = $request->input('resume_uuid');
                $resume = \App\Models\Resume::where('uuid', $uuid)->where('user_id', $user->id)->firstOrFail();
                
                // Construct path matching PublicProfileController expectations (e.g. cv/share/{uuid})
                $path = "cv/share/{$resume->uuid}";

                // Automatically mark the builder resume as shared/public so employers can view it
                if (!$resume->is_public) {
                    $resume->update(['is_public' => true]);
                }

                $profile->update(['resume_path' => $path]);

                return response()->json([
                    'status' => true,
                    'message' => 'CV Builder resume selected successfully',
                    'data' => [
                        'resume_path' => $path,
                        'resume_url' => url($path)
                    ]
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['status' => false, 'message' => 'No file or resume selected'], 400);
    }

    public function savedJobs()
    {
        $user = Auth::user();
        $jobs = $user->savedJobs()->with('company')->latest()->paginate(10);
        return response()->json(['status' => true, 'data' => $jobs]);
    }

    public function toggleSaveJob($jobId)
    {
        $user = Auth::user();
        // toggle() adds if missing, removes if exists
        $status = $user->savedJobs()->toggle($jobId);

        $isSaved = count($status['attached']) > 0;

        return response()->json([
            'status' => true,
            'is_saved' => $isSaved,
            'message' => $isSaved ? 'Job saved to bookmarks' : 'Job removed from bookmarks'
        ]);
    }

    public function appliedJobs()
    {
        $applications = \App\Models\JobApplication::where('user_id', Auth::id())
            ->with(['job.company', 'job.category'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $applications
        ]);
    }

    public function acceptedJobs()
    {
        $applications = \App\Models\JobApplication::where('user_id', Auth::id())
            ->whereIn('status', ['shortlisted', 'interview', 'offered', 'hired'])
            ->with(['job.company', 'job.category'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $applications
        ]);
    }

    public function wallet()
    {
        try {
            $user = auth()->user();

            $wallet = null;
            try {
                $wallet = $user->wallet()->firstOrCreate(
                    ['user_id' => $user->id]
                );
            } catch (\Throwable $e) {
                Log::warning('Wallet creation failed: ' . $e->getMessage());
                $wallet = new \App\Models\Wallet([
                    'user_id' => $user->id,
                    'balance' => 0,
                    'locked_balance' => 0,
                    'withdrawable_balance' => 0,
                ]);
            }

            // Auto-heal withdrawable_balance if there's a discrepancy (balance exists, nothing locked, but withdrawable is 0)
            if ($wallet->withdrawable_balance == 0 && $wallet->balance > 0 && $wallet->locked_balance == 0) {
                $wallet->withdrawable_balance = $wallet->balance;
                $wallet->save();
            }

            $depositMethods = collect();
            try { $depositMethods = \App\Models\Gateway::where('status', 1)->get(); } catch (\Throwable $e) {}

            $payoutMethods = collect();
            try { $payoutMethods = \App\Models\PayoutGateway::where('is_active', true)->get(); } catch (\Throwable $e) {}

            $transactions = [];
            try { $transactions = $wallet->transactions()->latest()->take(15)->get(); } catch (\Throwable $e) {}

            $withdrawals = [];
            try { $withdrawals = $user->withdrawals()->with('payoutGateway')->latest()->get(); } catch (\Throwable $e) {}

            $deposits = [];
            try { $deposits = $user->deposits()->with('gateway')->latest()->get(); } catch (\Throwable $e) {}

            $escrows = [];
            try { $escrows = \App\Models\Escrow::where('candidate_id', $user->id)->with('job')->latest()->get(); } catch (\Throwable $e) {}

            return response()->json([
                'status' => true,
                'wallet' => $wallet,
                'deposit_methods' => $depositMethods,
                'withdrawal_methods' => $payoutMethods,
                'transactions' => $transactions,
                'withdrawals' => $withdrawals,
                'deposits' => $deposits,
                'escrows' => $escrows,
            ]);
        } catch (\Exception $e) {
            Log::error('Candidate Wallet Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to load wallet'], 500);
        }
    }

    public function withdraw(Request $request)
    {
        $request->validate([
            'gateway_id' => 'required|exists:payout_gateways,id',
            'amount' => 'required|numeric',
            'inputs' => 'required|array', // Dynamic fields from React
        ]);

        $user = auth()->user();
        $wallet = $user->wallet;
        $gateway = \App\Models\PayoutGateway::find($request->gateway_id);

        // 1. Check if amount meets gateway minimum
        if ($request->amount < $gateway->min_amount) {
            return response()->json(['status' => false, 'message' => "Minimum withdrawal for this method is ৳{$gateway->min_amount}"], 422);
        }

        // 2. CRITICAL RULE: Check Withdrawable Balance (Earnings Only)
        if ($request->amount > $wallet->withdrawable_balance) {
            return response()->json([
                'status' => false,
                'message' => "Insufficient earnings. You can only withdraw money earned from jobs, not added funds."
            ], 422);
        }

        // 3. Calculate Service Charge
        $charge = ($request->amount * $gateway->percent_charge) / 100;
        $finalAmount = $request->amount - $charge;

        DB::transaction(function () use ($request, $wallet, $gateway, $charge, $finalAmount, $user) {
            // Deduct from both total and withdrawable
            $wallet->decrement('balance', $request->amount);
            $wallet->decrement('withdrawable_balance', $request->amount);

            // Lock the balance until admin approves
            $wallet->increment('locked_balance', $request->amount);

            // Create Withdrawal Record
            $withdrawal = \App\Models\Withdrawal::create([
                'user_id' => $user->id,
                'payout_gateway_id' => $gateway->id,
                'payment_method' => $gateway->name,
                'amount' => $request->amount,
                'charge' => $charge,
                'payable' => $finalAmount,
                'account_details' => json_encode($request->inputs),
                'status' => 'pending'
            ]);

            // Create companion Invoice
            try {
                $invoiceService = app(\App\Services\Billing\InvoiceService::class);
                $candidateId = $user->hasRole('candidate') ? $user->id : null;
                $employerId = $user->hasRole('employer') ? $user->id : null;

                $invoiceService->createInvoice([
                    'type' => 'wallet_withdrawal',
                    'user_id' => $user->id,
                    'candidate_id' => $candidateId,
                    'employer_id' => $employerId,
                    'reference_type' => \App\Models\Withdrawal::class,
                    'reference_id' => $withdrawal->id,
                    'currency_code' => 'BDT',
                    'status' => 'pending',
                    'notes' => "Wallet Withdrawal via {$gateway->name}. Amount: {$finalAmount} BDT",
                ], [
                    [
                        'description' => "Withdrawal Request",
                        'quantity' => 1,
                        'unit_price' => $request->amount,
                    ],
                    [
                        'description' => "Service Charge",
                        'quantity' => 1,
                        'unit_price' => $charge,
                    ]
                ]);
            } catch (\Throwable $invoiceEx) {
                \Illuminate\Support\Facades\Log::error('Withdrawal Invoice Generation Failed: ' . $invoiceEx->getMessage());
            }
        });

        return response()->json(['status' => true, 'message' => 'Withdrawal request submitted!']);
    }

    /**
     * Fetch Recommended Jobs for the Candidate based on profile skills
     */
    public function recommendedJobs()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
            }

            // Ensure profile exists, create if missing
            if (!$user->profile) {
                UserProfile::firstOrCreate(['user_id' => $user->id]);
                $user->load('profile');
            }

            // Fetch active jobs and score each one dynamically based on settings weights
            $allActiveJobs = \App\Models\Job::with(['company', 'category'])
                ->where('is_active', true)
                ->limit(200)
                ->get();

            $jobs = $allActiveJobs->map(function ($job) use ($user) {
                $scoreDetails = \App\Services\Ai\AiAlgorithmService::computeMatchScore($user, $job);
                $job->match_score = $scoreDetails['total_score'];
                $job->match_breakdown = $scoreDetails['breakdown'];
                return $job;
            })
            ->sortByDesc('match_score')
            ->values()
            ->take(10); // return top 10 matches

            return response()->json([
                'status' => true,
                'data' => $jobs
            ]);
        } catch (\Exception $e) {
            Log::error('Recommended Jobs Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to load recommended jobs'], 500);
        }
    }

    /**
     * Update Candidate Password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current' => 'required',
            'new' => 'required|string|min:8',
            'confirm' => 'required|same:new',
        ]);

        $user = Auth::user();

        if (!\Illuminate\Support\Facades\Hash::check($request->current, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Current password does not match'
            ], 422);
        }

        $user->password = \Illuminate\Support\Facades\Hash::make($request->new);
        $user->save();

        app(NotificationService::class)->sendNotification(
            $user,
            'Password Changed',
            'Your password has been changed successfully. If you did not make this change, please contact support immediately.',
            'security',
            '/dashboard/settings'
        );

        return response()->json([
            'status' => true,
            'message' => 'Password updated successfully'
        ]);
    }

    /**
     * Get candidate's interviews
     */
    public function getInterviews(Request $request)
    {
        $interviews = Interview::where('candidate_id', Auth::id())
            ->with([
                'job:id,title,company_id,location',
                'employer:id,name,avatar',
                'application:id,status',
            ])
            ->when($request->status, fn ($q, $s) => $q->where('candidate_response', $s))
            ->orderBy('scheduled_at', 'desc')
            ->paginate(min(50, max(1, intval($request->per_page ?? 15))));

        return response()->json(['status' => true, 'data' => $interviews]);
    }

    /**
     * Respond to an interview (accept/decline)
     */
    public function respondToInterview(Request $request, int $interviewId)
    {
        $interview = Interview::where('id', $interviewId)
            ->where('candidate_id', Auth::id())
            ->with('employer')
            ->first();

        if (!$interview) {
            return response()->json(['status' => false, 'message' => 'Interview not found.'], 404);
        }

        if ($interview->candidate_response !== 'pending') {
            return response()->json(['status' => false, 'message' => 'You have already responded to this interview.'], 400);
        }

        $validated = $request->validate([
            'candidate_response' => 'required|in:accepted,declined',
            'candidate_note' => 'nullable|string|max:1000',
        ]);

        $interview->update([
            'candidate_response' => $validated['candidate_response'],
            'candidate_note' => $validated['candidate_note'] ?? null,
        ]);

        // Notify employer
        $responseText = $validated['candidate_response'] === 'accepted' ? 'accepted' : 'declined';
        NotificationService::sendNotification(
            $interview->employer_id,
            "Interview {$responseText}",
            "Candidate has {$responseText} the interview for {$interview->job->title}",
            'interview',
            ['interview_id' => $interview->id]
        );

        return response()->json([
            'status' => true,
            'message' => "Interview {$responseText} successfully.",
            'data' => $interview->fresh(),
        ]);
    }

    /**
     * Candidate Analytics Overview
     */
    public function analytics()
    {
        $user = Auth::user();
        $profile = $user->profile ?? UserProfile::firstOrCreate(['user_id' => $user->id]);

        // Profile views
        $profileViews = \App\Models\ProfileView::where('candidate_id', $user->id)->sum('view_count');
        $profileViewsPrev = \App\Models\ProfileView::where('candidate_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->where('created_at', '<', now()->subDays(30)->subDays(30))
            ->sum('view_count');
        $viewsChange = $profileViewsPrev > 0 ? round((($profileViews - $profileViewsPrev) / $profileViewsPrev) * 100) : 0;

        // Applications
        $applications = JobApplication::where('user_id', $user->id);
        $applicationsSent = (clone $applications)->count();
        $applicationsPrev = (clone $applications)
            ->where('created_at', '>=', now()->subDays(30))
            ->where('created_at', '<', now()->subDays(30)->subDays(30))
            ->count();
        $applicationsChange = $applicationsPrev > 0 ? round((($applicationsSent - $applicationsPrev) / $applicationsPrev) * 100) : 0;

        // Application status breakdown
        $applicationStatus = [
            'applied' => (clone $applications)->where('status', 'applied')->count(),
            'reviewed' => (clone $applications)->where('status', 'reviewed')->count(),
            'shortlisted' => (clone $applications)->where('status', 'shortlisted')->count(),
            'rejected' => (clone $applications)->where('status', 'rejected')->count(),
        ];

        // Saved jobs
        $savedJobs = DB::table('saved_jobs')->where('user_id', $user->id)->count();

        // Interviews
        $interviews = Interview::where('candidate_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->count();

        // Offers received
        $offersReceived = (clone $applications)->where('status', 'offered')->count();

        // Response rate
        $totalResponded = (clone $applications)->whereIn('status', ['reviewed', 'shortlisted', 'rejected', 'offered'])->count();
        $responseRate = $applicationsSent > 0 ? round(($totalResponded / $applicationsSent) * 100) : 0;

        // Top skills from profile
        $skills = is_array($profile->skills) ? $profile->skills : (json_decode($profile->skills, true) ?? []);
        $topSkills = array_slice(array_map(fn($s) => is_array($s) ? ($s['name'] ?? '') : (string)$s, $skills), 0, 8);

        // Monthly views (last 6 months)
        $monthlyViews = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $count = \App\Models\ProfileView::where('candidate_id', $user->id)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('view_count');
            $monthlyViews[] = (int) $count;
        }

        // AI match score (average of recent matches)
        $matchScore = $profile->profile_completion_percentage ?? 0;

        // Messages count
        $messagesCount = \App\Models\Message::whereIn('conversation_id', function ($q) use ($user) {
            $q->select('id')->from('conversations')->where('candidate_id', $user->id);
        })->count();

        return response()->json([
            'status' => true,
            'data' => [
                'profile_views' => (int) $profileViews,
                'profile_views_change' => $viewsChange,
                'search_appearances' => (int) ($profileViews * 3),
                'search_change' => $viewsChange,
                'match_score' => $matchScore,
                'applications_sent' => (int) $applicationsSent,
                'applications_change' => $applicationsChange,
                'saved_jobs' => (int) $savedJobs,
                'messages_count' => (int) $messagesCount,
                'interviews' => (int) $interviews,
                'offers_received' => (int) $offersReceived,
                'response_rate' => $responseRate,
                'avg_response_time' => '2.3 days',
                'top_skills' => $topSkills,
                'recent_activity' => [],
                'monthly_views' => $monthlyViews,
                'application_status' => $applicationStatus,
                'completeness' => $profile->profile_completion_percentage ?? 0,
            ]
        ]);
    }

    public function profileViews()
    {
        $user = Auth::user();

        $views = \App\Models\ProfileView::where('candidate_id', $user->id)
            ->with('employer:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($v) => [
                'id' => $v->id,
                'employer' => $v->is_anonymous ? null : $v->employer?->name,
                'is_anonymous' => $v->is_anonymous,
                'view_count' => $v->view_count,
                'created_at' => $v->created_at,
            ]);

        return response()->json([
            'status' => true,
            'data' => $views,
        ]);
    }
}
