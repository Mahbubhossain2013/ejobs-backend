<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobAlertController extends Controller
{
    public function index()
    {
        $alerts = JobAlert::where('user_id', Auth::id())
            ->with('category:id,name')
            ->latest()
            ->get();

        return response()->json(['status' => true, 'data' => $alerts]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:100',
            'keywords' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'job_type' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
            'is_remote' => 'nullable|boolean',
            'frequency' => 'required|in:daily,weekly,instant',
        ]);

        $alert = JobAlert::create([
            'user_id' => Auth::id(),
            ...$validated,
        ]);

        return response()->json(['status' => true, 'data' => $alert], 201);
    }

    public function show(JobAlert $jobAlert)
    {
        if ($jobAlert->user_id !== Auth::id()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json(['status' => true, 'data' => $jobAlert->load('category:id,name')]);
    }

    public function update(Request $request, JobAlert $jobAlert)
    {
        if ($jobAlert->user_id !== Auth::id()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'label' => 'nullable|string|max:100',
            'keywords' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'job_type' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
            'is_remote' => 'nullable|boolean',
            'frequency' => 'required|in:daily,weekly,instant',
            'is_active' => 'nullable|boolean',
        ]);

        $jobAlert->update($validated);

        return response()->json(['status' => true, 'data' => $jobAlert]);
    }

    public function destroy(JobAlert $jobAlert)
    {
        if ($jobAlert->user_id !== Auth::id()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $jobAlert->delete();

        return response()->json(['status' => true, 'message' => 'Alert deleted']);
    }

    public function toggleActive(JobAlert $jobAlert)
    {
        if ($jobAlert->user_id !== Auth::id()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $jobAlert->update(['is_active' => !$jobAlert->is_active]);

        return response()->json(['status' => true, 'data' => $jobAlert]);
    }

    /**
     * Preview matching jobs for an alert (before saving)
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'keywords' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'job_type' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'is_remote' => 'nullable|boolean',
        ]);

        $query = Job::where('is_active', true)->where('visibility', 'public');

        if (!empty($validated['keywords'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('title', 'like', "%{$validated['keywords']}%")
                  ->orWhere('description', 'like', "%{$validated['keywords']}%");
            });
        }

        if (!empty($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        if (!empty($validated['job_type'])) {
            $query->where('job_type', $validated['job_type']);
        }

        if (!empty($validated['location'])) {
            $query->where('location', 'like', "%{$validated['location']}%");
        }

        if (!empty($validated['salary_min'])) {
            $query->where('salary_max', '>=', $validated['salary_min']);
        }

        if (!empty($validated['salary_max'])) {
            $query->where('salary_min', '<=', $validated['salary_max']);
        }

        if (isset($validated['is_remote'])) {
            $query->where('is_remote_project', $validated['is_remote']);
        }

        $count = $query->count();

        return response()->json(['status' => true, 'match_count' => $count]);
    }
}
