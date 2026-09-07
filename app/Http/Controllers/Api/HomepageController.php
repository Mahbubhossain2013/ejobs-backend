<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Company;
use App\Models\Job;
use App\Models\Notice;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomepageController extends Controller
{
    public function index()
    {
        $data = Cache::remember('homepage_data_v1', 300, function () {
            $categories = Category::where('is_active', true)
                ->where('is_highlighted', true)
                ->orderBy('name_en')
                ->limit(12)
                ->get(['id', 'name_en', 'name_bn', 'icon', 'is_highlighted']);

            $companies = Company::where('verification_status', '!=', 'rejected')
                ->with(['user:id,name,avatar'])
                ->orderByDesc('is_featured')
                ->orderByDesc('created_at')
                ->limit(30)
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'logo' => $c->logo,
                    'industry' => $c->industry,
                    'location' => $c->location,
                    'jobs_count' => $c->jobs_count ?? 0,
                    'rating' => $c->rating ?? 0,
                    'reviews_count' => $c->reviews()->where('status', 'approved')->count(),
                    'is_featured' => $c->is_featured,
                ]);

            $notices = Notice::orderByDesc('published_at')
                ->limit(5)
                ->get(['id', 'title', 'title_bn', 'category', 'category_bn', 'published_at']);

            $activePromoJobIds = Promotion::where('status', 'active')
                ->where('type', 'sponsored_job')
                ->where('start_date', '<=', now())
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                })
                ->whereNotNull('job_id')
                ->pluck('job_id')
                ->unique();

            $mapJob = function ($j) {
                return [
                    'id' => $j->id,
                    'title' => $j->title,
                    'slug' => $j->slug,
                    'location' => $j->location,
                    'job_type' => $j->job_type,
                    'salary_min' => $j->salary_min,
                    'salary_max' => $j->salary_max,
                    'budget' => $j->budget,
                    'budget_type' => $j->budget_type,
                    'created_at' => $j->created_at,
                    'is_promoted' => $j->is_promoted ? 1 : 0,
                    'company' => $j->company ? [
                        'id' => $j->company->id,
                        'name' => $j->company->name,
                        'logo' => $j->company->logo,
                    ] : null,
                ];
            };

            $featuredJobs = Job::where('is_active', true)
                ->with(['company:id,name,logo'])
                ->latest()
                ->limit(30)
                ->get()
                ->sortByDesc(fn($j) => $activePromoJobIds->contains($j->id))
                ->map(function ($j) use ($activePromoJobIds, $mapJob) {
                    $j->is_promoted = $activePromoJobIds->contains($j->id);
                    return $mapJob($j);
                })
                ->values();

            $remoteJobs = Job::where('is_active', true)
                ->with(['company:id,name,logo'])
                ->where('job_type', 'like', 'remote')
                ->latest()
                ->limit(30)
                ->get()
                ->sortByDesc(fn($j) => $activePromoJobIds->contains($j->id))
                ->map(function ($j) use ($activePromoJobIds, $mapJob) {
                    $j->is_promoted = $activePromoJobIds->contains($j->id);
                    return $mapJob($j);
                })
                ->values();

            $settingsRaw = Cache::remember('homepage_settings_v1', 3600, function () {
                $keys = [
                    'homepage_trending_searches', 'homepage_trending_searches_bn',
                    'homepage_hero_section', 'homepage_categories',
                    'homepage_hot_jobs', 'homepage_remote_jobs',
                    'homepage_features_grid', 'homepage_ai_assistant',
                    'homepage_offer_banner', 'homepage_quick_links',
                    'homepage_recruitment_callout', 'homepage_newsletter',
                    'site_name', 'site_logo', 'site_description',
                ];
                $rows = \App\Models\Setting::whereIn('key', $keys)->get(['key', 'value']);
                $out = [];
                foreach ($rows as $row) {
                    $decoded = json_decode($row->value, true);
                    $out[$row->key] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $row->value;
                }
                return $out;
            });

            return [
                'categories' => $categories,
                'companies' => $companies,
                'notices' => $notices,
                'featured_jobs' => $featuredJobs,
                'remote_jobs' => $remoteJobs,
                'settings' => $settingsRaw,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }
}
