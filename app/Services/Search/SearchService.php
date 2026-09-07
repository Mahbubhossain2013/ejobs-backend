<?php

namespace App\Services\Search;

use App\Models\Job;
use App\Models\Company;
use App\Models\UserProfile;
use App\Models\Category;
use App\Models\Resume;
use App\Models\SearchAnalytic;
use App\Models\SavedSearch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SearchService
{
    /**
     * Perform typo-tolerant search across multiple targets
     */
    public function search(string $target, ?string $query, array $filters = [], int $limit = 20, ?int $userId = null)
    {
        $query = trim($query ?? '');
        $results = [];

        // 1. Core Typo-Tolerance Dictionary Alignment
        $alignedQuery = $this->alignQueryWithTypoTolerance($query);

        // 2. Perform target-specific queries
        switch ($target) {
            case 'jobs':
                $results = $this->searchJobs($alignedQuery, $filters, false, $limit);
                break;
            case 'remote-jobs':
                $results = $this->searchJobs($alignedQuery, $filters, true, $limit);
                break;
            case 'employers':
                $results = $this->searchEmployers($alignedQuery, $filters, $limit);
                break;
            case 'candidates':
                $results = $this->searchCandidates($alignedQuery, $filters, $limit);
                break;
            case 'skills':
                $results = $this->searchBySkills($alignedQuery, $filters, $limit);
                break;
            case 'categories':
                $results = $this->searchCategories($alignedQuery, $limit);
                break;
            case 'resumes':
                $results = $this->searchResumes($alignedQuery, $filters, $limit);
                break;
            case 'portfolios':
                $results = $this->searchPortfolios($alignedQuery, $filters, $limit);
                break;
            case 'all':
            default:
                $results = [
                    'jobs' => $this->searchJobs($alignedQuery, $filters, false, 5),
                    'remote_jobs' => $this->searchJobs($alignedQuery, $filters, true, 5),
                    'employers' => $this->searchEmployers($alignedQuery, $filters, 5),
                    'candidates' => $this->searchCandidates($alignedQuery, $filters, 5),
                ];
                break;
        }

        // 3. Track Search Analytics (only if query is not empty)
        if (!empty($query)) {
            $resultCount = is_array($results) ? (isset($results['jobs']) ? count($results['jobs']) + count($results['remote_jobs']) : count($results)) : 0;
            $this->trackAnalytic($query, $filters, $resultCount, $userId);
        }

        return [
            'original_query' => $query,
            'processed_query' => $alignedQuery,
            'target' => $target,
            'filters' => $filters,
            'results' => $results,
        ];
    }

    /**
     * Typo-tolerant dictionary alignment using Levenshtein distance
     */
    protected function alignQueryWithTypoTolerance(string $query): string
    {
        if (empty($query)) {
            return '';
        }

        $words = explode(' ', $query);
        $dictionary = $this->getSearchDictionary();
        $correctedWords = [];

        foreach ($words as $word) {
            $cleanedWord = strtolower(trim($word));
            if (strlen($cleanedWord) < 3) {
                $correctedWords[] = $word;
                continue;
            }

            $bestMatch = null;
            $shortestDistance = 3; // Max threshold distance is 2

            foreach ($dictionary as $dictWord) {
                $dictWordLower = strtolower($dictWord);
                if ($cleanedWord === $dictWordLower) {
                    $bestMatch = $dictWord;
                    break;
                }

                $dist = levenshtein($cleanedWord, $dictWordLower);
                if ($dist < $shortestDistance) {
                    $shortestDistance = $dist;
                    $bestMatch = $dictWord;
                }
            }

            $correctedWords[] = $bestMatch ?? $word;
        }

        return implode(' ', $correctedWords);
    }

    /**
     * Cache search dictionary consisting of common job titles, skills, company names, categories
     */
    protected function getSearchDictionary(): array
    {
        return Cache::remember('search_dictionary', 3600, function () {
            $skills = DB::table('jobs')->whereNotNull('required_skills')->pluck('required_skills')->flatMap(function ($item) {
                return is_string($item) ? json_decode($item, true) : $item;
            })->filter()->unique()->toArray();

            $titles = DB::table('jobs')->pluck('title')->unique()->toArray();
            $companies = DB::table('companies')->pluck('name')->unique()->toArray();
            $categories = DB::table('categories')->pluck('name')->unique()->toArray();

            return array_values(array_unique(array_merge($skills, $titles, $companies, $categories)));
        });
    }

    /**
     * Search Normal or Remote Jobs
     */
    protected function searchJobs(string $query, array $filters, bool $isRemote, int $limit)
    {
        $q = Job::with('company', 'category')
            ->where('is_active', true)
            ->where('is_remote_project', $isRemote);

        // Keyword Match
        if (!empty($query)) {
            $q->where(function ($sub) use ($query) {
                $sub->where('title', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%")
                    ->orWhere('required_skills', 'LIKE', "%{$query}%");
            });
        }

        // Apply Filters
        if (isset($filters['category_id'])) {
            $q->where('category_id', $filters['category_id']);
        }

        if (isset($filters['skills']) && !empty($filters['skills'])) {
            $skills = is_array($filters['skills']) ? $filters['skills'] : [$filters['skills']];
            $q->where(function ($sub) use ($skills) {
                foreach ($skills as $skill) {
                    $sub->orWhere('required_skills', 'LIKE', "%{$skill}%");
                }
            });
        }

        if (isset($filters['experience_level'])) {
            $q->where('experience_level', $filters['experience_level']);
        }

        if (isset($filters['job_type'])) {
            $q->where('job_type', $filters['job_type']);
        }

        if (isset($filters['salary_min'])) {
            $q->where(function ($sub) use ($filters) {
                $sub->where('budget', '>=', $filters['salary_min'])
                    ->orWhere('salary_range', 'LIKE', "%{$filters['salary_min']}%");
            });
        }

        if (isset($filters['salary_max'])) {
            $q->where(function ($sub) use ($filters) {
                $sub->where('budget', '<=', $filters['salary_max'])
                    ->orWhere('salary_range', 'LIKE', "%{$filters['salary_max']}%");
            });
        }

        if (isset($filters['location'])) {
            $q->where('location', 'LIKE', "%{$filters['location']}%");
        }

        if (isset($filters['company_id'])) {
            $q->where('company_id', $filters['company_id']);
        }

        // Filter out shadow restricted/banned employers
        $q->whereHas('company', function ($sub) {
            $sub->where('restriction_status', '!=', 'shadow_restricted')
                ->where('ban_status', false);
        });

        return $q->limit($limit)->get();
    }

    /**
     * Search Employers/Companies
     */
    protected function searchEmployers(string $query, array $filters, int $limit)
    {
        $q = Company::where('ban_status', false)
            ->where('restriction_status', '!=', 'shadow_restricted');

        if (!empty($query)) {
            $q->where(function ($sub) use ($query) {
                $sub->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%")
                    ->orWhere('industry', 'LIKE', "%{$query}%");
            });
        }

        if (isset($filters['location'])) {
            $q->where('location', 'LIKE', "%{$filters['location']}%");
        }

        if (isset($filters['industry'])) {
            $q->where('industry', $filters['industry']);
        }

        if (isset($filters['is_verified'])) {
            $q->where('is_verified', (bool)$filters['is_verified']);
        }

        return $q->limit($limit)->get();
    }

    /**
     * Search Candidates
     */
    protected function searchCandidates(string $query, array $filters, int $limit)
    {
        $q = UserProfile::with('user')
            ->where('ban_status', false)
            ->where('restriction_status', '!=', 'shadow_restricted')
            ->where('is_public', true);

        if (!empty($query)) {
            $q->where(function ($sub) use ($query) {
                $sub->where('current_position', 'LIKE', "%{$query}%")
                    ->orWhere('current_company', 'LIKE', "%{$query}%")
                    ->orWhere('bio', 'LIKE', "%{$query}%")
                    ->orWhere('skills', 'LIKE', "%{$query}%");
            });
        }

        if (isset($filters['city'])) {
            $q->where('city', 'LIKE', "%{$filters['city']}%");
        }

        if (isset($filters['skills']) && !empty($filters['skills'])) {
            $skills = is_array($filters['skills']) ? $filters['skills'] : [$filters['skills']];
            $q->where(function ($sub) use ($skills) {
                foreach ($skills as $skill) {
                    $sub->orWhere('skills', 'LIKE', "%{$skill}%");
                }
            });
        }

        // Rank by profile strength/completion first, then trust score
        $q->orderByDesc('profile_completion_percentage')
          ->orderByDesc('trust_score');

        return $q->limit($limit)->get();
    }

    /**
     * Search Skills
     */
    protected function searchBySkills(string $query, array $filters, int $limit)
    {
        return [
            'jobs' => $this->searchJobs($query, array_merge($filters, ['skills' => $query]), false, $limit),
            'remote_jobs' => $this->searchJobs($query, array_merge($filters, ['skills' => $query]), true, $limit),
            'candidates' => $this->searchCandidates($query, array_merge($filters, ['skills' => $query]), $limit),
        ];
    }

    /**
     * Search Categories
     */
    protected function searchCategories(string $query, int $limit)
    {
        $q = Category::query();
        if (!empty($query)) {
            $q->where('name', 'LIKE', "%{$query}%")
              ->orWhere('description', 'LIKE', "%{$query}%");
        }
        return $q->limit($limit)->get();
    }

    /**
     * Search Resumes
     */
    protected function searchResumes(string $query, array $filters, int $limit)
    {
        $q = Resume::query();

        if (!empty($query)) {
            $q->where(function ($sub) use ($query) {
                $sub->where('title', 'LIKE', "%{$query}%")
                    ->orWhere('data_snapshot', 'LIKE', "%{$query}%");
            });
        }

        // Exclude shadow restricted candidates
        $q->whereHas('user.profile', function ($sub) {
            $sub->where('restriction_status', '!=', 'shadow_restricted')
                ->where('ban_status', false);
        });

        return $q->with('user.profile')->limit($limit)->get();
    }

    /**
     * Search Portfolios/Projects
     */
    protected function searchPortfolios(string $query, array $filters, int $limit)
    {
        $q = UserProfile::with('user')
            ->where('ban_status', false)
            ->where('restriction_status', '!=', 'shadow_restricted')
            ->where('is_public', true)
            ->whereNotNull('projects');

        if (!empty($query)) {
            $q->where('projects', 'LIKE', "%{$query}%");
        }

        return $q->limit($limit)->get();
    }

    /**
     * Track search analytics
     */
    protected function trackAnalytic(string $query, array $filters, int $resultCount, ?int $userId)
    {
        try {
            SearchAnalytic::create([
                'user_id' => $userId,
                'query_string' => $query,
                'filters' => $filters,
                'result_count' => $resultCount,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Silence analytics writing errors to avoid interrupting user searches
        }
    }

    /**
     * Get Autocomplete Suggestions based on trending queries
     */
    public function getSuggestions(string $query, int $limit = 8): array
    {
        $query = trim($query);
        if (empty($query)) {
            return $this->getTrendingSearches($limit);
        }

        return SearchAnalytic::select('query_string')
            ->where('query_string', 'LIKE', "{$query}%")
            ->groupBy('query_string')
            ->orderByRaw('COUNT(*) DESC')
            ->limit($limit)
            ->pluck('query_string')
            ->toArray();
    }

    /**
     * Get Top Trending Searches
     */
    public function getTrendingSearches(int $limit = 8): array
    {
        return Cache::remember('trending_searches', 600, function () use ($limit) {
            return SearchAnalytic::select('query_string')
                ->groupBy('query_string')
                ->orderByRaw('COUNT(*) DESC')
                ->limit($limit)
                ->pluck('query_string')
                ->toArray();
        });
    }

    /**
     * Save search criteria for user
     */
    public function saveSearch(int $userId, string $name, string $query, array $filters = [])
    {
        return SavedSearch::create([
            'user_id' => $userId,
            'name' => $name,
            'query_string' => $query,
            'filters' => $filters,
        ]);
    }

    /**
     * Candidate recommendation scoring preparation structure
     */
    public function getCandidateRecommendationScore(UserProfile $profile, Job $job): array
    {
        $skillsScore = 0;
        $profileSkills = $profile->skills ?? [];
        $jobSkills = $job->required_skills ?? [];

        if (!empty($jobSkills) && !empty($profileSkills)) {
            $intersection = array_intersect(
                array_map('strtolower', $profileSkills),
                array_map('strtolower', $jobSkills)
            );
            $skillsScore = (count($intersection) / count($jobSkills)) * 100;
        }

        $experienceMatch = false;
        if (!empty($job->experience_level)) {
            $experienceMatch = strtolower($profile->current_position ?? '') === strtolower($job->experience_level);
        }

        $locationMatch = false;
        if ($job->is_remote_project) {
            $locationMatch = true; // Remote jobs are match-friendly for candidate locations
        } elseif (!empty($job->location) && !empty($profile->city)) {
            $locationMatch = stripos($job->location, $profile->city) !== false;
        }

        $totalScore = ($skillsScore * 0.5) + ($experienceMatch ? 15 : 0) + ($locationMatch ? 15 : 0);

        // Profile Strength (completion) bonus: up to 20% impact
        $strengthBonus = ($profile->profile_completion_percentage ?? 0) * 0.20;
        $totalScore += $strengthBonus;
        $totalScore = min(100, max(0, $totalScore));

        return [
            'skills_match_percentage' => round($skillsScore, 2),
            'experience_match' => $experienceMatch,
            'location_match' => $locationMatch,
            'total_score' => round($totalScore, 2),
        ];
    }
}
