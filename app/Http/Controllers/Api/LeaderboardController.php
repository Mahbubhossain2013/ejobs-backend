<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Company;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category', 'trust');
        $type = $request->query('type', 'candidate');
        $perPage = min(50, max(1, intval($request->query('per_page', 10))));
        $page = max(1, intval($request->query('page', 1)));

        if ($type === 'candidate') {
            $query = UserProfile::query()
                ->with(['user'])
                ->whereHas('user', function ($q) {
                    $q->role('candidate');
                });

            if ($category === 'completions') {
                $query->orderBy('completed_jobs_count', 'desc');
            } elseif ($category === 'earnings') {
                $query->orderBy('total_earnings', 'desc');
            } else {
                $query->orderBy('trust_score', 'desc');
            }

            $total = $query->count();
            $results = $query->skip(($page - 1) * $perPage)->take($perPage)->get()->map(function ($profile) {
                return [
                    'rank' => null,
                    'user_id' => $profile->user_id,
                    'name' => $profile->user->name ?? 'Anonymous Candidate',
                    'username' => $profile->user->username ?? '',
                    'avatar' => $profile->avatar ? asset('storage/' . $profile->avatar) : null,
                    'trust_score' => $profile->trust_score,
                    'rating' => $profile->rating,
                    'completed_jobs_count' => $profile->completed_jobs_count,
                    'total_earnings' => $profile->total_earnings,
                    'reputation_status' => $profile->reputation_status,
                    'active_badges' => $profile->user ? $profile->user->activeBadges() : [],
                ];
            });
        } else {
            $query = Company::query()
                ->with(['user']);

            if ($category === 'completions') {
                $query->orderBy('completed_jobs_count', 'desc');
            } elseif ($category === 'earnings') {
                $query->orderBy('total_spend', 'desc');
            } else {
                $query->orderBy('trust_score', 'desc');
            }

            $total = $query->count();
            $results = $query->skip(($page - 1) * $perPage)->take($perPage)->get()->map(function ($company) {
                return [
                    'rank' => null,
                    'user_id' => $company->user_id,
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'logo' => $company->logo ? asset('storage/' . $company->logo) : null,
                    'trust_score' => $company->trust_score,
                    'rating' => $company->rating,
                    'completed_jobs_count' => $company->completed_jobs_count,
                    'total_spend' => $company->total_spend,
                    'reputation_status' => $company->reputation_status,
                    'active_badges' => $company->user ? $company->user->activeBadges() : [],
                ];
            });
        }

        return response()->json([
            'status' => true,
            'category' => $category,
            'type' => $type,
            'data' => $results,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int)ceil($total / $perPage),
            ],
        ]);
    }
}
