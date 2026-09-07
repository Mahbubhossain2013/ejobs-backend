<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GuestJobController extends Controller
{
    /**
     * Check if guest job posting is enabled
     */
    public function checkAvailability()
    {
        $enabled = Setting::getValue('guest_job_posting_enabled', 'true') === 'true';
        $limit = (int) Setting::getValue('guest_job_post_limit', '3');
        $requiresReview = Setting::getValue('guest_job_requires_review', 'true') === 'true';

        return response()->json([
            'status' => true,
            'data' => [
                'enabled' => $enabled,
                'max_jobs' => $limit,
                'requires_review' => $requiresReview,
            ],
        ]);
    }

    /**
     * Store a guest job posting (no login required)
     */
    public function store(Request $request)
    {
        // Check if guest job posting is enabled
        $enabled = Setting::getValue('guest_job_posting_enabled', 'true') === 'true';
        if (!$enabled) {
            return response()->json([
                'status' => false,
                'message' => 'Guest job posting is currently disabled. Please create an account to post jobs.',
            ], 403);
        }

        $limit = (int) Setting::getValue('guest_job_post_limit', '3');
        $requiresReview = Setting::getValue('guest_job_requires_review', 'true') === 'true';

        // Validate contact email to track guest posts
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:50',
            'category_id' => 'required|exists:categories,id',
            'job_type' => 'required|string|in:full-time,part-time,contract,internship,freelance',
            'vacancies' => 'nullable|integer|min:1',
            'location' => 'required|string|max:255',
            'salary_range' => 'nullable|string|max:255',
            'deadline' => 'required|date|after:today',
            'contact_email' => 'required|email|max:255',
            'contact_person_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
        ]);

        // Rate limit: check how many jobs this email has posted
        $email = $request->contact_email;
        $recentGuestJobs = Job::where('guest_email', $email)
            ->where('is_guest_post', true)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        if ($recentGuestJobs >= $limit) {
            return response()->json([
                'status' => false,
                'message' => "You have reached the maximum of {$limit} guest job postings. Please create an account to post more jobs.",
            ], 429);
        }

        $requiresReview = Setting::getValue('guest_job_requires_review', 'true') === 'true';

        $job = Job::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . time(),
            'description' => $request->description,
            'category_id' => $request->category_id,
            'job_type' => $request->job_type,
            'vacancies' => $request->vacancies ?? 1,
            'location' => $request->location,
            'salary_range' => $request->salary_range,
            'deadline' => $request->deadline,
            'contact_person_name' => $request->contact_person_name,
            'contact_email' => $request->contact_email,
            'contact_phone' => $request->contact_phone,
            'is_active' => !$requiresReview,
            'is_guest_post' => true,
            'guest_email' => $email,
            'company_id' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => $requiresReview
                ? 'Job posted successfully! It will be visible after admin review.'
                : 'Job posted successfully!',
            'data' => [
                'id' => $job->id,
                'title' => $job->title,
                'status' => $requiresReview ? 'pending_review' : 'active',
            ],
        ], 201);
    }
}
