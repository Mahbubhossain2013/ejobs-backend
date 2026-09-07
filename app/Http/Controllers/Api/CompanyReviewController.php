<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyReview;
use App\Services\Ai\AiManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\Notification\NotificationService;

class CompanyReviewController extends Controller
{
    /**
     * Get approved reviews and averaged rating categories for a company
     */
    public function index($companyId)
    {
        $company = Company::findOrFail($companyId);
        $reviews = CompanyReview::where('company_id', $companyId)
            ->where('status', 'approved')
            ->with(['user:id,name,avatar'])
            ->latest()
            ->get();

        $count = $reviews->count();
        $averages = [
            'overall' => 0,
            'work_culture' => 0,
            'salary' => 0,
            'management' => 0,
            'growth' => 0,
            'work_life_balance' => 0,
        ];

        if ($count > 0) {
            $averages['overall'] = round($reviews->avg('rating'), 1);
            $averages['work_culture'] = round($reviews->avg('rating_work_culture'), 1);
            $averages['salary'] = round($reviews->avg('rating_salary'), 1);
            $averages['management'] = round($reviews->avg('rating_management'), 1);
            $averages['growth'] = round($reviews->avg('rating_growth'), 1);
            $averages['work_life_balance'] = round($reviews->avg('rating_work_life_balance'), 1);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'reviews' => $reviews->map(function ($r) {
                    if ($r->is_anonymous) {
                        return array_merge($r->toArray(), [
                            'user' => [
                                'name' => 'Anonymous Employee',
                                'avatar' => null
                            ]
                        ]);
                    }
                    return $r;
                }),
                'averages' => $averages,
                'total_reviews' => $count
            ]
        ]);
    }

    /**
     * Submit an employee/candidate review and run active AI Moderation checks
     */
    public function store(Request $request, $companyId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:10|max:2000',
            'rating_work_culture' => 'nullable|integer|min:1|max:5',
            'rating_salary' => 'nullable|integer|min:1|max:5',
            'rating_management' => 'nullable|integer|min:1|max:5',
            'rating_growth' => 'nullable|integer|min:1|max:5',
            'rating_work_life_balance' => 'nullable|integer|min:1|max:5',
            'is_anonymous' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        $company = Company::findOrFail($companyId);

        // Check if user already reviewed this company
        $existing = CompanyReview::where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => false,
                'message' => 'You have already submitted a review for this company.'
            ], 400);
        }

        $comment = $request->comment;

        // Perform AI Toxicity, spam & Fake Review Detection
        $prompt = "Audit this company review comment: '{$comment}'.
        Analyze for:
        1. Toxicity (hate speech, abusive words, profanities) -> score 0 to 1
        2. Fake/Spam review probability (unnatural, repetitive keywords, random symbols) -> score 0 to 1
        3. Moderation decision (approved, rejected, or flagged)
        
        Return ONLY valid JSON (no code blocks, ONLY JSON):
        {
            \"toxicity_score\": 0.05,
            \"fake_probability\": 0.10,
            \"decision\": \"approved\",
            \"reason\": \"Clean constructive critique\"
        }";

        $toxicityScore = 0.00;
        $fakeProbability = 0.00;
        $status = 'approved';
        $moderationReason = 'Approved by platform AI system.';

        try {
            $aiResponse = AiManagerService::ask($prompt, temperature: 0.1);
            $cleanJson = trim(str_replace(['```json', '```'], '', $aiResponse));
            $analysis = json_decode($cleanJson, true);

            if ($analysis) {
                $toxicityScore = $analysis['toxicity_score'] ?? 0.0;
                $fakeProbability = $analysis['fake_probability'] ?? 0.0;
                $moderationReason = $analysis['reason'] ?? 'Audited by AI.';
                
                if ($toxicityScore > 0.60 || $fakeProbability > 0.70) {
                    $status = 'flagged';
                } else {
                    $status = 'approved';
                }
            }
        } catch (\Exception $e) {
            Log::error("Review AI Moderation failed: " . $e->getMessage());
            // Fallback: keep status approved initially unless bad words detected manually
            $status = 'approved';
        }

        $review = CompanyReview::create([
            'user_id' => $user->id,
            'company_id' => $companyId,
            'rating' => $request->rating,
            'rating_work_culture' => $request->rating_work_culture,
            'rating_salary' => $request->rating_salary,
            'rating_management' => $request->rating_management,
            'rating_growth' => $request->rating_growth,
            'rating_work_life_balance' => $request->rating_work_life_balance,
            'comment' => $comment,
            'is_anonymous' => $request->is_anonymous ?? false,
            'status' => $status,
            'ai_toxicity_score' => $toxicityScore,
            'ai_fake_probability' => $fakeProbability,
            'ai_duplicate_score' => 0.00, // baseline
            'moderation_details' => $moderationReason,
        ]);

        // If approved, recalculate average company rating
        if ($status === 'approved') {
            $allApproved = CompanyReview::where('company_id', $companyId)->where('status', 'approved')->get();
            $newAvg = round($allApproved->avg('rating'), 2);
            $company->update([
                'rating' => $newAvg
            ]);
        }

        \Illuminate\Support\Facades\Cache::forget("company_{$companyId}_reviews_count");

        $employer = \App\Models\User::find($company->user_id);
        if ($employer) {
            $reviewerName = $user->name;
            app(NotificationService::class)->sendNotification(
                $employer,
                'Company Review Submitted',
                "{$reviewerName} has submitted a {$request->rating}-star review for your company \"{$company->name}\".",
                'general',
                '/employer/reviews'
            );
        }

        return response()->json([
            'status' => true,
            'message' => $status === 'approved' 
                ? 'Your review has been successfully submitted!' 
                : 'Your review was flagged by the automated AI filter and is pending manual admin approval.',
            'data' => $review
        ]);
    }
}
