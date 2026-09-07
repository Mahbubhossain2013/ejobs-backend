<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CvBulkDownloadController extends Controller
{
    /**
     * Employer: Search and filter candidates for bulk download
     */
    public function search(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('employer')) {
            return response()->json(['status' => false, 'message' => 'Only employers can access CV database'], 403);
        }

        $query = User::with('profile')
            ->whereHas('roles', fn ($q) => $q->where('name', 'candidate'))
            ->whereHas('profile');

        if ($request->filled('skills')) {
            $skills = is_array($request->skills) ? $request->skills : explode(',', (string) $request->skills);
            foreach (array_filter(array_map('trim', $skills)) as $skill) {
                if ($skill !== '') {
                    $query->whereHas('profile', fn ($q) => $q->where('skills', 'like', "%{$skill}%"));
                }
            }
        }

        if ($request->filled('location')) {
            $query->whereHas('profile', fn ($q) => $q->where('location', 'like', "%{$request->location}%"));
        }

        if ($request->filled('experience_min') && Schema::hasColumn('user_profiles', 'years_of_experience')) {
            $query->whereHas('profile', fn ($q) => $q->where('years_of_experience', '>=', (int) $request->experience_min));
        }

        if ($request->filled('experience_max') && Schema::hasColumn('user_profiles', 'years_of_experience')) {
            $query->whereHas('profile', fn ($q) => $q->where('years_of_experience', '<=', (int) $request->experience_max));
        }

        if ($request->filled('education') && $request->education !== 'any' && Schema::hasColumn('user_profiles', 'highest_education')) {
            $query->whereHas('profile', fn ($q) => $q->where('highest_education', $request->education));
        }

        if ($request->filled('availability') && $request->availability !== 'any' && Schema::hasColumn('user_profiles', 'availability')) {
            $query->whereHas('profile', fn ($q) => $q->where('availability', $request->availability));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('profile', fn ($pq) => $pq->where('skills', 'like', "%{$search}%"));
            });
        }

        $sort = (string) $request->get('sort', 'newest');
        if ($sort === 'experience_desc' && Schema::hasColumn('user_profiles', 'years_of_experience')) {
            $query->orderByDesc(UserProfile::select('years_of_experience')->whereColumn('user_profiles.user_id', 'users.id'));
        } elseif ($sort === 'experience_asc' && Schema::hasColumn('user_profiles', 'years_of_experience')) {
            $query->orderBy(UserProfile::select('years_of_experience')->whereColumn('user_profiles.user_id', 'users.id'));
        } elseif ($sort === 'name_asc') {
            $query->orderBy('name');
        } elseif ($sort === 'name_desc') {
            $query->orderByDesc('name');
        } elseif ($sort === 'oldest') {
            $query->orderBy('created_at');
        } else {
            $query->orderByDesc('created_at');
        }

        $perPage = (int) ($request->get('per_page', 24));
        $perPage = in_array($perPage, [12, 24, 48, 96], true) ? $perPage : 24;
        $candidates = $query->paginate($perPage);

        return response()->json(['status' => true, 'data' => $candidates]);
    }

    /**
     * Employer: Export candidates as CSV
     */
    public function exportCsv(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('employer')) {
            return response()->json(['status' => false, 'message' => 'Only employers can export CV data'], 403);
        }

        $query = User::with('profile')
            ->whereHas('roles', fn ($q) => $q->where('name', 'candidate'))
            ->whereHas('profile');

        if ($request->filled('skills')) {
            $skills = is_array($request->skills) ? $request->skills : explode(',', (string) $request->skills);
            foreach (array_filter(array_map('trim', $skills)) as $skill) {
                if ($skill !== '') {
                    $query->whereHas('profile', fn ($q) => $q->where('skills', 'like', "%{$skill}%"));
                }
            }
        }
        if ($request->filled('location')) {
            $query->whereHas('profile', fn ($q) => $q->where('location', 'like', "%{$request->location}%"));
        }
        if ($request->filled('experience_min') && Schema::hasColumn('user_profiles', 'years_of_experience')) {
            $query->whereHas('profile', fn ($q) => $q->where('years_of_experience', '>=', (int) $request->experience_min));
        }
        if ($request->filled('experience_max') && Schema::hasColumn('user_profiles', 'years_of_experience')) {
            $query->whereHas('profile', fn ($q) => $q->where('years_of_experience', '<=', (int) $request->experience_max));
        }
        if ($request->filled('education') && $request->education !== 'any' && Schema::hasColumn('user_profiles', 'highest_education')) {
            $query->whereHas('profile', fn ($q) => $q->where('highest_education', $request->education));
        }
        if ($request->filled('availability') && $request->availability !== 'any' && Schema::hasColumn('user_profiles', 'availability')) {
            $query->whereHas('profile', fn ($q) => $q->where('availability', $request->availability));
        }

        $candidates = $query->limit(500)->get();

        $csvData = [];
        $csvData[] = ['Name', 'Email', 'Phone', 'Location', 'Skills', 'Experience (years)', 'Education', 'Current Position', 'Availability', 'Expected Salary', 'Summary'];

        foreach ($candidates as $candidate) {
            $profile = $candidate->profile;
            $skills = is_array($profile->skills) ? implode('; ', $profile->skills) : ($profile->skills ?? '');
            $csvData[] = [
                $candidate->name,
                $candidate->email,
                $profile->phone ?? '',
                $profile->location ?? '',
                $skills,
                Schema::hasColumn('user_profiles', 'years_of_experience') ? ($profile->years_of_experience ?? '') : '',
                Schema::hasColumn('user_profiles', 'highest_education') ? ($profile->highest_education ?? '') : '',
                $profile->current_position ?? '',
                Schema::hasColumn('user_profiles', 'availability') ? ($profile->availability ?? '') : '',
                $profile->expected_salary ?? '',
                strip_tags(substr($profile->summary ?? '', 0, 200)),
            ];
        }

        $filename = 'cv_database_' . now()->format('Y-m-d_H-i') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
