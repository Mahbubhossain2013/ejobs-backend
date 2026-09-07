<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractRevision;
use App\Models\Escrow;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Setting;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomHireController extends Controller
{
    private float $platformFeeRate = 0.10;

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'job_id' => 'nullable|exists:jobs,id',
            'candidate_id' => 'required|exists:users,id',
            'job_application_id' => 'nullable|exists:job_applications,id',
            'budget' => 'required|numeric|min:100',
        ]);

        $budget = $validated['budget'];
        $platformFee = round($budget * $this->platformFeeRate, 2);
        $totalAmount = $budget + $platformFee;

        $wallet = Wallet::where('user_id', Auth::id())->first();
        $hasEnoughBalance = $wallet && $wallet->balance >= $totalAmount;

        return response()->json([
            'status' => true,
            'data' => [
                'budget' => $budget,
                'platform_fee' => $platformFee,
                'platform_fee_rate' => $this->platformFeeRate * 100,
                'total_amount' => $totalAmount,
                'wallet_balance' => $wallet->balance ?? 0,
                'has_enough_balance' => $hasEnoughBalance,
            ],
        ]);
    }

    public function sendOffer(Request $request)
    {
        $validated = $request->validate([
            'job_id' => 'nullable|exists:jobs,id',
            'candidate_id' => 'required|exists:users,id',
            'job_application_id' => 'nullable|exists:job_applications,id',
            'title' => 'required|string|max:255',
            'job_title_custom' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'project_scope' => 'required|string',
            'deliverables' => 'nullable|array',
            'budget' => 'required|numeric|min:100',
            'budget_type' => 'nullable|string|in:fixed,hourly,daily,monthly',
            'delivery_date' => 'nullable|date|after:today',
            'ownership_clause' => 'nullable|string',
            'confidentiality_clause' => 'nullable|string',
            'penalty_clause' => 'nullable|string',
            'dispute_clause' => 'nullable|string',
            'additional_terms' => 'nullable|string',
        ]);

        $employerId = Auth::id();
        $candidateId = $validated['candidate_id'];
        $budget = $validated['budget'];
        $platformFee = round($budget * $this->platformFeeRate, 2);
        $totalAmount = $budget + $platformFee;

        $wallet = Wallet::where('user_id', $employerId)->first();
        if (!$wallet || $wallet->balance < $totalAmount) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient wallet balance.',
            ], 422);
        }

        $jobId = $validated['job_id'] ?? null;

        if (!$jobId) {
            $company = Company::where('user_id', $employerId)->first();
            $slug = Str::slug($validated['job_title_custom'] ?? $validated['title']);
            $existingCount = Job::where('slug', 'like', "{$slug}%")->count();
            if ($existingCount > 0) {
                $slug = "{$slug}-" . ($existingCount + 1);
            }

            $job = Job::create([
                'company_id' => $company?->id,
                'title' => $validated['job_title_custom'] ?? $validated['title'],
                'slug' => $slug,
                'description' => $validated['project_scope'],
                'job_type' => 'contract',
                'location' => 'Remote',
                'is_remote_project' => true,
                'budget' => $budget,
                'budget_type' => $validated['budget_type'] ?? 'fixed',
                'deadline' => $validated['delivery_date'] ?? now()->addDays(30),
                'visibility' => 'private',
                'is_active' => true,
                'project_status' => 'draft',
                'assigned_to' => $candidateId,
            ]);
            $jobId = $job->id;
        }

        $contract = Contract::create([
            'job_id' => $jobId,
            'employer_id' => $employerId,
            'candidate_id' => $candidateId,
            'job_application_id' => $validated['job_application_id'] ?? null,
            'title' => $validated['title'],
            'job_title_custom' => $validated['job_title_custom'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'project_scope' => $validated['project_scope'],
            'budget' => $budget,
            'platform_fee' => $platformFee,
            'total_amount' => $totalAmount,
            'delivery_date' => $validated['delivery_date'] ?? null,
            'budget_type' => $validated['budget_type'] ?? 'fixed',
            'is_remote_project' => true,
            'deliverables' => $validated['deliverables'] ?? null,
            'ownership_clause' => $validated['ownership_clause'] ?? null,
            'confidentiality_clause' => $validated['confidentiality_clause'] ?? null,
            'penalty_clause' => $validated['penalty_clause'] ?? null,
            'dispute_clause' => $validated['dispute_clause'] ?? null,
            'additional_terms' => $validated['additional_terms'] ?? null,
            'status' => 'draft',
            'offer_status' => 'pending_candidate',
            'offer_sent_at' => now(),
            'offer_expires_at' => now()->addDays(7),
        ]);

        try {
            $candidate = User::find($candidateId);
            if ($candidate) {
                app(NotificationService::class)->sendNotification(
                    $candidate,
                    'New Contract Offer',
                    "You have received a contract offer for \"{$contract->title}\". Please review and respond within 7 days.",
                    'contract',
                    "/dashboard/contracts/{$contract->id}"
                );
            }
        } catch (\Throwable $e) {
            Log::error("Contract offer notification failed: " . $e->getMessage());
        }

        if ($jobApplicationId = $validated['job_application_id'] ?? null) {
            JobApplication::where('id', $jobApplicationId)->update(['status' => 'offered']);
        }

        return response()->json([
            'status' => true,
            'message' => 'Offer sent to candidate.',
            'data' => $contract->fresh(['job', 'employer', 'candidate']),
        ], 201);
    }

    public function editOffer(Request $request, $contractId)
    {
        $user = Auth::user();
        $contract = Contract::where('id', $contractId)
            ->where('employer_id', $user->id)
            ->whereIn('offer_status', ['pending_candidate', 'draft'])
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'project_scope' => 'sometimes|string',
            'deliverables' => 'nullable|array',
            'budget' => 'sometimes|numeric|min:100',
            'budget_type' => 'nullable|string',
            'delivery_date' => 'nullable|date|after:today',
            'ownership_clause' => 'nullable|string',
            'confidentiality_clause' => 'nullable|string',
            'penalty_clause' => 'nullable|string',
            'dispute_clause' => 'nullable|string',
            'additional_terms' => 'nullable|string',
        ]);

        $previousData = $contract->only([
            'title', 'project_scope', 'budget', 'platform_fee', 'total_amount',
            'delivery_date', 'budget_type', 'deliverables',
            'ownership_clause', 'confidentiality_clause', 'penalty_clause',
            'dispute_clause', 'additional_terms',
        ]);

        if (isset($validated['budget'])) {
            $validated['platform_fee'] = round($validated['budget'] * $this->platformFeeRate, 2);
            $validated['total_amount'] = $validated['budget'] + $validated['platform_fee'];
        }

        $contract->update($validated);

        $newData = $contract->only(array_keys($previousData));

        $revision = ContractRevision::create([
            'contract_id' => $contract->id,
            'revised_by' => $user->id,
            'changes_summary' => 'Employer updated contract terms.',
            'previous_data' => $previousData,
            'new_data' => $newData,
            'requires_candidate_approval' => false,
            'status' => 'approved',
        ]);

        if (!isset($validated['budget'])) {
            return response()->json([
                'status' => true,
                'message' => 'Contract updated.',
                'data' => $contract->fresh(),
            ]);
        }

        $wallet = Wallet::where('user_id', $user->id)->first();
        $totalAmount = $validated['total_amount'] ?? $contract->total_amount;
        if ($wallet && $wallet->balance < $totalAmount) {
            return response()->json([
                'status' => true,
                'message' => 'Contract updated, but new budget exceeds wallet balance. Please top up before signing.',
                'data' => $contract->fresh(),
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Contract updated.',
            'data' => $contract->fresh(),
        ]);
    }

    public function acceptOffer(Request $request, $contractId)
    {
        $user = Auth::user();
        $contract = Contract::where('id', $contractId)
            ->where('candidate_id', $user->id)
            ->where('offer_status', 'pending_candidate')
            ->firstOrFail();

        if ($contract->isExpired()) {
            return response()->json([
                'status' => false,
                'message' => 'This offer has expired.',
            ], 422);
        }

        $contract->update(['offer_status' => 'candidate_accepted']);

        try {
            $employer = User::find($contract->employer_id);
            if ($employer) {
                app(NotificationService::class)->sendNotification(
                    $employer,
                    'Offer Accepted',
                    "Candidate has accepted your offer for \"{$contract->title}\". Please review and sign.",
                    'contract',
                    "/employer/contracts/{$contract->id}"
                );
            }
        } catch (\Throwable $e) {
            Log::error("Contract accept notification failed: " . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Offer accepted. Please sign to finalize.',
            'data' => $contract->fresh(),
        ]);
    }

    public function rejectOffer(Request $request, $contractId)
    {
        $user = Auth::user();
        $contract = Contract::where('id', $contractId)
            ->where('candidate_id', $user->id)
            ->where('offer_status', 'pending_candidate')
            ->firstOrFail();

        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|min:5',
        ]);

        $contract->update([
            'offer_status' => 'candidate_rejected',
            'status' => 'terminated',
            'rejection_reason' => $validated['rejection_reason'] ?? null,
            'terminated_at' => now(),
        ]);

        try {
            $employer = User::find($contract->employer_id);
            if ($employer) {
                app(NotificationService::class)->sendNotification(
                    $employer,
                    'Offer Declined',
                    "Candidate has declined your offer for \"{$contract->title}\".",
                    'contract',
                    "/employer/contracts"
                );
            }
        } catch (\Throwable $e) {
            Log::error("Contract reject notification failed: " . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Offer declined.',
        ]);
    }

    public function requestOtp(Request $request, $contractId)
    {
        $user = Auth::user();
        $contract = Contract::with(['employer', 'candidate'])->findOrFail($contractId);

        $isEmployer = $contract->employer_id === $user->id;
        $isCandidate = $contract->candidate_id === $user->id;

        if (!$isEmployer && !$isCandidate) {
            return response()->json(['status' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($isEmployer && !in_array($contract->offer_status, ['pending_candidate', 'candidate_accepted', 'candidate_signed'])) {
            return response()->json(['status' => false, 'message' => 'Contract is not in a signable state.'], 422);
        }
        if ($isCandidate && $contract->offer_status !== 'candidate_accepted') {
            return response()->json(['status' => false, 'message' => 'You must accept the offer before signing.'], 422);
        }

        $otp = Str::random(6);
        $expiresAt = now()->addMinutes(10);

        if ($isEmployer) {
            $contract->employer_otp = $otp;
            $contract->employer_otp_expires_at = $expiresAt;
        } else {
            $contract->candidate_otp = $otp;
            $contract->candidate_otp_expires_at = $expiresAt;
        }
        $contract->save();

        try {
            $recipient = $isEmployer ? $contract->employer : $contract->candidate;
            \Illuminate\Support\Facades\Mail::raw(
                "Your contract signing OTP is: {$otp}\nThis code expires in 10 minutes.",
                function ($mail) use ($recipient, $otp) {
                    $mail->to($recipient->email)
                        ->subject('Contract Signing OTP')
                        ->from('noreply@ejobs.bd', 'eJobs');
                }
            );
        } catch (\Exception $e) {
            Log::warning("Contract OTP email failed: " . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'OTP sent to your email.',
        ]);
    }

    public function sign(Request $request, $contractId)
    {
        $user = Auth::user();
        $contract = Contract::with(['employer', 'candidate'])->findOrFail($contractId);

        $isEmployer = $contract->employer_id === $user->id;
        $isCandidate = $contract->candidate_id === $user->id;

        if (!$isEmployer && !$isCandidate) {
            return response()->json(['status' => false, 'message' => 'Unauthorized.'], 403);
        }

        if (!$isEmployer && $contract->offer_status !== 'candidate_accepted') {
            return response()->json([
                'status' => false,
                'message' => 'Candidate must accept the offer before signing.',
            ], 422);
        }

        $validated = $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $storedOtp = $isEmployer ? $contract->employer_otp : $contract->candidate_otp;
        $expiresAt = $isEmployer ? $contract->employer_otp_expires_at : $contract->candidate_otp_expires_at;

        if (!$storedOtp || !$expiresAt || now()->greaterThan($expiresAt)) {
            return response()->json(['status' => false, 'message' => 'OTP has expired. Please request a new one.'], 422);
        }

        if (!hash_equals($storedOtp, $validated['otp'])) {
            return response()->json(['status' => false, 'message' => 'Invalid OTP.'], 422);
        }

        if ($isEmployer) {
            $contract->employer_signed_at = now();
            $contract->employer_otp = null;
            $contract->offer_status = 'employer_signed';
        } else {
            $contract->candidate_signed_at = now();
            $contract->candidate_otp = null;
            $contract->offer_status = 'candidate_signed';
        }

        if ($contract->isFullySigned()) {
            $contract->offer_status = 'active';
            $contract->status = 'active';

            $this->fundEscrow($contract);
        }

        $contract->save();

        return response()->json([
            'status' => true,
            'message' => $contract->isFullySigned() ? 'Contract signed and activated!' : 'Contract signed. Waiting for other party.',
            'data' => $contract->fresh(['employer', 'candidate']),
        ]);
    }

    protected function fundEscrow(Contract $contract): void
    {
        DB::transaction(function () use ($contract) {
            $job = Job::find($contract->job_id);
            $employerId = $contract->employer_id;
            $candidateId = $contract->candidate_id;

            $wallet = Wallet::where('user_id', $employerId)->lockForUpdate()->first();
            if (!$wallet) {
                throw new \Exception('Employer wallet not found.');
            }

            $feePayer = Setting::where('key', 'escrow_fee_payer')->value('value') ?? 'candidate';
            $feeAmount = (float) $contract->platform_fee;
            $totalLocked = $feePayer === 'employer'
                ? $contract->budget + $feeAmount
                : $contract->budget;

            if ($wallet->balance < $totalLocked) {
                throw new \Exception('Insufficient balance to fund escrow.');
            }

            $wallet->balance -= $totalLocked;
            $wallet->locked_balance += $totalLocked;
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'amount' => $totalLocked,
                'balance_after' => $wallet->balance,
                'reference_type' => 'escrow_funding',
                'reference_id' => $contract->id,
                'description' => "Escrow funding for contract: {$contract->title}",
                'status' => 'completed',
            ]);

            Escrow::create([
                'job_id' => $contract->job_id,
                'contract_id' => $contract->id,
                'employer_id' => $employerId,
                'candidate_id' => $candidateId,
                'amount' => $contract->budget,
                'platform_fee' => $feePayer === 'employer' ? $feeAmount : 0,
                'status' => 'funded',
            ]);

            if ($job && !$job->assigned_to) {
                $job->update([
                    'assigned_to' => $candidateId,
                    'project_status' => 'in_progress',
                ]);
            }

            try {
                $candidate = User::find($candidateId);
                if ($candidate) {
                    app(NotificationService::class)->sendNotification(
                        $candidate,
                        'Contract Activated',
                        "The contract \"{$contract->title}\" has been activated. You can now start working.",
                        'contract',
                        "/dashboard/workspace/{$job?->id}"
                    );
                }
            } catch (\Throwable $e) {
                Log::error("Contract activation notification failed: " . $e->getMessage());
            }
        });
    }

    public function employerContracts(Request $request)
    {
        $user = Auth::user();
        $status = $request->query('status');

        $query = Contract::with(['job', 'candidate', 'category'])
            ->where('employer_id', $user->id)
            ->whereNotNull('offer_sent_at');

        if ($status) {
            $query->where('offer_status', $status);
        }

        $contracts = $query->latest()->paginate(15);

        return response()->json([
            'status' => true,
            'data' => $contracts,
        ]);
    }

    public function employerShow($id)
    {
        $user = Auth::user();
        $contract = Contract::with(['job', 'candidate', 'employer', 'category', 'escrow', 'revisions.reviser'])
            ->where('id', $id)
            ->where('employer_id', $user->id)
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'data' => $contract,
        ]);
    }
}
