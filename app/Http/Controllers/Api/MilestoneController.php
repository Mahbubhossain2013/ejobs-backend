<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\Job;
use App\Models\Company;
use App\Models\Escrow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MilestoneController extends Controller
{
    /**
     * List milestones for a job
     */
    public function index($jobId)
    {
        $user = Auth::user();

        // Allow both employer (company owner) and assigned candidate
        $job = Job::where('id', $jobId)->firstOrFail();

        $isEmployer = Company::where('user_id', $user->id)->value('id') === $job->company_id;
        $isCandidate = $job->assigned_to === $user->id;

        if (!$isEmployer && !$isCandidate) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $milestones = Milestone::where('job_id', $jobId)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $milestones,
            'completion_percentage' => Milestone::completionPercentage($jobId),
            'approved_amount' => Milestone::approvedAmount($jobId),
        ]);
    }

    /**
     * EMPLOYER: Create milestones for a job
     */
    public function store(Request $request, $jobId)
    {
        $companyId = Company::where('user_id', Auth::id())->value('id');
        $job = Job::where('id', $jobId)->where('company_id', $companyId)->firstOrFail();

        $request->validate([
            'milestones'           => 'required|array|min:1|max:20',
            'milestones.*.title'   => 'required|string|max:255',
            'milestones.*.description' => 'nullable|string|max:2000',
            'milestones.*.amount'  => 'required|numeric|min:1',
            'milestones.*.deadline' => 'nullable|date|after:today',
        ]);

        $totalAmount = collect($request->milestones)->sum('amount');
        $escrow = Escrow::where('job_id', $jobId)->where('status', 'funded')->first();

        if ($escrow && $totalAmount > $escrow->amount) {
            return response()->json([
                'status' => false,
                'message' => "Total milestone amounts ({$totalAmount}) exceed escrow balance ({$escrow->amount})."
            ], 400);
        }

        DB::transaction(function () use ($request, $jobId) {
            // Clear existing milestones
            Milestone::where('job_id', $jobId)->delete();

            foreach ($request->milestones as $index => $ms) {
                Milestone::create([
                    'job_id'      => $jobId,
                    'title'       => $ms['title'],
                    'description' => $ms['description'] ?? null,
                    'amount'      => $ms['amount'],
                    'deadline'    => $ms['deadline'] ?? null,
                    'sort_order'  => $index + 1,
                    'status'      => 'pending',
                ]);
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'Milestones created successfully.',
            'data' => Milestone::where('job_id', $jobId)->orderBy('sort_order')->get(),
        ], 201);
    }

    /**
     * CANDIDATE: Mark a milestone as submitted for review
     */
    public function submit($jobId, $milestoneId)
    {
        $user = Auth::user();
        $job = Job::where('id', $jobId)->where('assigned_to', $user->id)->firstOrFail();

        $milestone = Milestone::where('id', $milestoneId)->where('job_id', $jobId)->firstOrFail();

        if (!in_array($milestone->status, ['pending', 'in_progress', 'rejected'])) {
            return response()->json([
                'status' => false,
                'message' => 'This milestone cannot be submitted in its current status.'
            ], 400);
        }

        $milestone->update([
            'status'       => 'submitted',
            'submitted_at' => now(),
        ]);

        return response()->json(['status' => true, 'data' => $milestone->fresh()]);
    }

    /**
     * EMPLOYER: Approve or reject a milestone
     */
    public function review(Request $request, $jobId, $milestoneId)
    {
        $companyId = Company::where('user_id', Auth::id())->value('id');
        $job = Job::where('id', $jobId)->where('company_id', $companyId)->firstOrFail();

        $request->validate([
            'status' => 'required|in:approved,rejected,in_progress',
        ]);

        $milestone = Milestone::where('id', $milestoneId)->where('job_id', $jobId)->firstOrFail();

        if ($milestone->status !== 'submitted') {
            return response()->json([
                'status' => false,
                'message' => 'Only submitted milestones can be reviewed.'
            ], 400);
        }

        $update = ['status' => $request->status];
        if ($request->status === 'approved') {
            $update['completed_at'] = now();
        }

        $milestone->update($update);

        return response()->json(['status' => true, 'data' => $milestone->fresh()]);
    }
}
