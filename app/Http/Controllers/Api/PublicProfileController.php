<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicProfileController extends Controller
{
    public function publicSearchCandidates(Request $request)
    {
        $users = User::query()
            ->with('profile')
            ->whereHas('roles', fn($q) => $q->where('name', 'candidate'))
            ->whereHas('profile', fn($q) => $q->where('is_public', true))
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($user) {
                $profile = $user->profile;
                $skills = $profile->skills ?? [];
                $normalizedSkills = array_map(function ($skill) {
                    if (is_array($skill) && isset($skill['name'])) return $skill['name'];
                    return (string) $skill;
                }, $skills);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'avatar' => $user->avatar,
                    'bio' => $profile->bio ?? '',
                    'current_position' => $profile->current_position ?? '',
                    'city' => $profile->city ?? '',
                    'skills' => $normalizedSkills,
                    'trust_score' => $profile->trust_score ?? 100,
                    'is_featured' => $profile->is_featured ?? false,
                    'is_verified' => $user->hasBadge('verified'),
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $users,
        ]);
    }

    public function show($username)
    {
        $user = User::query()
            ->with(['profile', 'company'])
            ->when(is_numeric($username), fn ($q) => $q->where('users.id', (int) $username))
            ->when(!is_numeric($username), fn ($q) => $q->where('users.username', (string) $username))
            ->first();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'User not found'], 404);
        }

        if ($user->profile && !$user->profile->is_public) {
            return response()->json(['status' => false, 'message' => 'This profile is private'], 403);
        }

        // Normalize skills
        $skills = $user->profile->skills ?? [];
        $normalizedSkills = array_map(function ($skill) {
            if (is_array($skill) && isset($skill['name'])) {
                return $skill['name'];
            }
            return (string) $skill;
        }, $skills);

        $role = $user->getRoleNames()->first() ?? 'candidate';
        $profile = $user->profile;
        $company = $user->company;

        $actualJobs = $role === 'candidate'
            ? ($profile ? $profile->completed_jobs_count : 0)
            : ($company ? $company->completed_jobs_count : 0);

        $actualScore = $role === 'candidate'
            ? ($profile ? $profile->trust_score : 100)
            : ($company ? $company->trust_score : 100);

        $actualRating = $role === 'candidate'
            ? ($profile ? $profile->rating : 0.0)
            : ($company ? $company->rating : 0.0);

        $actualEarnings = $profile ? $profile->total_earnings : 0;
        $actualSpend = $company ? $company->total_spend : 0;

        $userBadges = $user->badges()->pluck('badges.id', 'badges.badge_key');
        $isVerified = $role === 'candidate'
            ? $userBadges->has('verified')
            : ($company ? (bool)$company->is_verified : false);

        $ownedBadgeIds = $userBadges->keys()->toArray();

        $lockedBadges = \App\Models\Badge::where('is_active', true)
            ->where('is_automatic', true)
            ->where('is_hidden', false)
            ->whereIn('role', [$role, 'both'])
            ->whereNotIn('id', $ownedBadgeIds)
            ->get()
            ->map(function ($badge) use ($actualJobs, $actualScore, $actualRating, $actualEarnings, $actualSpend, $isVerified) {
                $rules = $badge->rules;
                $progressDetails = [];
                $totalRules = 0;
                $passedRules = 0;
                $isNewFormat = false;
                if (is_array($rules) && count($rules) > 0) {
                    $first = reset($rules);
                    if (is_array($first) && isset($first['field'])) {
                        $isNewFormat = true;
                    }
                }

                if (!$isNewFormat) {
                    foreach (($rules ?? []) as $key => $targetVal) {
                        $totalRules++;
                        $currentVal = 0;
                        $passed = false;
                        if ($key === 'completed_jobs') {
                            $currentVal = $actualJobs;
                            $passed = $currentVal >= $targetVal;
                        } elseif ($key === 'trust_score') {
                            $currentVal = $actualScore;
                            $passed = $currentVal >= $targetVal;
                        } elseif ($key === 'rating') {
                            $currentVal = $actualRating;
                            $passed = $currentVal >= $targetVal;
                        } elseif ($key === 'earnings') {
                            $currentVal = $actualEarnings;
                            $passed = $currentVal >= $targetVal;
                        } elseif ($key === 'spend') {
                            $currentVal = $actualSpend;
                            $passed = $currentVal >= $targetVal;
                        } elseif ($key === 'verified') {
                            $currentVal = $isVerified ? 1 : 0;
                            $targetVal = filter_var($targetVal, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                            $passed = $currentVal == $targetVal;
                        }
                        if ($passed) $passedRules++;
                        $progressDetails[] = [
                            'field' => $key,
                            'current' => $currentVal,
                            'target' => $targetVal,
                            'passed' => $passed
                        ];
                    }
                } else {
                    foreach (($rules ?? []) as $rule) {
                        if (!isset($rule['field']) || !isset($rule['value'])) {
                            continue;
                        }
                        $totalRules++;
                        $field = $rule['field'];
                        $operator = $rule['operator'] ?? '>=';
                        $targetValue = $rule['value'];
                        $actualValue = 0;
                        if ($field === 'completed_jobs') {
                            $actualValue = $actualJobs;
                        } elseif ($field === 'trust_score') {
                            $actualValue = $actualScore;
                        } elseif ($field === 'rating') {
                            $actualValue = $actualRating;
                        } elseif ($field === 'earnings') {
                            $actualValue = $actualEarnings;
                        } elseif ($field === 'spend') {
                            $actualValue = $actualSpend;
                        } elseif ($field === 'verified') {
                            $actualValue = $isVerified;
                            $targetValue = filter_var($targetValue, FILTER_VALIDATE_BOOLEAN);
                        }

                        $passed = false;
                        switch ($operator) {
                            case '>=': $passed = $actualValue >= $targetValue; break;
                            case '>': $passed = $actualValue > $targetValue; break;
                            case '<=': $passed = $actualValue <= $targetValue; break;
                            case '<': $passed = $actualValue < $targetValue; break;
                            case '=':
                            case '==': $passed = $actualValue == $targetValue; break;
                            case '!=':
                            case '<>': $passed = $actualValue != $targetValue; break;
                        }

                        if ($passed) $passedRules++;

                        $progressDetails[] = [
                            'field' => $field,
                            'operator' => $operator,
                            'current' => $actualValue,
                            'target' => $targetValue,
                            'passed' => $passed
                        ];
                    }
                }

                $percentage = $totalRules > 0 ? min(100, round(($passedRules / $totalRules) * 100)) : 0;
                $progressText = '';
                if (count($progressDetails) > 0) {
                    $primary = $progressDetails[0];
                    $fieldName = str_replace('_', ' ', $primary['field']);
                    $progressText = "{$primary['current']}/{$primary['target']} {$fieldName}";
                }

                return [
                    'id' => $badge->id,
                    'name' => $badge->name,
                    'badge_key' => $badge->badge_key,
                    'description' => $badge->description,
                    'color' => $badge->color,
                    'icon' => $badge->icon,
                    'icon_type' => $badge->icon_type,
                    'icon_url' => $badge->icon_path ? asset('storage/' . $badge->icon_path) : null,
                    'priority' => $badge->priority,
                    'rarity' => $badge->rarity,
                    'badge_type' => $badge->badge_type,
                    'progress_percentage' => $percentage,
                    'progress_text' => $progressText,
                    'progress_details' => $progressDetails,
                ];
            })
            ->values();

        $profileViews = \App\Models\ProfileView::where('candidate_id', $user->id)->sum('view_count');

        // ── Fetch data from relational tables (primary source) with JSON fallback ──
        $relExperiences = \App\Models\CandidateExperience::where('user_id', $user->id)->orderBy('order')->get();
        $experiences = $relExperiences->isNotEmpty()
            ? $relExperiences->toArray()
            : ($profile->experience ?? []);

        $relEducations = \App\Models\CandidateEducation::where('user_id', $user->id)->orderBy('order')->get();
        $educations = $relEducations->isNotEmpty()
            ? $relEducations->toArray()
            : ($profile->education ?? []);

        $relTrainings = \App\Models\CandidateTraining::where('user_id', $user->id)->get();
        $trainings = $relTrainings->isNotEmpty()
            ? $relTrainings->toArray()
            : ($profile->trainings ?? []);

        $relCertifications = \App\Models\CandidateCertification::where('user_id', $user->id)->get();
        $certifications = $relCertifications->isNotEmpty()
            ? $relCertifications->toArray()
            : ($profile->certifications ?? []);

        // Social links: merge top-level fields with social_links JSON
        $socialLinksData = $profile->social_links ?? [];
        if (is_string($socialLinksData)) {
            $socialLinksData = json_decode($socialLinksData, true) ?? [];
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $profile->phone ?? null,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'current_position' => $profile->current_position ?? null,
                'city' => $profile->city ?? null,
                'bio' => $profile->bio ?? null,
                'availability_status' => $profile->availability_status ?? 'available',
                'skills' => $normalizedSkills,
                'experience' => $experiences,
                'education' => $educations,
                'trainings' => $trainings,
                'certifications' => $certifications,
                'language_proficiency' => $profile->language_proficiency ?? [],
                'projects' => $profile->projects ?? [],
                'social_links' => $socialLinksData,
                'linkedin_url' => $profile->linkedin_url ?? null,
                'github_url' => $profile->github_url ?? null,
                'portfolio_url' => $profile->portfolio_url ?? null,
                'facebook_url' => $profile->facebook_url ?? null,
                'resume' => ($profile && $profile->resume_path) ? (
                    (filter_var($profile->resume_path, FILTER_VALIDATE_URL) ||
                     str_starts_with($profile->resume_path, 'http') ||
                     str_starts_with($profile->resume_path, '/cv/') ||
                     str_starts_with($profile->resume_path, 'cv/'))
                        ? (str_starts_with($profile->resume_path, 'cv/') || str_starts_with($profile->resume_path, '/cv/')
                            ? url($profile->resume_path)
                            : $profile->resume_path)
                        : asset('storage/' . $profile->resume_path)
                ) : null,
                'active_badges' => $user->activeBadges(),
                'locked_badges' => $lockedBadges,
                'is_verified' => $user->hasBadge('verified') || ($profile && $profile->trust_score >= 80),
                'trust_score' => $profile->trust_score ?? 100,
                'trust_explanations' => $profile->trust_explanations ?? [],
                'profile_strength_breakdown' => $profile->profile_strength_breakdown ?? [],
                'rating' => $profile->rating ?? 0.00,
                'reputation_status' => $profile->reputation_status ?? 'good',
                'is_featured' => $profile->is_featured ?? false,
                'profile_completion_percentage' => $profile->profile_completion_percentage ?? 0,
                'profile_views_count' => (int) $profileViews,
                'follower_count' => $user->followers()->count(),
                'following_count' => $user->following()->count(),
            ]
        ]);
    }

    public function toggleFollow(Request $request, $username)
    {
        try {
            $currentUser = $request->user();
            $targetUser = User::where('username', $username)->first();

            if (!$targetUser) {
                return response()->json(['status' => false, 'message' => 'User not found'], 404);
            }

            if ($currentUser->id === $targetUser->id) {
                return response()->json(['status' => false, 'message' => 'Cannot follow yourself'], 422);
            }

            $follow = \App\Models\UserFollow::where('follower_id', $currentUser->id)
                ->where('following_id', $targetUser->id)
                ->first();

            if ($follow) {
                $follow->delete();
                return response()->json([
                    'status' => true,
                    'following' => false,
                    'message' => 'Unfollowed successfully',
                ]);
            }

            \App\Models\UserFollow::create([
                'follower_id' => $currentUser->id,
                'following_id' => $targetUser->id,
            ]);

            return response()->json([
                'status' => true,
                'following' => true,
                'message' => 'Followed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to toggle follow'], 500);
        }
    }

    public function getFollowStatus(Request $request, $username)
    {
        try {
            $currentUser = $request->user();
            $targetUser = User::where('username', $username)->first();

            if (!$targetUser) {
                return response()->json(['status' => false, 'message' => 'User not found'], 404);
            }

            $isFollowing = \App\Models\UserFollow::where('follower_id', $currentUser->id)
                ->where('following_id', $targetUser->id)
                ->exists();

            return response()->json([
                'status' => true,
                'following' => $isFollowing,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'following' => false], 500);
        }
    }
}
