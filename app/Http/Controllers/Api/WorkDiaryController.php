<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkDiary;
use App\Models\Job;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkDiaryController extends Controller
{
    /**
     * CANDIDATE: List work diary entries for a job
     */
    public function index(Request $request, $jobId)
    {
        $user = Auth::user();
        $job = Job::where('id', $jobId)->where('assigned_to', $user->id)->firstOrFail();

        $entries = WorkDiary::where('job_id', $jobId)
            ->where('user_id', $user->id)
            ->orderByDesc('date')
            ->paginate(15);

        return response()->json([
            'status' => true,
            'data' => $entries,
            'summary' => WorkDiary::weeklySummary($jobId, $user->id),
            'total_approved_hours' => WorkDiary::approvedHoursForJob($jobId),
        ]);
    }

    /**
     * EMPLOYER: List all diary entries for a job
     */
    public function employerIndex(Request $request, $jobId)
    {
        $companyId = Company::where('user_id', Auth::id())->value('id');
        $job = Job::where('id', $jobId)->where('company_id', $companyId)->firstOrFail();

        $entries = WorkDiary::where('job_id', $jobId)
            ->with('user:id,name,username')
            ->orderByDesc('date')
            ->paginate(15);

        return response()->json([
            'status' => true,
            'data' => $entries,
            'total_approved_hours' => WorkDiary::approvedHoursForJob($jobId),
        ]);
    }

    /**
     * CANDIDATE: Create a diary entry
     */
    public function store(Request $request, $jobId)
    {
        $user = Auth::user();
        $job = Job::where('id', $jobId)->where('assigned_to', $user->id)->firstOrFail();

        $request->validate([
            'date'         => 'required|date|before_or_equal:today',
            'hours_worked' => 'required|numeric|min:0.5|max:24',
            'description'  => 'required|string|max:2000',
            'attachments'  => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:zip,pdf,jpg,png,doc,docx,mp4|max:20480',
        ]);

        $exists = WorkDiary::where('job_id', $jobId)
            ->where('user_id', $user->id)
            ->whereDate('date', $request->date)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'A diary entry already exists for this date.'
            ], 409);
        }

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachmentPaths[] = $file->store('work-diary', 'public');
            }
        }

        $entry = WorkDiary::create([
            'job_id'       => $jobId,
            'user_id'      => $user->id,
            'date'         => $request->date,
            'hours_worked' => $request->hours_worked,
            'description'  => $request->description,
            'attachments'  => $attachmentPaths,
            'status'       => 'submitted',
        ]);

        return response()->json(['status' => true, 'data' => $entry], 201);
    }

    /**
     * EMPLOYER: Approve or reject a diary entry
     */
    public function review(Request $request, $jobId, $diaryId)
    {
        $companyId = Company::where('user_id', Auth::id())->value('id');
        $job = Job::where('id', $jobId)->where('company_id', $companyId)->firstOrFail();

        $request->validate([
            'status'     => 'required|in:approved,rejected',
            'review_note' => 'nullable|string|max:20480',
        ]);

        $entry = WorkDiary::where('id', $diaryId)->where('job_id', $jobId)->firstOrFail();

        if ($entry->status !== 'submitted') {
            return response()->json([
                'status' => false,
                'message' => 'This entry has already been reviewed.'
            ], 400);
        }

        $entry->update([
            'status'      => $request->status,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => $request->review_note,
        ]);

        return response()->json(['status' => true, 'data' => $entry->fresh()]);
    }
}
