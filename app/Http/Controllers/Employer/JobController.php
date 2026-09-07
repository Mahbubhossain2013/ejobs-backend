<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    public function create()
    {
        return view('employer.jobs.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required',
            'job_type' => 'required',
            'salary_range' => 'nullable|string',
            'location' => 'required',
            'deadline' => 'required|date',
        ]);

        Job::create([
            'company_id' => Auth::user()->profile->company_id, // Ensure user profile has company_id
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . time(),
            'description' => $request->description,
            'job_type' => $request->job_type,
            'salary_range' => $request->salary_range,
            'location' => $request->location,
            'deadline' => $request->deadline,
            'is_active' => true,
        ]);

        return redirect()->route('employer.dashboard')->with('success', 'Job posted successfully!');
    }
}