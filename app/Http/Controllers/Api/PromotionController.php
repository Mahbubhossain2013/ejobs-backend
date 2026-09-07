<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\Job;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PromotionController extends Controller
{
    /**
     * Get Employer/Candidate Promotions & Eligible Jobs
     */
    public function index()
    {
        try {
            $user = Auth::user();
            
            $eligibleJobs = [];
            if ($user->role === 'employer' || $user->hasRole('employer')) {
                $company = Company::where('user_id', $user->id)->first();
                if ($company) {
                    $eligibleJobs = Job::where('company_id', $company->id)
                        ->where('is_active', true)
                        ->select('id', 'title')
                        ->get();
                }
            }
            
            $promotions = Promotion::where('user_id', $user->id)
                ->with('job:id,title')
                ->latest()
                ->get();

            return response()->json([
                'status' => true,
                'data' => $promotions,
                'eligible_jobs' => $eligibleJobs
            ]);
            
        } catch (\Exception $e) {
            Log::error("Promotions API Error: " . $e->getMessage());
            
            // Return safe empty arrays instead of crashing React
            return response()->json([
                'status' => false, 
                'message' => 'Internal Server Error',
                'data' => [], 
                'eligible_jobs' => []
            ], 500);
        }
    }

    /**
     * Launch a New Campaign
     */
    public function store(Request $request)
    {
        $type = $request->input('type', 'sponsored_job');

        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'nullable|string|in:sponsored_job,profile_boost,awareness_ad,application_boost,company_branding',
            'job_id' => 'required_if:type,sponsored_job|exists:jobs,id',
            'daily_budget' => 'required|numeric|min:100',
            'total_budget' => 'required|numeric|gte:daily_budget',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $user = Auth::user();

        // 1. Run AI Validation & Moderation Pre-check
        $moderation = \App\Services\Ad\AdAiModerationService::moderateCampaign($request->all(), $user);
        if ($moderation['status'] === 'high_risk') {
            return response()->json([
                'status' => false,
                'message' => 'Campaign blocked by AI Brain Moderation.',
                'reason' => $moderation['reason']
            ], 422);
        }

        if ($type === 'profile_boost') {
            // Validate candidate permission
            if ($user->role !== 'candidate' && !$user->hasRole('candidate')) {
                return response()->json(['status' => false, 'message' => 'Only candidates can launch profile boosts.'], 403);
            }

            if (!$user->hasFeature('profile_boosting')) {
                return response()->json(['status' => false, 'message' => 'Your subscription plan does not allow profile boosting. Please upgrade your plan.'], 403);
            }

            // Verify Candidate profile completeness
            if (!$user->profile) {
                return response()->json(['status' => false, 'message' => 'Please create your candidate profile first.'], 403);
            }
            
            $jobId = null;
        } else {
            // Validate employer permission
            if ($user->role !== 'employer' && !$user->hasRole('employer')) {
                return response()->json(['status' => false, 'message' => 'Only employers can launch job promotions.'], 403);
            }

            if (!$user->hasFeature('premium_job_promotion')) {
                return response()->json(['status' => false, 'message' => 'Your subscription plan does not allow premium job promotion. Please upgrade your plan.'], 403);
            }

            // Check if Company Profile is Complete
            $company = Company::where('user_id', $user->id)->first();
            if (!$company) {
                return response()->json(['status' => false, 'message' => 'Please complete your company profile first before promoting jobs.'], 403);
            }

            $jobId = $request->job_id;
        }

        // 2. Validate Wallet Available balance
        $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();
        if (!$wallet || $wallet->balance < $request->daily_budget) {
            return response()->json(['status' => false, 'message' => 'Insufficient wallet balance. You need at least 1 day of budget (৳' . $request->daily_budget . ') to start this campaign.'], 402);
        }

        // 3. Determine status based on moderation outcomes
        $status = 'active';
        $rejectionReason = null;
        if ($moderation['status'] === 'uncertain') {
            $status = 'pending_review';
            $rejectionReason = $moderation['reason'];
        }

        // Create Promotion
        $promotion = Promotion::create([
            'user_id' => $user->id,
            'job_id' => $jobId,
            'title' => $request->title,
            'type' => $type,
            'campaign_type' => $type,
            'daily_budget' => $request->daily_budget,
            'total_budget' => $request->total_budget,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $status,
            'target_skills' => $request->target_skills ?? [],
            'target_location' => $request->target_location,
            'target_devices' => $request->target_devices ?? 'all',
            'rejection_reason' => $rejectionReason,
            'moderation_status' => $moderation['status']
        ]);

        // 4. Lock budget upfront if status is Active
        if ($status === 'active') {
            $locked = \App\Services\Ad\CampaignBillingService::lockUpfrontBudget($promotion);
            if (!$locked) {
                $promotion->update(['status' => 'paused', 'rejection_reason' => 'Wallet balance locking failed upfront.']);
            }
        }

        return response()->json([
            'status' => true,
            'message' => $status === 'pending_review' 
                ? 'Campaign submitted! AI placed it in Review Queue due to compliance rules.' 
                : 'Campaign launched successfully with upfront wallet lock!',
            'data' => $promotion
        ]);
    }

    /**
     * Pause / Activate Campaign
     */
    public function toggleStatus($id)
    {
        $promotion = Promotion::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $newStatus = $promotion->status === 'active' ? 'paused' : 'active';
        $promotion->update(['status' => $newStatus]);
        
        return response()->json(['status' => true, 'message' => 'Campaign ' . $newStatus]);
    }

    /**
     * View Campaign Analytics
     */
    public function analytics($id)
    {
        $promotion = Promotion::where('id', $id)
            ->where('user_id', Auth::id())
            ->with('job')
            ->firstOrFail();
            
        $applicationsCount = \App\Models\JobApplication::where('job_id', $promotion->job_id)
            ->where('created_at', '>=', $promotion->start_date)
            ->count();

        $ctr = $promotion->impressions > 0 ? round(($promotion->clicks / $promotion->impressions) * 100, 2) : 0;
        $cvr = $promotion->clicks > 0 ? round(($applicationsCount / $promotion->clicks) * 100, 2) : 0;
        $cpa = $applicationsCount > 0 ? round($promotion->spent_amount / $applicationsCount, 2) : 0;

        return response()->json([
            'status' => true,
            'data' => [
                'campaign' => $promotion,
                'metrics' => [
                    'impressions' => $promotion->impressions, 
                    'clicks' => $promotion->clicks,
                    'applications' => $applicationsCount, 
                    'ctr' => $ctr, 
                    'cvr' => $cvr, 
                    'cpa' => $cpa,
                    'remaining_budget' => max(0, $promotion->total_budget - $promotion->spent_amount)
                ]
            ]
        ]);
    }

    /**
     * Get AI Advertising Suggestions
     */
    public function getAiSuggestions($id)
    {
        return response()->json(['status' => true, 'suggestions' => [
            'Increase your daily budget to reach more candidates in your target area.', 
            'Refine your target skills to improve your click-through rate (CTR).',
            'Your conversion rate is below average. Consider updating the job description.'
        ]]);
    }

    /**
     * Update/Modify an existing Campaign
     */
    public function update(Request $request, $id)
    {
        try {
            $promotion = Promotion::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $request->validate([
                'title' => 'required|string|max:255',
                'daily_budget' => 'required|numeric|min:100',
                'total_budget' => 'required|numeric|gte:daily_budget',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
            ]);

            $user = Auth::user();

            // Run AI Validation & Moderation Check
            $moderation = \App\Services\Ad\AdAiModerationService::moderateCampaign($request->all(), $user);
            if ($moderation['status'] === 'high_risk') {
                return response()->json([
                    'status' => false,
                    'message' => 'Campaign update blocked by AI Brain Moderation.',
                    'reason' => $moderation['reason']
                ], 422);
            }
            // Check Wallet for budget increase if the new total budget is higher than original total budget
            $budgetDiff = $request->total_budget - $promotion->total_budget;
            if ($budgetDiff > 0) {
                $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();
                if (!$wallet || $wallet->balance < $budgetDiff) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Insufficient wallet balance to increase total campaign budget by ৳' . $budgetDiff
                    ], 402);
                }
            }

            $status = $promotion->status;
            $rejectionReason = $promotion->rejection_reason;
            if ($moderation['status'] === 'uncertain') {
                $status = 'pending_review';
                $rejectionReason = $moderation['reason'];
            }

            $promotion->update([
                'title' => $request->title,
                'daily_budget' => $request->daily_budget,
                'total_budget' => $request->total_budget,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'target_skills' => $request->target_skills ?? [],
                'target_location' => $request->target_location,
                'target_devices' => $request->target_devices ?? 'all',
                'status' => $status,
                'rejection_reason' => $rejectionReason,
                'moderation_status' => $moderation['status']
            ]);

            // Lock budget difference upfront if status is active
            if ($budgetDiff > 0 && $status === 'active') {
                // Deduct budget difference from wallet
                $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();
                if ($wallet) {
                    $wallet->decrement('balance', $budgetDiff);
                    $promotion->increment('spent_amount', $budgetDiff);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Campaign updated successfully!',
                'data' => $promotion
            ]);

        } catch (\Exception $e) {
            Log::error("Campaign Update Error: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to update campaign: ' . $e->getMessage()
            ], 500);
        }
    }
}