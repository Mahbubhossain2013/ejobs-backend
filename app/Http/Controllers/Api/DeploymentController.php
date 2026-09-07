<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deployment;
use App\Models\DeploymentStage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Notification\AdminEmailService;

class DeploymentController extends Controller
{
    /**
     * Default deployment stages template
     */
    private const DEFAULT_STAGES = [
        ['stage_name' => 'offer_letter', 'stage_label' => 'Offer Letter', 'order' => 1],
        ['stage_name' => 'visa_processing', 'stage_label' => 'Visa Processing', 'order' => 2],
        ['stage_name' => 'medical_test', 'stage_label' => 'Medical Test', 'order' => 3],
        ['stage_name' => 'police_clearance', 'stage_label' => 'Police Clearance', 'order' => 4],
        ['stage_name' => 'ticketing', 'stage_label' => 'Ticketing', 'order' => 5],
        ['stage_name' => 'flight', 'stage_label' => 'Flight', 'order' => 6],
        ['stage_name' => 'arrival_onboarding', 'stage_label' => 'Arrival & Onboarding', 'order' => 7],
    ];

    /**
     * List deployments for the authenticated user (candidate or employer)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Deployment::with(['stages', 'candidate', 'employer', 'job']);

        if ($user->hasRole('employer')) {
            $query->where('employer_id', $user->id);
        } else {
            $query->where('candidate_id', $user->id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $deployments = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'status' => true,
            'data' => $deployments,
        ]);
    }

    /**
     * Show a single deployment with all stages
     */
    public function show(int $id)
    {
        $user = Auth::user();
        $deployment = Deployment::with(['stages', 'candidate', 'employer', 'job'])
            ->findOrFail($id);

        // Authorization: only candidate or employer can view
        if ($deployment->candidate_id !== $user->id && $deployment->employer_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $deployment,
        ]);
    }

    /**
     * Employer: Create a new deployment for a candidate
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('employer')) {
            return response()->json(['status' => false, 'message' => 'Only employers can create deployments'], 403);
        }

        $validated = $request->validate([
            'candidate_id' => 'required|exists:users,id',
            'job_id' => 'nullable|exists:jobs,id',
            'job_title' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'destination_country' => 'required|string|max:100',
            'destination_city' => 'nullable|string|max:100',
            'expected_joining_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['employer_id'] = $user->id;
        $validated['status'] = 'initiated';

        $deployment = Deployment::create($validated);

        // Create default stages
        foreach (self::DEFAULT_STAGES as $stage) {
            $deployment->stages()->create($stage);
        }

        // Notify candidate about new deployment
        $candidate = User::find($validated['candidate_id']);
        if ($candidate) {
            AdminEmailService::notifyUser(
                $candidate,
                'New deployment initiated for "' . $validated['job_title'] . '"',
                'emails.deployment_update',
                [
                    'userName' => $candidate->name,
                    'subject' => 'New Deployment Initiated',
                    'message' => 'An employer has initiated a deployment for you. Job: ' . $validated['job_title'] . '. Destination: ' . $validated['destination_country'] . '. Please review the deployment details.',
                    'jobTitle' => $validated['job_title'],
                    'destinationCountry' => $validated['destination_country'],
                    'deploymentUrl' => config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/dashboard/workspace',
                ]
            );
        }

        return response()->json([
            'status' => true,
            'message' => 'Deployment initiated successfully',
            'data' => $deployment->load('stages'),
        ], 201);
    }

    /**
     * Employer: Update deployment stage status
     */
    public function updateStage(Request $request, int $stageId)
    {
        $user = $request->user();
        $stage = DeploymentStage::with('deployment')->findOrFail($stageId);

        if ($stage->deployment->employer_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Only the employer can update stages'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,failed',
            'remarks' => 'nullable|string',
            'deadline' => 'nullable|date',
            'document_path' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        if (!isset($validated['started_at']) && $validated['status'] === 'in_progress') {
            $validated['started_at'] = now();
        }
        if (!isset($validated['completed_at']) && $validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }

        $stage->update($validated);

        // Auto-update deployment status based on stages
        $this->recalculateDeploymentStatus($stage->deployment);

        // Notify candidate about stage update
        $candidate = User::find($stage->deployment->candidate_id);
        if ($candidate) {
            $stageLabels = [
                'offer_letter' => 'Offer Letter',
                'visa_processing' => 'Visa Processing',
                'medical_test' => 'Medical Test',
                'police_clearance' => 'Police Clearance',
                'ticketing' => 'Ticketing',
                'flight' => 'Flight',
                'arrival_onboarding' => 'Arrival & Onboarding',
            ];
            $stageLabel = $stageLabels[$stage->stage_name] ?? $stage->stage_name;

            AdminEmailService::notifyUser(
                $candidate,
                'Deployment stage updated: ' . $stageLabel,
                'emails.deployment_update',
                [
                    'userName' => $candidate->name,
                    'subject' => 'Deployment Stage Updated',
                    'message' => 'The deployment stage "' . $stageLabel . '" has been updated to ' . ucfirst($validated['status']) . '. ' . ($validated['remarks'] ?? ''),
                    'jobTitle' => $stage->deployment->job_title ?? 'N/A',
                    'stageName' => $stageLabel,
                    'stageStatus' => $validated['status'],
                    'destinationCountry' => $stage->deployment->destination_country ?? 'N/A',
                    'deploymentUrl' => config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/dashboard/workspace',
                ]
            );
        }

        return response()->json([
            'status' => true,
            'message' => 'Stage updated successfully',
            'data' => $stage->fresh()->load('deployment'),
        ]);
    }

    /**
     * Employer: Add a custom stage to deployment
     */
    public function addStage(Request $request, int $deploymentId)
    {
        $user = $request->user();
        $deployment = Deployment::findOrFail($deploymentId);

        if ($deployment->employer_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'stage_name' => 'required|string|max:100',
            'stage_label' => 'required|string|max:100',
            'deadline' => 'nullable|date',
            'order' => 'nullable|integer',
        ]);

        if (!isset($validated['order'])) {
            $validated['order'] = $deployment->stages()->max('order') + 1;
        }

        $stage = $deployment->stages()->create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Stage added',
            'data' => $stage,
        ], 201);
    }

    /**
     * Employer: Delete a custom stage
     */
    public function deleteStage(int $stageId)
    {
        $user = Auth::user();
        $stage = DeploymentStage::with('deployment')->findOrFail($stageId);

        if ($stage->deployment->employer_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $stage->delete();

        return response()->json(['status' => true, 'message' => 'Stage removed']);
    }

    /**
     * Recalculate deployment status based on stages
     */
    private function recalculateDeploymentStatus(Deployment $deployment): void
    {
        $stages = $deployment->stages;
        $totalStages = $stages->count();

        if ($totalStages === 0) {
            return;
        }

        $completedStages = $stages->where('status', 'completed')->count();
        $failedStages = $stages->where('status', 'failed')->count();

        if ($failedStages > 0) {
            $deployment->update(['status' => 'on_hold']);
        } elseif ($completedStages === $totalStages) {
            $deployment->update(['status' => 'completed']);
        } elseif ($completedStages > 0) {
            $deployment->update(['status' => 'in_progress']);
        }
    }
}
