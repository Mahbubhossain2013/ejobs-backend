<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendBulkApplicationEmail;
use App\Jobs\SendBulkSms;
use App\Models\BulkMessageBatch;
use App\Models\BulkMessageRecipient;
use App\Models\Job;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\Interview;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Notification\NotificationService;
use App\Services\ProfileStrengthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmployerController extends Controller
{
    /**
     * Employer Dashboard Overview
     */
    public function dashboard()
    {
        try {
        $user = Auth::user();
        $user->load('badges');
        $company = Company::where('user_id', $user->id)->first();

        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Company profile not found'], 404);
        }

        $jobs = Job::where('company_id', $company->id)
            ->with('category')
            ->withCount('applications')
            ->latest()
            ->get();

        // Calculate locked badges milestones progress tracking
        $role = 'employer';
        $actualJobs = $company->completed_jobs_count ?? 0;
        $actualScore = $company->trust_score ?? 100;
        $actualRating = $company->rating ?? 0.0;
        $actualEarnings = 0;
        $actualSpend = $company->total_spend ?? 0;
        $isVerified = $company->is_verified ?? false;
        $ownedBadgeIds = $user->badges()->pluck('badges.id')->toArray();

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
            Log::warning('Employer locked badges query failed: ' . $e->getMessage());
        }

        $companyData = array_merge($company->toArray(), [
            'active_badges' => $user->activeBadges(),
            'locked_badges' => $lockedBadges
        ]);

        $recentApplications = \App\Models\JobApplication::whereHas('job', fn ($q) => $q->where('company_id', $company->id))
            ->with(['user:id,name,avatar', 'job:id,title'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($app) => [
                'id' => $app->id,
                'candidate_name' => $app->user->name ?? 'Candidate',
                'job_title' => $app->job->title ?? 'Job',
                'status' => $app->status,
                'created_at' => $app->created_at,
            ]);

        return response()->json([
            'status' => true,
            'company' => $companyData,
            'jobs' => $jobs,
            'recent_applications' => $recentApplications,
            'stats' => [
                'total_jobs' => $jobs->count(),
                'total_applicants' => JobApplication::whereIn('job_id', $jobs->pluck('id'))->count(),
                'active_jobs' => $jobs->where('is_active', true)->count(),
            ]
        ]);
        } catch (\Exception $e) {
            Log::error('Employer Dashboard Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to load dashboard'], 500);
        }
    }

    /**
     * Get Employer Profile Data
     */
    public function getProfile()
    {
        $user = Auth::user();
        $company = Company::where('user_id', $user->id)->first();

        return response()->json([
            'status' => true,
            'user' => $user,
            'company' => $company ?? (object)[]
        ]);
    }

    public function selectableCandidates(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:255',
        ]);

        $search = trim((string) ($request->input('q', '')));

        $query = \App\Models\User::query()
            ->where('role', 'candidate')
            ->select('id', 'name', 'email');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $candidates = $query->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'status' => true,
            'data' => $candidates,
        ]);
    }

    /**
     * Update Employer Profile (Includes Trade License handling)
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $company = Company::firstOrCreate(['user_id' => $user->id]);

        $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'trade_license_number' => 'nullable|string',
            'trade_license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:20480',
            'website' => 'nullable|url',
            'industry' => 'nullable|string',
            'location' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $user, $company) {
            $user->update(['name' => $request->name]);

            $companyData = [
                'name' => $request->company_name,
                'website' => $request->website,
                'industry' => $request->industry,
                'location' => $request->location,
                'description' => $request->description,
                'trade_license_number' => $request->trade_license_number,
            ];

            if ($request->hasFile('trade_license_document')) {
                if ($company->trade_license_document) {
                    Storage::disk('public')->delete($company->trade_license_document);
                }
                $companyData['trade_license_document'] = $request->file('trade_license_document')->store('licenses', 'public');
            }

            $company->update($companyData);
        });

        return response()->json(['status' => true, 'message' => 'Profile updated successfully']);
    }

    /**
     * Get Paginated, Filtered Jobs for Employer Management Table
     */
    public function getJobs(Request $request)
    {
        $company = Company::where('user_id', Auth::id())->first();

        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Company not found'], 404);
        }

        $query = Job::where('company_id', $company->id)
            ->with('category')
            ->withCount('applications');

        // Apply Search Filter
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Apply Status Filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'remote') {
                $query->where('is_remote_project', true);
            }
        }

        $jobs = $query->latest()->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $jobs
        ]);
    }

    /**
     * Store New Job Posting
     */
    public function storeJob(Request $request)
    {
        $company = Company::where('user_id', Auth::id())->first();

        if (!$company || !$company->is_verified) {
            return response()->json([
                'status' => false, 
                'message' => 'Your account is not verified. Please contact admin to post jobs.'
            ], 403);
        }

        // Quota Limit Check
        $user = Auth::user();
        if (!$user->useFeatureQuota('job_post_limit')) {
            return response()->json([
                'status' => false,
                'message' => 'You have reached the job posting limit for your current subscription plan. Please upgrade to post more jobs.'
            ], 403);
        }

        $request->validate([
            // Section 1: Basic Job Information
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'job_type' => 'required|string',
            'vacancies' => 'required|integer|min:1',
            'location' => 'required|string',
            'division' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'upazila' => 'nullable|string|max:100',
            'workplace_type' => 'nullable|string|in:on-site,remote,hybrid',
            'salary_range' => 'nullable|string',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
            'salary_type' => 'nullable|string|in:monthly,hourly,negotiable',
            'deadline' => 'required|date|after:today',
            // Section 2: Company / Contact
            'contact_person_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            // Section 3: Job Description
            'description' => 'required|string',
            'job_summary' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'education_requirements' => 'nullable|string',
            'experience_requirements' => 'nullable|string',
            'experience_level' => 'nullable|string',
            'additional_requirements' => 'nullable|string',
            'benefits' => 'nullable|string',
            'required_skills' => 'nullable|string',
            // Section 4: Candidate Requirements
            'min_age' => 'nullable|integer|min:16|max:70',
            'max_age' => 'nullable|integer|min:16|max:70|gte:min_age',
            'gender_preference' => 'nullable|string|in:any,male,female,other',
            'language_skills' => 'nullable|array',
            'required_certifications' => 'nullable|array',
            'driving_license_required' => 'nullable|boolean',
            // Section 5: Application Info
            'application_method' => 'nullable|string|in:internal,external,email',
            'application_url' => 'nullable|url',
            'application_email' => 'nullable|email',
            'required_documents' => 'nullable|array',
            // Remote project fields
            'is_remote_project' => 'nullable|boolean',
            'budget' => 'nullable|numeric',
            'budget_type' => 'nullable|string|in:fixed,hourly,daily,monthly,6_months,1_year,full_project',
        ]);

        $job = Job::create([
            'company_id' => $company->id,
            'category_id' => $request->category_id,
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . Str::random(6),
            'description' => $request->description,
            'job_type' => $request->job_type,
            'location' => $request->location,
            'division' => $request->division,
            'district' => $request->district,
            'upazila' => $request->upazila,
            'salary_range' => $request->salary_range,
            'salary_min' => $request->salary_min,
            'salary_max' => $request->salary_max,
            'deadline' => $request->deadline,
            'is_active' => true,
            // Section 1
            'vacancies' => $request->vacancies ?? 1,
            'workplace_type' => $request->workplace_type,
            'salary_type' => $request->salary_type,
            // Section 2
            'contact_person_name' => $request->contact_person_name,
            'contact_email' => $request->contact_email,
            'contact_phone' => $request->contact_phone,
            // Section 3
            'job_summary' => $request->job_summary,
            'responsibilities' => $request->responsibilities,
            'education_requirements' => $request->education_requirements,
            'experience_requirements' => $request->experience_requirements,
            'experience_level' => $request->experience_level,
            'additional_requirements' => $request->additional_requirements,
            'benefits' => $request->benefits,
            'required_skills' => $request->required_skills ? array_map('trim', explode(',', $request->required_skills)) : null,
            // Section 4
            'min_age' => $request->min_age,
            'max_age' => $request->max_age,
            'gender_preference' => $request->gender_preference,
            'language_skills' => $request->language_skills,
            'required_certifications' => $request->required_certifications,
            'driving_license_required' => $request->boolean('driving_license_required'),
            // Section 5
            'application_method' => $request->application_method ?? 'internal',
            'application_url' => $request->application_url,
            'application_email' => $request->application_email,
            'required_documents' => $request->required_documents,
            // Remote project
            'is_remote_project' => $request->boolean('is_remote_project'),
            'budget' => $request->budget,
            'budget_type' => $request->budget_type ?? 'fixed',
        ]);

        return response()->json(['status' => true, 'message' => 'Job posted successfully', 'data' => $job]);
    }

    /**
     * Update an Existing Job Posting
     */
    public function updateJob(Request $request, $id)
    {
        $company = Company::where('user_id', Auth::id())->first();
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Company profile not found'], 404);
        }

        $job = Job::where('id', $id)->where('company_id', $company->id)->firstOrFail();

        if ($job->project_status === 'in_progress' || $job->project_status === 'completed') {
            return response()->json(['status' => false, 'message' => 'Cannot edit a job with active/completed project'], 422);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'job_type' => 'sometimes|string',
            'vacancies' => 'sometimes|integer|min:1',
            'location' => 'sometimes|string',
            'workplace_type' => 'nullable|string|in:on-site,remote,hybrid',
            'salary_range' => 'nullable|string',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'salary_type' => 'nullable|string|in:monthly,hourly,negotiable',
            'deadline' => 'sometimes|date',
            'description' => 'sometimes|string',
            'job_summary' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'education_requirements' => 'nullable|string',
            'experience_requirements' => 'nullable|string',
            'experience_level' => 'nullable|string',
            'additional_requirements' => 'nullable|string',
            'benefits' => 'nullable|string',
            'required_skills' => 'nullable|string',
            'min_age' => 'nullable|integer|min:16|max:70',
            'max_age' => 'nullable|integer|min:16|max:70',
            'gender_preference' => 'nullable|string|in:any,male,female,other',
            'contact_person_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'application_method' => 'nullable|string|in:internal,external,email',
            'is_remote_project' => 'nullable|boolean',
            'budget' => 'nullable|numeric',
            'budget_type' => 'nullable|string|in:fixed,hourly,daily,monthly,6_months,1_year,full_project',
            'division' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'upazila' => 'nullable|string|max:100',
        ]);

        $fields = $request->only([
            'title', 'category_id', 'job_type', 'vacancies', 'location', 'workplace_type',
            'salary_range', 'salary_min', 'salary_max', 'salary_type', 'deadline', 'description', 'job_summary',
            'responsibilities', 'education_requirements', 'experience_requirements',
            'experience_level', 'additional_requirements', 'benefits',
            'min_age', 'max_age', 'gender_preference',
            'contact_person_name', 'contact_email', 'contact_phone',
            'application_method', 'is_remote_project', 'budget', 'budget_type',
            'division', 'district', 'upazila',
        ]);

        if (isset($fields['budget_type']) && $fields['budget_type'] === null) {
            $fields['budget_type'] = 'fixed';
        }

        if ($request->has('required_skills')) {
            $fields['required_skills'] = array_map('trim', explode(',', $request->required_skills));
        }

        $job->update($fields);

        return response()->json(['status' => true, 'message' => 'Job updated successfully', 'data' => $job->fresh()]);
    }

    /**
     * Delete a Job Posting
     */
    public function deleteJob($id)
    {
        $company = Company::where('user_id', Auth::id())->first();
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Company profile not found'], 404);
        }

        $job = Job::where('id', $id)->where('company_id', $company->id)->firstOrFail();

        if ($job->project_status === 'in_progress' || $job->project_status === 'completed') {
            return response()->json(['status' => false, 'message' => 'Cannot delete a job with active/completed project'], 422);
        }

        if ($job->applications()->count() > 0) {
            $job->update(['is_active' => false]);
            return response()->json(['status' => true, 'message' => 'Job archived (has existing applications)']);
        }

        $job->delete();

        return response()->json(['status' => true, 'message' => 'Job deleted successfully']);
    }

    /**
     * Toggle Job Active/Inactive Status
     */
    public function toggleStatus($id)
    {
        $company = Company::where('user_id', Auth::id())->first();
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Company profile not found'], 404);
        }

        $job = Job::where('id', $id)->where('company_id', $company->id)->firstOrFail();

        $job->update(['is_active' => !$job->is_active]);

        return response()->json([
            'status' => true,
            'message' => 'Job ' . ($job->is_active ? 'activated' : 'deactivated') . ' successfully',
            'data' => ['is_active' => $job->is_active],
        ]);
    }

    /**
     * Smart Recommendation Engine
     */
    public function recommendedJobs()
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile || empty($profile->skills)) {
            $jobs = Job::with('company')
                ->where('is_active', true)
                ->latest()
                ->take(4)
                ->get();
                
            return response()->json(['status' => true, 'data' => $jobs, 'type' => 'trending']);
        }

        $skills = (array) $profile->skills;
        $city = $profile->city;

        $query = Job::with('company')->where('is_active', true);

        $query->where(function ($q) use ($skills, $city) {
            foreach ($skills as $skill) {
                $q->orWhere('title', 'LIKE', "%{$skill}%")
                  ->orWhere('description', 'LIKE', "%{$skill}%");
            }
            if ($city) {
                $q->orWhere('location', 'LIKE', "%{$city}%");
            }
        });

        $jobs = $query->orderBy('is_remote_project', 'desc')
                      ->latest()
                      ->take(4)
                      ->get();

        if ($jobs->isEmpty()) {
            $jobs = Job::with('company')->where('is_active', true)->latest()->take(4)->get();
            return response()->json(['status' => true, 'data' => $jobs, 'type' => 'fallback']);
        }

        return response()->json(['status' => true, 'data' => $jobs, 'type' => 'smart_match']);
    }

    /**
     * Get Applicants for Employer's Jobs
     */
    public function getApplicants()
    {
        $companyId = Company::where('user_id', Auth::id())->value('id');

        if (!$companyId) {
            return response()->json(['status' => false, 'message' => 'Company profile not found'], 404);
        }

        $applicants = JobApplication::whereHas('job', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->with(['user.profile', 'job:id,title,is_remote_project,budget,budget_type,job_type,location,salary_min,salary_max,salary_type,deadline,vacancies,workplace_type,description,required_skills,experience_level,contact_person_name,contact_email,contact_phone,application_method,application_url,application_email,required_documents,job_summary,responsibilities,education_requirements,experience_requirements,additional_requirements,benefits,min_age,max_age,gender_preference,language_skills,required_certifications,driving_license_required'])
            ->latest()
            ->get()
            ->map(function ($applicant) {
                $applicant->profile_strength = ProfileStrengthService::calculate($applicant->user);
                $applicant->cover_letter = $applicant->cover_letter ?? null;
                $applicant->resume_url = $applicant->resume_path ? url('storage/' . $applicant->resume_path) : null;
                return $applicant;
            });

        // Sort by profile_strength descending (highest first)
        $applicants = $applicants->sortByDesc('profile_strength')->values();

        return response()->json(['status' => true, 'data' => $applicants]);
    }

    /**
     * Update Application Status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:pending,reviewed,shortlisted,rejected,interview,hired']);
        
        $application = JobApplication::with('job')->findOrFail($id);
        $companyId = Company::where('user_id', Auth::id())->value('id');
        
        if ($application->job->company_id !== $companyId) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $application->update(['status' => $request->status]);

        if ($request->status === 'hired' && $application->job->is_remote_project && empty($application->job->assigned_to)) {
            $application->job->update([
                'assigned_to' => $application->user_id,
                'project_status' => 'in_progress'
            ]);
        }

        $candidate = $application->user;
        $statusLabels = [
            'shortlisted' => 'shortlisted for',
            'rejected' => 'not selected for',
            'interview' => 'invited to interview for',
            'pending' => 'under review for',
            'reviewed' => 'reviewed for',
            'hired' => 'hired for',
        ];
        $label = $statusLabels[$request->status] ?? $request->status;
        try {
            if ($candidate) {
                app(NotificationService::class)->sendNotification(
                    $candidate,
                    'Application Update',
                    "Your application for \"{$application->job->title}\" has been {$label} this position.",
                    'application',
                    '/dashboard/applied-jobs'
                );
            }
        } catch (\Throwable $e) {
            Log::error("Application status notification failed for application {$application->id}: " . $e->getMessage());
        }

        // Send email for non-pending status changes
        if ($request->status !== 'pending') {
            $statusColors = [
                'shortlisted' => ['#16a34a', '#059669', '#f0fdf4', '#166534'],
                'rejected' => ['#dc2626', '#b91c1c', '#fef2f2', '#991b1b'],
                'interview' => ['#4f46e5', '#7c3aed', '#eff6ff', '#1e40af'],
                'reviewed' => ['#2563eb', '#1d4ed8', '#eff6ff', '#1e40af'],
                'hired' => ['#059669', '#047857', '#ecfdf5', '#065f46'],
            ];
            $colors = $statusColors[$request->status] ?? ['#6366f1', '#4f46e5', '#f0f0ff', '#3730a3'];
            $candidateMessages = [
                'shortlisted' => 'Great news! You have been shortlisted. The employer will contact you soon with next steps.',
                'rejected' => 'Unfortunately, you were not selected for this position. Keep applying — the right opportunity is out there!',
                'interview' => 'The employer would like to schedule an interview with you. Check your messages for details.',
                'reviewed' => 'Your application has been reviewed by the employer. Keep an eye on your inbox for updates.',
                'hired' => 'Congratulations! You have been hired for this position. Welcome to the team!',
            ];
            try {
                $brandName = Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'eJobs');
                $dashboardUrl = config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/dashboard/applied-jobs';
                Mail::to($candidate->email)->queue(new \App\Mail\GenericMail('emails.application_status_changed', [
                    'brandName' => $brandName,
                    'candidateName' => $candidate->name,
                    'jobTitle' => $application->job->title,
                    'companyName' => $application->job->company->name ?? '',
                    'status' => $request->status,
                    'message' => $candidateMessages[$request->status] ?? '',
                    'statusColor' => $colors[0],
                    'statusColorDark' => $colors[1],
                    'statusBg' => $colors[2],
                    'statusTextColor' => $colors[3],
                    'dashboardUrl' => $dashboardUrl,
                ], "{$brandName} — Application {$application->job->title}: " . ucfirst($application->status)));
            } catch (\Throwable $e) {
                Log::error("Application status email failed for user {$candidate->id}: " . $e->getMessage());
            }
        }

        return response()->json(['status' => true, 'message' => 'Status updated successfully']);
    }

    public function hireApplicant(Request $request, $id)
    {
        return $this->updateStatus($request->merge(['status' => 'hired']), $id);
    }

    public function getApplicant($id)
    {
        $companyId = Company::where('user_id', Auth::id())->value('id');
        if (!$companyId) {
            return response()->json(['status' => false, 'message' => 'Company profile not found'], 404);
        }
        $application = JobApplication::with(['user.profile', 'job:id,title,is_remote_project,budget,budget_type'])
            ->where('id', $id)
            ->whereHas('job', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->first();
        if (!$application) {
            return response()->json(['status' => false, 'message' => 'Application not found'], 404);
        }
        $application->cover_letter = $application->cover_letter ?? null;
        $application->resume_url = $application->resume_path ? url('storage/' . $application->resume_path) : null;
        return response()->json(['status' => true, 'data' => $application]);
    }

    /**
     * Send bulk email/SMS to applicants matching a status filter for a job.
     */
    public function sendBulkMessage(Request $request)
    {
        $request->validate([
            'channel' => 'required|in:email,sms',
            'template' => 'required|in:interview,update,custom',
            'job_id' => 'nullable|integer',
            'status' => 'required|string',
            'recipient_ids' => 'required|array|min:1',
            'recipient_ids.*' => 'integer',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'sms_message' => 'required_if:channel,sms|string|max:1600',
            'interview_date' => 'nullable|string|max:255',
            'interview_location' => 'nullable|string|max:255',
        ]);

        $companyId = Company::where('user_id', Auth::id())->value('id');
        if (!$companyId) {
            return response()->json(['status' => false, 'message' => 'Company profile not found'], 404);
        }

        // Load applicants for the company whose application IDs were explicitly sent.
        $applications = JobApplication::whereIn('id', $request->recipient_ids)
            ->whereHas('job', fn ($q) => $q->where('company_id', $companyId))
            ->with(['user.profile', 'job.company'])
            ->get();

        // For SMS: compute total cost & verify wallet balance covers it.
        if ($request->channel === 'sms') {
            $wallet = Wallet::where('user_id', Auth::id())->first();
            if (!$wallet) {
                return response()->json(['status' => false, 'message' => 'Wallet not found'], 404);
            }

            $perSms = (float) (Setting::where('key', 'sms_charge_per_sms')->value('value') ?? 1);
            $smsService = app(\App\Services\Notification\SmsService::class);
            $validated = $applications->filter(function ($app) use ($smsService) {
                $phone = $app->user?->profile?->phone;
                $clean = $smsService->cleanPhoneNumber((string) $phone);
                return strlen($clean) === 11 && str_starts_with($clean, '01');
            });

            $totalSms = 0;
            foreach ($validated as $app) {
                $cleanPhone = $smsService->cleanPhoneNumber((string) $app->user?->profile?->phone);
                $personalized = str_replace(
                    ['{name}', '{job_title}', '{company}'],
                    [$app->user->name ?? 'there', $app->job->title ?? '', $app->job->company->name ?? ''],
                    $request->sms_message
                );
                $totalSms += (int) max(1, ceil(mb_strlen($personalized) / 160));
            }
            $totalCost = $totalSms * $perSms;

            if ($wallet->balance < $totalCost) {
                return response()->json([
                    'status' => false,
                    'message' => "Insufficient wallet balance. Required: {$totalCost} BDT for {$totalSms} SMS segments. Current balance: {$wallet->balance} BDT",
                    'wallet_balance' => (float) $wallet->balance,
                    'required' => $totalCost,
                    'sms_count' => $totalSms,
                ], 400);
            }
        }

        $batch = BulkMessageBatch::create([
            'employer_id' => Auth::id(),
            'company_id' => $companyId,
            'job_id' => $request->job_id,
            'status_filter' => $request->status,
            'channel' => $request->channel,
            'template' => $request->template,
            'subject' => $request->subject,
            'message' => $request->message,
            'sms_message' => $request->sms_message,
            'interview_date' => $request->interview_date,
            'interview_location' => $request->interview_location,
            'total_recipients' => $applications->count(),
            'started_at' => now(),
        ]);

        foreach ($applications as $app) {
            $batchId = $batch->id;
            $channel = $request->channel;
            $appId = $app->id;
            $template = $request->template;
            $subject = $request->subject;
            $message = $request->message;
            $smsMessage = $request->sms_message;
            $interviewDate = $request->interview_date;
            $interviewLocation = $request->interview_location;

            $recipient = BulkMessageRecipient::create([
                'batch_id' => $batchId,
                'user_id' => $app->user_id,
                'application_id' => $appId,
                'channel' => $channel,
                'recipient_email' => $app->user?->email,
                'recipient_phone' => $app->user?->profile?->phone,
                'status' => 'queued',
            ]);

            if ($channel === 'email') {
                SendBulkApplicationEmail::dispatch(
                    $recipient->id, $template, $subject, $message, $interviewDate, $interviewLocation
                );
            } else {
                SendBulkSms::dispatch($recipient->id, $smsMessage);
            }
        }

        $batch->refresh();
        $batch->sms_count = $request->channel === 'sms' ? ($batch->recipients()->sum('sms_count') ?: 0) : 0;

        return response()->json([
            'status' => true,
            'message' => 'Bulk ' . $request->channel . ' queued for ' . $applications->count() . ' recipients.',
            'batch_id' => $batch->id,
            'total_recipients' => $batch->total_recipients,
        ]);
    }

    /**
     * List the employer's bulk message batches (delivery monitor).
     */
    public function bulkMessageBatches()
    {
        $companyId = Company::where('user_id', Auth::id())->value('id');
        if (!$companyId) {
            return response()->json(['status' => false, 'message' => 'Company profile not found'], 404);
        }

        $batches = BulkMessageBatch::where('company_id', $companyId)
            ->withCount('recipients')
            ->latest()
            ->take(50)
            ->get()
            ->map(function ($b) {
                $b->recipient_count = $b->recipients_count;
                return $b;
            });

        return response()->json(['status' => true, 'data' => $batches]);
    }

    /**
     * Get recipients for a batch.
     */
    public function bulkMessageRecipients($batchId)
    {
        $companyId = Company::where('user_id', Auth::id())->value('id');
        $batch = BulkMessageBatch::where('id', $batchId)->where('company_id', $companyId)->first();
        if (!$batch) {
            return response()->json(['status' => false, 'message' => 'Batch not found'], 404);
        }

        $recipients = $batch->recipients()
            ->with(['user:id,name'])
            ->get();

        return response()->json(['status' => true, 'data' => $recipients]);
    }

    /**
     * Retry a failed recipient (email or SMS).
     */
    public function bulkMessageRetry($batchId, $recipientId)
    {
        $companyId = Company::where('user_id', Auth::id())->value('id');
        $batch = BulkMessageBatch::where('id', $batchId)->where('company_id', $companyId)->first();
        if (!$batch) {
            return response()->json(['status' => false, 'message' => 'Batch not found'], 404);
        }

        $recipient = BulkMessageRecipient::where('id', $recipientId)->where('batch_id', $batchId)->first();
        if (!$recipient || !in_array($recipient->status, ['failed'])) {
            return response()->json(['status' => false, 'message' => 'Recipient is not in a retryable state'], 400);
        }

        $recipient->update(['status' => 'queued', 'error' => null]);

        if ($recipient->channel === 'email') {
            SendBulkApplicationEmail::dispatch(
                $recipient->id, $batch->template, $batch->subject, $batch->message, $batch->interview_date, $batch->interview_location
            );
        } else {
            SendBulkSms::dispatch($recipient->id, $batch->sms_message);
        }

        return response()->json(['status' => true, 'message' => 'Retry dispatched.']);
    }

    /**
     * Search candidate database with role-based restriction and CV lock
     */
    public function searchCandidates(Request $request)
    {
        $employer = Auth::user();

        // 1. Guard check
        if (!$employer->hasFeature('candidate_database_access')) {
            return response()->json([
                'status' => false,
                'message' => 'Candidate database access is not included in your current subscription. Please upgrade your plan.'
            ], 403);
        }
        
        // 2. Query candidates
        $query = \App\Models\User::role('candidate')
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->where('user_profiles.is_public', true)
            ->select('users.*', 'user_profiles.bio', 'user_profiles.current_position', 'user_profiles.city', 'user_profiles.skills', 'user_profiles.resume_path', 'user_profiles.avatar')
            ->selectRaw('(
                SELECT COUNT(*) FROM promotions 
                WHERE promotions.user_id = users.id 
                AND promotions.type = "profile_boost"
                AND promotions.status = "active" 
                AND promotions.start_date <= NOW() 
                AND (promotions.end_date IS NULL OR promotions.end_date >= NOW())
            ) as is_boosted')
            ->selectRaw('(
                SELECT COUNT(*) FROM badge_user
                JOIN badges ON badge_user.badge_id = badges.id
                WHERE badge_user.user_id = users.id
                AND badges.badge_key IN ("premium", "pro")
                AND badges.is_active = 1
                AND (badge_user.expires_at IS NULL OR badge_user.expires_at > NOW())
            ) as is_premium');

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('users.name', 'like', '%' . $search . '%')
                  ->orWhere('user_profiles.bio', 'like', '%' . $search . '%')
                  ->orWhere('user_profiles.current_position', 'like', '%' . $search . '%')
                  ->orWhere('user_profiles.city', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('city')) {
            $query->where('user_profiles.city', 'like', '%' . $request->city . '%');
        }

        if ($request->filled('skills')) {
            $skills = is_array($request->skills) ? $request->skills : explode(',', $request->skills);
            $query->where(function($q) use ($skills) {
                foreach ($skills as $skill) {
                    $q->orWhere('user_profiles.skills', 'like', '%' . trim($skill) . '%');
                }
            });
        }

        // Sort: boosted first, then premium, then latest
        $candidates = $query->orderByDesc('is_boosted')
                            ->orderByDesc('is_premium')
                            ->latest('users.created_at')
                            ->paginate(12);

        $hasResumeAccess = $employer->hasFeature('resume_cv_downloads');

        // Format output
        $candidates->getCollection()->transform(function($candidate) use ($hasResumeAccess) {
            $skills = is_array($candidate->skills) ? $candidate->skills : json_decode($candidate->skills, true) ?? [];
            $normalizedSkills = array_map(function ($skill) {
                if (is_array($skill) && isset($skill['name'])) {
                    return $skill['name'];
                }
                return (string) $skill;
            }, $skills);

            return [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'username' => $candidate->username,
                'current_position' => $candidate->current_position,
                'city' => $candidate->city,
                'bio' => $candidate->bio,
                'skills' => $normalizedSkills,
                'avatar' => $candidate->avatar ? asset('storage/' . $candidate->avatar) : null,
                'is_boosted' => $candidate->is_boosted > 0,
                'is_premium' => $candidate->is_premium > 0,
                'resume_locked' => !$hasResumeAccess,
                'resume' => $hasResumeAccess && $candidate->resume_path ? asset('storage/' . $candidate->resume_path) : null,
                'active_badges' => $candidate->activeBadges(),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $candidates
        ]);
    }

    /**
     * Phase 2: Approve applicant and assign remote project with escrow funding
     */
    public function assignProjectCandidate($jobId, $applicationId)
    {
        $companyId = Company::where('user_id', Auth::id())->value('id');
        if (!$companyId) {
            return response()->json(['status' => false, 'message' => 'Company profile not found'], 404);
        }

        $job = Job::where('id', $jobId)->where('company_id', $companyId)->firstOrFail();
        $application = JobApplication::where('id', $applicationId)->where('job_id', $jobId)->firstOrFail();

        if (!$job->is_remote_project) {
            return response()->json(['status' => false, 'message' => 'Only remote projects can be assigned with escrows.'], 400);
        }

        if ($job->assigned_to) {
            return response()->json(['status' => false, 'message' => 'Project has already been assigned.'], 400);
        }

        DB::transaction(function () use ($job, $application) {
            // 1. Lock wallet row to prevent race conditions during concurrent funding
            $employerWallet = \App\Models\Wallet::where('user_id', Auth::id())->lockForUpdate()->first();

            // Read fee settings
            $feePayer = \App\Models\Setting::where('key', 'escrow_fee_payer')->value('value') ?? 'candidate';
            $feePercent = (float) (\App\Models\Setting::where('key', 'escrow_fee_percent')->value('value') ?? 2);
            $feeAmount = $job->budget * ($feePercent / 100);
            $totalRequired = $feePayer === 'employer' ? $job->budget + $feeAmount : $job->budget;

            if ($employerWallet->balance < $totalRequired) {
                throw new \Exception("Insufficient wallet balance. Required: {$totalRequired} BDT");
            }

            // 2. Move employer wallet balance to locked balance
            $lockAmount = $feePayer === 'employer' ? $job->budget + $feeAmount : $job->budget;
            $employerWallet->balance -= $lockAmount;
            $employerWallet->locked_balance += $lockAmount;
            $employerWallet->save();

            // Log escrow funding transaction
            $employerWallet->transactions()->create([
                'type' => 'debit',
                'amount' => $lockAmount,
                'reference_type' => 'escrow_funding',
                'reference_id' => null,
                'description' => 'Escrow locked for: ' . $job->title . ($feePayer === 'employer' ? " (incl. {$feePercent}% fee)" : ''),
                'status' => 'completed',
            ]);

            // 3. Create Escrow record
            \App\Models\Escrow::create([
                'job_id' => $job->id,
                'employer_id' => Auth::id(),
                'candidate_id' => $application->user_id,
                'amount' => $job->budget,
                'platform_fee' => $feePayer === 'employer' ? $feeAmount : 0,
                'status' => 'funded'
            ]);

            // 3. Assign Candidate and update status
            $job->update([
                'assigned_to' => $application->user_id,
                'project_status' => 'in_progress'
            ]);

            // 4. Update Application status
            $application->update(['status' => 'shortlisted']);

            // 5. Create dedicated private Conversation between employer and candidate
            \App\Models\Conversation::create([
                'employer_id' => Auth::id(),
                'candidate_id' => $application->user_id,
                'job_id' => $job->id,
                'uuid' => \Illuminate\Support\Str::uuid()->toString()
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Freelancer successfully assigned! Escrow has been funded with ' . $job->budget . ' BDT.'
        ]);
    }

    /**
     * Schedule an interview for a shortlisted candidate
     */
    public function scheduleInterview(Request $request, int $applicationId)
    {
        $application = JobApplication::with(['job.company', 'user'])
            ->where('id', $applicationId)
            ->whereHas('job', fn ($q) => $q->where('company_id', Auth::user()->company_id ?? Auth::id()))
            ->first();

        if (!$application) {
            return response()->json(['status' => false, 'message' => 'Application not found.'], 404);
        }

        if (!in_array($application->status, ['shortlisted', 'interview'])) {
            return response()->json(['status' => false, 'message' => 'Candidate must be shortlisted first.'], 400);
        }

        $validated = $request->validate([
            'type' => 'required|in:in_person,video,phone',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:5|max:480',
            'location' => 'required_if:type,in_person|nullable|string|max:20480',
            'notes' => 'nullable|string|max:2000',
        ]);

        $interview = Interview::create([
            'job_id' => $application->job_id,
            'application_id' => $application->id,
            'employer_id' => Auth::id(),
            'candidate_id' => $application->user_id,
            'type' => $validated['type'],
            'scheduled_at' => $validated['scheduled_at'],
            'duration_minutes' => $validated['duration_minutes'],
            'location' => $validated['location'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Update application status to interview
        $application->update(['status' => 'interview']);

        // Notify candidate
        NotificationService::sendNotification(
            $application->user_id,
            'Interview Scheduled',
            "You have been invited to a {$validated['type']} interview for {$application->job->title}",
            'interview',
            ['interview_id' => $interview->id]
        );

        // Send interview scheduled email
        $brandName = Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'JobBazar');
        $frontendUrl = config('app.frontend_url', config('app.url', 'http://localhost:3000'));
        $interviewDate = Carbon::parse($validated['scheduled_at'])->format('F j, Y');
        $interviewTime = Carbon::parse($validated['scheduled_at'])->format('g:i A');
        $acceptUrl = $frontendUrl . '/dashboard/interviews';
        $declineUrl = $frontendUrl . '/dashboard/interviews';
        try {
            Mail::to($application->user->email)->queue(new \App\Mail\GenericMail('emails.interview_scheduled', [
                'brandName' => $brandName,
                'userName' => $application->user->name,
                'companyName' => $application->job->company->name ?? 'the company',
                'jobTitle' => $application->job->title,
                'interviewDate' => $interviewDate,
                'interviewTime' => $interviewTime,
                'interviewType' => ucfirst(str_replace('_', ' ', $validated['type'])),
                'location' => $validated['location'] ?? null,
                'acceptUrl' => $acceptUrl,
                'declineUrl' => $declineUrl,
            ], "{$brandName} — Interview Scheduled for {$application->job->title}"));
        } catch (\Throwable $e) {
            Log::error("Interview email failed for user {$application->user_id}: " . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Interview scheduled successfully.',
            'data' => $interview->load(['job:id,title', 'candidate:id,name,avatar,email']),
        ]);
    }

    /**
     * Get all interviews for employer's jobs
     */
    public function getInterviews(Request $request)
    {
        $companyId = Auth::user()->company_id ?? Auth::id();

        $interviews = Interview::whereHas('job', fn ($q) => $q->where('company_id', $companyId))
            ->with([
                'job:id,title,company_id',
                'candidate:id,name,avatar,email',
                'application:id,status',
            ])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('scheduled_at', 'desc')
            ->paginate(min(50, max(1, intval($request->per_page ?? 15))));

        return response()->json(['status' => true, 'data' => $interviews]);
    }

    /**
     * Update interview status (complete, cancel, no_show)
     */
    public function updateInterviewStatus(Request $request, int $interviewId)
    {
        $interview = Interview::whereHas('job', fn ($q) => $q->where('company_id', Auth::user()->company_id ?? Auth::id()))
            ->with('application')
            ->findOrFail($interviewId);

        $validated = $request->validate([
            'status' => 'required|in:completed,cancelled,no_show',
            'outcome' => 'nullable|string|max:2000',
        ]);

        $interview->update([
            'status' => $validated['status'],
            'outcome' => $validated['outcome'] ?? null,
        ]);

        // If completed, auto-set application to hired
        if ($validated['status'] === 'completed') {
            $interview->application->update(['status' => 'hired']);
        }

        // Notify candidate
        $statusMessages = [
            'completed' => 'Interview completed',
            'cancelled' => 'Interview cancelled',
            'no_show' => 'Interview marked as no-show',
        ];

        NotificationService::sendNotification(
            $interview->candidate_id,
            $statusMessages[$validated['status']],
            "Your interview for {$interview->job->title} has been {$validated['status']}.",
            'interview',
            ['interview_id' => $interview->id]
        );

        return response()->json([
            'status' => true,
            'message' => 'Interview status updated.',
            'data' => $interview->fresh(),
        ]);
    }

    /**
     * Send a job offer to a shortlisted/interviewed candidate
     */
    public function offerApplication(Request $request, int $applicationId)
    {
        $application = JobApplication::with(['job.company', 'user'])
            ->where('id', $applicationId)
            ->whereHas('job', fn ($q) => $q->where('company_id', Auth::user()->company_id ?? Auth::id()))
            ->first();

        if (!$application) {
            return response()->json(['status' => false, 'message' => 'Application not found.'], 404);
        }

        if (!in_array($application->status, ['shortlisted', 'interview'])) {
            return response()->json(['status' => false, 'message' => 'Candidate must be shortlisted or interviewed first.'], 400);
        }

        $validated = $request->validate([
            'salary' => 'nullable|string|max:100',
            'start_date' => 'nullable|date',
            'response_deadline' => 'nullable|integer|min:1|max:30',
            'notes' => 'nullable|string|max:2000',
        ]);

        $application->update(['status' => 'offered']);

        $candidate = $application->user;
        app(NotificationService::class)->sendNotification(
            $candidate,
            'Job Offer Received',
            "You have received a job offer from \"{$application->job->company->name}\" for the position of \"{$application->job->title}\".",
            'application',
            '/dashboard/applied-jobs'
        );

        // Send job offer email
        $brandName = Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'JobBazar');
        $dashboardUrl = config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/dashboard/applied-jobs';
        $deadline = ($validated['response_deadline'] ?? 7) . ' days';
        try {
            Mail::to($candidate->email)->queue(new \App\Mail\GenericMail('emails.job_offer_received', [
                'brandName' => $brandName,
                'userName' => $candidate->name,
                'companyName' => $application->job->company->name ?? 'the company',
                'jobTitle' => $application->job->title,
                'salary' => $validated['salary'] ?? null,
                'startDate' => $validated['start_date'] ? Carbon::parse($validated['start_date'])->format('F j, Y') : null,
                'responseDeadline' => $deadline,
                'dashboardUrl' => $dashboardUrl,
            ], "{$brandName} — You've Received a Job Offer!"));
        } catch (\Throwable $e) {
            Log::error("Job offer email failed for user {$candidate->id}: " . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Job offer sent successfully.',
        ]);
    }

    /**
     * Get accepted jobs with shortlisted/hired candidates
     */
    public function getAcceptedJobs(Request $request)
    {
        $companyId = Auth::user()->company_id ?? Auth::id();

        $jobs = Job::where('company_id', $companyId)
            ->whereHas('applications', fn ($q) => $q->whereIn('status', ['shortlisted', 'hired', 'interview', 'offered']))
            ->with([
                'applications' => function ($q) {
                    $q->whereIn('status', ['shortlisted', 'hired', 'interview', 'offered'])
                        ->with(['user:id,name,avatar,email,username']);
                },
                'applications.interview' => function ($q) {
                    $q->latest()->first();
                },
            ])
            ->get()
            ->map(function ($job) {
                $job->accepted_count = $job->applications->count();
                $job->latest_message = \App\Models\Message::whereHas('conversation', fn ($q) => $q->where('job_id', $job->id))
                    ->latest()
                    ->value('message');
                return $job;
            });

        return response()->json(['status' => true, 'data' => $jobs]);
    }
}
