<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->query('status');

        $query = Contract::with(['job', 'employer', 'candidate'])
            ->where(function ($q) use ($user) {
                $q->where('employer_id', $user->id)
                    ->orWhere('candidate_id', $user->id);
            });

        if ($status) {
            $query->where('status', $status);
        }

        $contracts = $query->latest()->paginate(15);

        return response()->json([
            'status' => true,
            'data' => $contracts,
        ]);
    }

    public function show($id)
    {
        $user = Auth::user();
        $contract = Contract::with(['job', 'employer', 'candidate', 'jobApplication'])
            ->where('id', $id)
            ->where(function ($q) use ($user) {
                $q->where('employer_id', $user->id)
                    ->orWhere('candidate_id', $user->id);
            })
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'data' => $contract,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'job_id' => 'required|exists:jobs,id',
            'candidate_id' => 'required|exists:users,id',
            'job_application_id' => 'nullable|exists:job_applications,id',
            'title' => 'required|string|max:255',
            'project_scope' => 'nullable|string',
            'budget' => 'required|numeric|min:0',
            'delivery_date' => 'nullable|date|after:today',
            'ownership_clause' => 'nullable|string',
            'confidentiality_clause' => 'nullable|string',
            'penalty_clause' => 'nullable|string',
            'dispute_clause' => 'nullable|string',
            'additional_terms' => 'nullable|string',
        ]);

        $job = Job::findOrFail($validated['job_id']);
        $platformFeeRate = 0.10; // 10%
        $platformFee = $validated['budget'] * $platformFeeRate;
        $totalAmount = $validated['budget'] + $platformFee;

        $contract = Contract::create([
            ...$validated,
            'employer_id' => $user->id,
            'platform_fee' => $platformFee,
            'total_amount' => $totalAmount,
            'status' => 'pending_employer',
        ]);

        // Notify candidate
        if (method_exists($this, 'sendContractNotification')) {
            $this->sendContractNotification($contract, 'created');
        }

        return response()->json([
            'status' => true,
            'message' => 'Contract created and sent to candidate for signing.',
            'data' => $contract,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $contract = Contract::where('id', $id)
            ->where('employer_id', $user->id)
            ->whereIn('status', ['draft', 'pending_employer'])
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'project_scope' => 'nullable|string',
            'budget' => 'sometimes|numeric|min:0',
            'delivery_date' => 'nullable|date|after:today',
            'ownership_clause' => 'nullable|string',
            'confidentiality_clause' => 'nullable|string',
            'penalty_clause' => 'nullable|string',
            'dispute_clause' => 'nullable|string',
            'additional_terms' => 'nullable|string',
        ]);

        if (isset($validated['budget'])) {
            $platformFeeRate = 0.10;
            $validated['platform_fee'] = $validated['budget'] * $platformFeeRate;
            $validated['total_amount'] = $validated['budget'] + $validated['platform_fee'];
        }

        $contract->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Contract updated.',
            'data' => $contract,
        ]);
    }

    public function requestOtp($id)
    {
        $user = Auth::user();
        $contract = Contract::with(['employer', 'candidate'])->findOrFail($id);

        if ($contract->employer_id !== $user->id && $contract->candidate_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized.'], 403);
        }

        $isEmployer = $contract->employer_id === $user->id;

        if ($isEmployer && !in_array($contract->offer_status, ['pending_candidate', 'candidate_accepted', 'candidate_signed', 'pending_employer', 'employer_signed'])) {
            return response()->json(['status' => false, 'message' => 'Contract is not in a signable state.'], 422);
        }
        if (!$isEmployer && !in_array($contract->offer_status, ['pending_candidate', 'candidate_accepted'])) {
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
            Mail::raw("Your contract signing OTP is: {$otp}\nThis code expires in 10 minutes.", function ($mail) use ($recipient) {
                $mail->to($recipient->email)
                    ->subject('Contract Signing OTP');
            });
        } catch (\Exception $e) {
            // OTP still saved even if email fails
        }

        return response()->json([
            'status' => true,
            'message' => 'OTP sent to your email.',
        ]);
    }

    public function sign(Request $request, $id)
    {
        $user = Auth::user();
        $contract = Contract::with(['employer', 'candidate'])->where(function ($q) use ($user) {
            $q->where('employer_id', $user->id)->orWhere('candidate_id', $user->id);
        })->findOrFail($id);

        $validated = $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $isEmployer = $contract->employer_id === $user->id;
        $isCandidate = $contract->candidate_id === $user->id;

        // Status guard: candidate must have accepted before signing
        if ($isCandidate && $contract->offer_status !== 'candidate_accepted') {
            return response()->json(['status' => false, 'message' => 'Offer must be accepted before signing.'], 422);
        }

        // Verify OTP
        $storedOtp = $isEmployer ? $contract->employer_otp : $contract->candidate_otp;
        $expiresAt = $isEmployer ? $contract->employer_otp_expires_at : $contract->candidate_otp_expires_at;

        if (!$storedOtp || !$expiresAt || now()->greaterThan($expiresAt)) {
            return response()->json(['status' => false, 'message' => 'OTP has expired. Please request a new one.'], 422);
        }

        if (!hash_equals($storedOtp, $validated['otp'])) {
            return response()->json(['status' => false, 'message' => 'Invalid OTP.'], 422);
        }

        // Sign
        if ($isEmployer) {
            $contract->employer_signed_at = now();
            $contract->employer_otp = null;
            $contract->status = 'employer_signed';
            $contract->offer_status = 'employer_signed';
        } else {
            $contract->candidate_signed_at = now();
            $contract->candidate_otp = null;
            $contract->status = 'candidate_signed';
            $contract->offer_status = 'candidate_signed';
        }

        // If both signed → active
        if ($contract->isFullySigned()) {
            $contract->status = 'active';
            $contract->offer_status = 'active';
        }

        $contract->save();

        return response()->json([
            'status' => true,
            'message' => 'Contract signed successfully.',
            'data' => $contract->fresh(['employer', 'candidate']),
        ]);
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        $contract = Contract::findOrFail($id);

        if ($contract->employer_id !== $user->id && $contract->candidate_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        $contract->update([
            'status' => 'terminated',
            'offer_status' => 'terminated',
            'rejection_reason' => $validated['rejection_reason'],
            'terminated_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Contract rejected.',
        ]);
    }

    public function terminate(Request $request, $id)
    {
        $user = Auth::user();
        $contract = Contract::where('id', $id)
            ->where('status', 'active')
            ->where(function ($q) use ($user) {
                $q->where('employer_id', $user->id)
                    ->orWhere('candidate_id', $user->id);
            })
            ->firstOrFail();

        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        $contract->update([
            'status' => 'terminated',
            'offer_status' => 'terminated',
            'rejection_reason' => $validated['rejection_reason'],
            'terminated_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Contract terminated.',
        ]);
    }

    public function fromApplication($applicationId)
    {
        $user = Auth::user();
        $application = JobApplication::with('job')->findOrFail($applicationId);

        $contract = Contract::where('job_application_id', $applicationId)
            ->where(function ($q) use ($user) {
                $q->where('employer_id', $user->id)->orWhere('candidate_id', $user->id);
            })
            ->first();

        if (!$contract) {
            return response()->json(['status' => false, 'message' => 'Contract not found'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $contract,
        ]);
    }
}
