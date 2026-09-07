<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectRating;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectRatingController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'job_id' => 'required|exists:jobs,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'rated_id' => 'required|exists:users,id',
            'skill_rating' => 'required|integer|min:1|max:5',
            'communication_rating' => 'required|integer|min:1|max:5',
            'delivery_rating' => 'required|integer|min:1|max:5',
            'professionalism_rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
        ]);

        if ($validated['rated_id'] === $user->id) {
            return response()->json(['status' => false, 'message' => 'Cannot rate yourself.'], 422);
        }

        $existing = ProjectRating::where('job_id', $validated['job_id'])
            ->where('rater_id', $user->id)
            ->where('rated_id', $validated['rated_id'])
            ->first();

        if ($existing) {
            return response()->json(['status' => false, 'message' => 'You have already rated this user for this project.'], 422);
        }

        $isEmployer = $user->role === 'employer';
        $raterRole = $isEmployer ? 'employer' : 'candidate';

        $validated['rater_id'] = $user->id;
        $validated['rater_role'] = $raterRole;
        $validated['is_public'] = $validated['is_public'] ?? true;

        $rating = ProjectRating::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Rating submitted successfully.',
            'data' => $rating->fresh(['rater', 'rated']),
        ], 201);
    }

    public function getUserRatings($userId)
    {
        $ratings = ProjectRating::with(['rater', 'job'])
            ->where('rated_id', $userId)
            ->where('is_public', true)
            ->latest()
            ->paginate(15);

        $averages = ProjectRating::where('rated_id', $userId)
            ->where('is_public', true)
            ->selectRaw('
                AVG(skill_rating) as avg_skill,
                AVG(communication_rating) as avg_communication,
                AVG(delivery_rating) as avg_delivery,
                AVG(professionalism_rating) as avg_professionalism,
                COUNT(*) as total_ratings
            ')
            ->first();

        return response()->json([
            'status' => true,
            'data' => [
                'ratings' => $ratings,
                'averages' => [
                    'skill' => round($averages->avg_skill ?? 0, 2),
                    'communication' => round($averages->avg_communication ?? 0, 2),
                    'delivery' => round($averages->avg_delivery ?? 0, 2),
                    'professionalism' => round($averages->avg_professionalism ?? 0, 2),
                    'overall' => round(($averages->avg_skill + $averages->avg_communication + $averages->avg_delivery + $averages->avg_professionalism) / 4, 2),
                    'total' => $averages->total_ratings ?? 0,
                ],
            ],
        ]);
    }

    public function getJobRatings($jobId)
    {
        $ratings = ProjectRating::with(['rater', 'rated'])
            ->where('job_id', $jobId)
            ->where('is_public', true)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $ratings,
        ]);
    }

    public function checkEligibility($jobId)
    {
        $user = Auth::user();

        $isEmployer = $user->role === 'employer';

        $hasExisting = ProjectRating::where('job_id', $jobId)
            ->where('rater_id', $user->id)
            ->exists();

        $hasInteraction = false;
        $potentialRatedId = null;

        if ($isEmployer) {
            $application = JobApplication::where('job_id', $jobId)
                ->whereIn('status', ['accepted', 'completed'])
                ->first();
            if ($application) {
                $hasInteraction = true;
                $potentialRatedId = $application->user_id;
            }
        } else {
            $application = JobApplication::where('job_id', $jobId)
                ->where('user_id', $user->id)
                ->whereIn('status', ['accepted', 'completed'])
                ->first();
            if ($application) {
                $hasInteraction = true;
                $job = Job::find($jobId);
                $potentialRatedId = $job->user_id ?? $job->company_id;
            }
        }

        return response()->json([
            'status' => true,
            'data' => [
                'eligible' => $hasInteraction && !$hasExisting,
                'has_rated' => $hasExisting,
                'potential_rated_id' => $potentialRatedId,
            ],
        ]);
    }
}
