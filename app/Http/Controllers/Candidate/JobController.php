<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    // Search and List Jobs
    public function index(Request $request)
    {
        $query = Job::query()->where('is_active', true);

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $jobs = $query->latest()->paginate(10);
        return view('candidate.jobs.index', compact('jobs'));
    }

    // Apply for Job
    public function apply(Request $request, $jobId)
    {
        $request->validate(['resume' => 'required|file|mimes:pdf,doc,docx|max:2048']);
        
        $path = $request->file('resume')->store('resumes', 'public');

        JobApplication::create([
            'job_id' => $jobId,
            'user_id' => Auth::id(),
            'resume_path' => $path,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Application submitted successfully!');
    }
}