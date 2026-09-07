<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Escrow;
use App\Models\ProjectDelivery;
use App\Models\Wallet;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\Company;
use App\Models\User;
use App\Models\Milestone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Notification\NotificationService;
use App\Services\Notification\AdminEmailService;

class WorkspaceController extends Controller
{
    /**
     * CANDIDATE: Get Active Projects & Delivery History
     */
    public function candidateWorkspace()
    {
        $jobs = Job::where('assigned_to', Auth::id())
            ->where('is_remote_project', true)
            ->with(['company', 'category'])
            ->latest()
            ->get();

        foreach ($jobs as $job) {
            // Retrieve all delivery attempts for history tracking
            $job->deliveries = ProjectDelivery::where('job_id', $job->id)->latest()->get();
            $job->escrow = Escrow::where('job_id', $job->id)->first();
        }

        return response()->json(['status' => true, 'data' => $jobs]);
    }

    /**
     * CANDIDATE: Submit Work / Resubmit Revision
     */
    public function submitWork(Request $request, $jobId)
    {
        $request->validate([
            'message' => 'required|string',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:zip,pdf,jpg,png,doc,docx|max:20480',
        ]);

        $job = Job::where('id', $jobId)->where('assigned_to', Auth::id())->firstOrFail();

        if (in_array($job->project_status, ['completed', 'disputed', 'cancelled'])) {
            return response()->json(['status' => false, 'message' => 'Project is locked and cannot accept new submissions.'], 403);
        }

        $attachmentPaths = [];
        foreach ($request->file('attachments') ?? [] as $file) {
            $attachmentPaths[] = $file->store('deliveries', 'public');
        }

        DB::transaction(function () use ($job, $request, $attachmentPaths) {
            ProjectDelivery::create([
                'job_id' => $job->id,
                'candidate_id' => Auth::id(),
                'message' => $request->message,
                'attachments' => $attachmentPaths,
                'status' => 'submitted',
            ]);

            $job->update(['project_status' => 'submitted']);
        });

        // Email employer about new submission
        $employerUser = User::find(Company::where('id', $job->company_id)->value('user_id'));
        if ($employerUser) {
            AdminEmailService::notifyUser(
                $employerUser,
                'New work submitted for "' . $job->title . '"',
                'emails.workspace_update',
                [
                    'userName' => $employerUser->name,
                    'action' => 'submitted',
                    'jobTitle' => $job->title,
                    'message' => Auth::user()->name . ' has submitted work for your project. Please review the submission.',
                    'workspaceUrl' => config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/employer/workspace',
                ]
            );
        }

        return response()->json(['status' => true, 'message' => 'Work submitted successfully. Awaiting employer review.']);
    }

    /**
     * EMPLOYER: Get Active Projects & Deliveries
     */
    public function employerWorkspace()
    {
        $companyId = Company::where('user_id', Auth::id())->value('id');

        if (!$companyId) {
            return response()->json(['status' => false, 'message' => 'Company profile not found'], 404);
        }

        $jobs = Job::where('company_id', $companyId)
            ->where('is_remote_project', true)
            ->whereNotNull('assigned_to')
            ->latest()
            ->get();

        foreach ($jobs as $job) {
            $job->candidate = User::find($job->assigned_to);
            $job->deliveries = ProjectDelivery::where('job_id', $job->id)->latest()->get();
            $job->escrow = Escrow::where('job_id', $job->id)->first();
        }

        return response()->json(['status' => true, 'data' => $jobs]);
    }

    /**
     * EMPLOYER: Request Revision
     */
    public function requestRevision(Request $request, $jobId)
    {
        $request->validate(['revision_note' => 'required|string']);
        
        $companyId = Company::where('user_id', Auth::id())->value('id');
        $job = Job::where('id', $jobId)->where('company_id', $companyId)->firstOrFail();
        
        $latestDelivery = ProjectDelivery::where('job_id', $job->id)->latest()->firstOrFail();

        DB::transaction(function () use ($job, $latestDelivery, $request) {
            $latestDelivery->update(['status' => 'revision_requested']);
            $job->update(['project_status' => 'revision_requested']);
            
            // Log the revision request in the conversation for transparency
            $conversationId = Conversation::where('job_id', $job->id)->value('id');
            if ($conversationId) {
                Message::create([
                    'conversation_id' => $conversationId,
                    'sender_id' => Auth::id(),
                    'message' => "🔴 Revision Requested: " . $request->revision_note,
                    'is_read' => false
                ]);
            }
        });

        $candidate = User::find($job->assigned_to);
        if ($candidate) {
            app(NotificationService::class)->sendNotification(
                $candidate,
                'Revision Requested',
                'The employer has requested revisions for "' . $job->title . '". Reason: ' . $request->revision_note,
                'workspace',
                '/dashboard/workspace'
            );

            AdminEmailService::notifyUser(
                $candidate,
                'Revision requested for "' . $job->title . '"',
                'emails.workspace_update',
                [
                    'userName' => $candidate->name,
                    'action' => 'revision_requested',
                    'jobTitle' => $job->title,
                    'message' => 'The employer has requested revisions: ' . $request->revision_note,
                    'workspaceUrl' => config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/dashboard/workspace',
                ]
            );
        }

        return response()->json(['status' => true, 'message' => 'Revision requested. Candidate has been notified.']);
    }

    /**
     * EMPLOYER: Reject Work (completely reject, not requesting revision)
     */
    public function rejectWork(Request $request, $jobId)
    {
        $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        $companyId = Company::where('user_id', Auth::id())->value('id');
        $job = Job::where('id', $jobId)->where('company_id', $companyId)->firstOrFail();

        $latestDelivery = ProjectDelivery::where('job_id', $job->id)->latest()->firstOrFail();

        DB::transaction(function () use ($job, $latestDelivery, $request) {
            $latestDelivery->update(['status' => 'rejected']);
            $job->update(['project_status' => 'revision_requested']);

            $conversationId = Conversation::where('job_id', $job->id)->value('id');
            if ($conversationId) {
                Message::create([
                    'conversation_id' => $conversationId,
                    'sender_id' => Auth::id(),
                    'message' => "❌ Work Rejected: " . $request->reason,
                    'is_read' => false,
                ]);
            }
        });

        $candidate = User::find($job->assigned_to);
        if ($candidate) {
            app(NotificationService::class)->sendNotification(
                $candidate,
                'Work Rejected',
                'Your submission for "' . $job->title . '" has been rejected. Reason: ' . $request->reason,
                'workspace',
                '/dashboard/workspace'
            );

            AdminEmailService::notifyUser(
                $candidate,
                'Your work has been rejected for "' . $job->title . '"',
                'emails.workspace_update',
                [
                    'userName' => $candidate->name,
                    'action' => 'rejected',
                    'jobTitle' => $job->title,
                    'message' => 'Your submission has been rejected. Reason: ' . $request->reason,
                    'workspaceUrl' => config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/dashboard/workspace',
                ]
            );
        }

        return response()->json(['status' => true, 'message' => 'Work rejected. Candidate has been notified.']);
    }

    /**
     * EMPLOYER: Approve Work & Release Escrow
     */
    public function releasePayment($jobId)
    {
        $companyId = Company::where('user_id', Auth::id())->value('id');
        $job = Job::where('id', $jobId)->where('company_id', $companyId)->firstOrFail();
        
        $escrow = Escrow::where('job_id', $job->id)->where('status', 'funded')->firstOrFail();
        $latestDelivery = ProjectDelivery::where('job_id', $job->id)->latest()->firstOrFail();

        try {
            DB::transaction(function () use ($job, $escrow, $latestDelivery) {
                // Read fee settings
                $feePayer = \App\Models\Setting::where('key', 'escrow_fee_payer')->value('value') ?? 'candidate';
                $feePercentSetting = \App\Models\Setting::where('key', 'escrow_fee_percent')->first();
                $feePercent = $feePercentSetting ? (float) $feePercentSetting->value : 2.00;

                // If employer already paid fee at lock time, release full amount to candidate
                // If candidate pays fee, deduct from candidate payout
                if ($feePayer === 'employer') {
                    $platformFee = 0;
                    $candidatePayout = $escrow->amount;
                } else {
                    $platformFee = $escrow->amount * ($feePercent / 100.00);
                    $candidatePayout = $escrow->amount - $platformFee;
                }

                // Deduct from Employer Locked Wallet BEFORE updating escrow (Bug #1 fix)
                // If employer paid fee at lock, locked amount = budget + fee, so deduct full amount
                $employerWallet = Wallet::where('user_id', Auth::id())->lockForUpdate()->first();
                $originalPlatformFee = $escrow->platform_fee ?? 0;
                $employerWallet->locked_balance -= ($escrow->amount + ($feePayer === 'employer' ? $originalPlatformFee : 0));
                $employerWallet->save();

                // Update Project States
                $job->update(['project_status' => 'completed']);
                $latestDelivery->update(['status' => 'approved']);
                $escrow->update(['status' => 'released', 'platform_fee' => $platformFee]);

                // Credit Candidate Wallet
                $candidateWallet = Wallet::firstOrCreate(['user_id' => $job->assigned_to]);
                $candidateWallet->credit(
                    $candidatePayout,
                    'project_payout',
                    $escrow->id,
                    'Payout for project: ' . $job->title
                );

                // Credit Admin Wallet with Platform Commission (only if candidate pays fee)
                if ($feePayer === 'candidate' && $platformFee > 0) {
                    $admin = \App\Models\User::role('admin')->first() ?? \App\Models\User::first();
                    if ($admin) {
                        $adminWallet = Wallet::firstOrCreate(['user_id' => $admin->id]);
                        $adminWallet->credit(
                            $platformFee,
                            'project_commission',
                            $escrow->id,
                            'Commission from project: ' . $job->title
                        );
                    }
                }
            });

            // Notification uses actual candidatePayout (Bug #3 fix)
            $candidate = User::find($job->assigned_to);
            if ($candidate) {
                $feePayer = \App\Models\Setting::where('key', 'escrow_fee_payer')->value('value') ?? 'candidate';
                if ($feePayer === 'employer') {
                    $notifPayout = $escrow->amount;
                } else {
                    $feePercentSetting = \App\Models\Setting::where('key', 'escrow_fee_percent')->first();
                    $feePercent = $feePercentSetting ? (float) $feePercentSetting->value : 2.00;
                    $notifPayout = $escrow->amount - ($escrow->amount * ($feePercent / 100.00));
                }

                app(NotificationService::class)->sendNotification(
                    $candidate,
                    'Escrow Payment Released',
                    'Your payment of ' . number_format($notifPayout, 2) . ' BDT for project "' . $job->title . '" has been released to your wallet.',
                    'billing',
                    '/dashboard/wallet'
                );

                AdminEmailService::notifyUser(
                    $candidate,
                    'Payment released for "' . $job->title . '"',
                    'emails.workspace_update',
                    [
                        'userName' => $candidate->name,
                        'action' => 'payment_released',
                        'jobTitle' => $job->title,
                        'message' => 'Your payment of ' . number_format($notifCandidatePayout, 2) . ' BDT has been released to your wallet.',
                        'workspaceUrl' => config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/dashboard/wallet',
                    ]
                );
            }

            return response()->json(['status' => true, 'message' => 'Funds released! Project officially completed.']);

        } catch (\Exception $e) {
            Log::error("Release Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to release funds.'], 500);
        }
    }

    /**
     * WORKSPACE: Fetch Encrypted Secure Credentials Safe
     */
    public function getSecureCredentials($jobId)
    {
        $user = Auth::user();
        $job = Job::findOrFail($jobId);

        $isEmployer = Company::where('user_id', $user->id)->value('id') === $job->company_id;
        $isCandidate = $job->assigned_to === $user->id;

        if (!$isEmployer && !$isCandidate) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $credentials = \App\Models\SecureCredential::where('job_id', $job->id)
            ->where('is_deleted', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->get();

        return response()->json(['status' => true, 'data' => $credentials]);
    }

    /**
     * WORKSPACE: Add New Encrypted Secure Credential
     */
    public function storeSecureCredential(Request $request, $jobId)
    {
        $request->validate([
            'key_name' => 'required|string|max:255',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'notes' => 'nullable|string',
            'expiry_hours' => 'nullable|integer|min:1'
        ]);

        $user = Auth::user();
        $job = Job::findOrFail($jobId);

        $isEmployer = Company::where('user_id', $user->id)->value('id') === $job->company_id;
        $isCandidate = $job->assigned_to === $user->id;

        if (!$isEmployer && !$isCandidate) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $expiresAt = $request->expiry_hours ? now()->addHours($request->expiry_hours) : null;

        $credential = \App\Models\SecureCredential::create([
            'job_id' => $job->id,
            'key_name' => $request->key_name,
            'username' => $request->username,
            'password' => $request->password,
            'notes' => $request->notes,
            'expires_at' => $expiresAt
        ]);

        $conversationId = Conversation::where('job_id', $job->id)->value('id');
        if ($conversationId) {
            Message::create([
                'conversation_id' => $conversationId,
                'sender_id' => Auth::id(),
                'message' => "🔐 Secure Credential Added: " . $request->key_name . ($expiresAt ? " (Expires in {$request->expiry_hours}h)" : ""),
                'is_read' => false
            ]);
        }

        return response()->json(['status' => true, 'message' => 'Credential successfully encrypted and stored.', 'data' => $credential]);
    }

    /**
     * WORKSPACE: Delete Secure Credential (disposable logic)
     */
    public function deleteSecureCredential($jobId, $credentialId)
    {
        $user = Auth::user();
        $job = Job::findOrFail($jobId);

        $isEmployer = Company::where('user_id', $user->id)->value('id') === $job->company_id;
        $isCandidate = $job->assigned_to === $user->id;

        if (!$isEmployer && !$isCandidate) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $credential = \App\Models\SecureCredential::where('job_id', $job->id)->findOrFail($credentialId);
        $credential->update(['is_deleted' => true]);

        return response()->json(['status' => true, 'message' => 'Credential deleted permanently.']);
    }

    /**
     * WORKSPACE: Get Active Dispute
     */
    public function getDispute($jobId)
    {
        $user = Auth::user();
        $job = Job::findOrFail($jobId);

        $isEmployer = Company::where('user_id', $user->id)->value('id') === $job->company_id;
        $isCandidate = $job->assigned_to === $user->id;

        if (!$isEmployer && !$isCandidate) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $dispute = \App\Models\Dispute::where('job_id', $job->id)->with('openedBy:id,name,username')->latest()->first();

        return response()->json(['status' => true, 'data' => $dispute]);
    }

    /**
     * EITHER PARTY: Open Dispute (Upgraded)
     */
    public function openDispute(Request $request, $jobId)
    {
        $request->validate([
            'reason' => 'required|string|min:20',
            'proof' => 'nullable|file|mimes:zip,pdf,jpg,png|max:20480'
        ]);

        $user = Auth::user();
        $job = Job::findOrFail($jobId);

        $isEmployer = Company::where('user_id', $user->id)->value('id') === $job->company_id;
        $isCandidate = $job->assigned_to === $user->id;

        if (!$isEmployer && !$isCandidate) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $proofPath = $request->hasFile('proof') ? $request->file('proof')->store('disputes', 'public') : null;

        try {
            DB::transaction(function () use ($job, $user, $request, $proofPath) {
                $job->update(['project_status' => 'disputed']);
                Escrow::where('job_id', $job->id)->update(['status' => 'disputed']);

                \App\Models\Dispute::create([
                    'job_id' => $job->id,
                    'opened_by' => $user->id,
                    'reason' => $request->reason,
                    'proof_path' => $proofPath,
                    'status' => 'open'
                ]);

                $conversationId = Conversation::where('job_id', $job->id)->value('id');
                if ($conversationId) {
                    Message::create([
                        'conversation_id' => $conversationId,
                        'sender_id' => $user->id,
                        'message' => "⚠️ Dispute Opened: \"{$request->reason}\" (Admin mediation requested)",
                        'is_read' => false
                    ]);
                }
            });

            return response()->json(['status' => true, 'message' => 'Dispute opened. Admin desk has been notified.']);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Dispute registration failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * EMPLOYER: Release payment for a specific milestone
     */
    public function releaseMilestonePayment(Request $request, $jobId, $milestoneId)
    {
        $companyId = Company::where('user_id', Auth::id())->value('id');
        $job = Job::where('id', $jobId)->where('company_id', $companyId)->firstOrFail();

        $milestone = Milestone::where('id', $milestoneId)
            ->where('job_id', $jobId)
            ->whereIn('status', ['submitted', 'approved'])
            ->firstOrFail();

        $escrow = Escrow::where('job_id', $job->id)->where('status', 'funded')->firstOrFail();

        if ($milestone->amount > $escrow->amount) {
            return response()->json(['status' => false, 'message' => 'Milestone amount exceeds escrow balance.'], 422);
        }

        try {
            DB::transaction(function () use ($job, $escrow, $milestone) {
                $serviceChargeSetting = \App\Models\Setting::where('key', 'remote_job_service_charge')->first();
                $percentage = $serviceChargeSetting ? (float) $serviceChargeSetting->value : 5.00;

                $platformFee = $milestone->amount * ($percentage / 100.00);
                $candidatePayout = $milestone->amount - $platformFee;

                $milestone->update(['status' => 'paid', 'completed_at' => now()]);

                $employerWallet = Wallet::where('user_id', Auth::id())->lockForUpdate()->first();
                $employerWallet->locked_balance -= $milestone->amount;
                $employerWallet->save();

                $candidateWallet = Wallet::firstOrCreate(['user_id' => $job->assigned_to]);
                $candidateWallet->credit(
                    $candidatePayout,
                    'milestone_payout',
                    $milestone->id,
                    'Milestone payment for: ' . $milestone->title
                );

                $admin = \App\Models\User::role('admin')->first() ?? \App\Models\User::first();
                if ($admin && $platformFee > 0) {
                    $adminWallet = Wallet::firstOrCreate(['user_id' => $admin->id]);
                    $adminWallet->credit(
                        $platformFee,
                        'milestone_commission',
                        $milestone->id,
                        'Commission from milestone: ' . $milestone->title
                    );
                }

                // Check if all milestones are paid → complete project
                $allPaid = Milestone::where('job_id', $job->id)->where('status', '!=', 'paid')->count() === 0;
                if ($allPaid) {
                    $job->update(['project_status' => 'completed']);
                    $escrow->update(['status' => 'released', 'platform_fee' => $escrow->amount * ($percentage / 100.00)]);
                }
            });

            $candidate = User::find($job->assigned_to);
            if ($candidate) {
                app(NotificationService::class)->sendNotification(
                    $candidate,
                    'Milestone Payment Released',
                    'Payment for milestone "' . $milestone->title . '" has been released to your wallet.',
                    'billing',
                    '/dashboard/wallet'
                );

                AdminEmailService::notifyUser(
                    $candidate,
                    'Milestone payment released for "' . $job->title . '"',
                    'emails.workspace_update',
                    [
                        'userName' => $candidate->name,
                        'action' => 'milestone_payment_released',
                        'jobTitle' => $job->title,
                        'message' => 'Payment for milestone "' . $milestone->title . '" has been released to your wallet.',
                        'workspaceUrl' => config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/dashboard/wallet',
                    ]
                );
            }

            return response()->json(['status' => true, 'message' => 'Milestone payment released.']);

        } catch (\Exception $e) {
            Log::error("Milestone Release Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to release milestone payment.'], 500);
        }
    }
}