<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Seo\SeoService;
use App\Models\Job;
use App\Models\Company;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    protected SeoService $seoService;

    public function __construct(SeoService $seoService)
    {
        $this->seoService = $seoService;
    }

    /**
     * Get compiled metadata for a specific page or entity
     */
    public function getMeta(Request $request)
    {
        $request->validate([
            'page_key' => 'required|string',
            'lang' => 'nullable|string|in:en,bn',
            'entity_type' => 'nullable|string|in:job,company,profile',
            'entity_id' => 'nullable|integer',
        ]);

        $pageKey = $request->input('page_key');
        $lang = $request->input('lang', 'en');
        $entityType = $request->input('entity_type');
        $entityId = $request->input('entity_id');
        $entity = null;

        if ($entityType && $entityId) {
            if ($entityType === 'job') {
                $entity = Job::with('company')->find($entityId);
            } elseif ($entityType === 'company') {
                $entity = Company::find($entityId);
            } elseif ($entityType === 'profile') {
                $entity = \App\Models\UserProfile::with('user')->find($entityId);
            }
        }

        $meta = $this->seoService->compileMeta($pageKey, $entity, $lang);

        return response()->json([
            'status' => true,
            'data' => $meta
        ]);
    }

    /**
     * Generate dynamic sitemap.xml
     */
    public function sitemap()
    {
        $jobs = Job::where('is_active', true)->latest()->get();
        $companies = Company::where('ban_status', false)->where('restriction_status', '!=', 'shadow_restricted')->get();

        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Home
        $xml[] = '  <url>';
        $xml[] = '    <loc>' . url('/') . '</loc>';
        $xml[] = '    <changefreq>daily</changefreq>';
        $xml[] = '    <priority>1.0</priority>';
        $xml[] = '  </url>';

        // Normal Jobs page
        $xml[] = '  <url>';
        $xml[] = '    <loc>' . url('/jobs') . '</loc>';
        $xml[] = '    <changefreq>hourly</changefreq>';
        $xml[] = '    <priority>0.9</priority>';
        $xml[] = '  </url>';

        // Remote Jobs page
        $xml[] = '  <url>';
        $xml[] = '    <loc>' . url('/jobs/remote') . '</loc>';
        $xml[] = '    <changefreq>hourly</changefreq>';
        $xml[] = '    <priority>0.9</priority>';
        $xml[] = '  </url>';

        // Static pages
        $staticPages = [
            '/about' => '0.6',
            '/contact' => '0.6',
            '/help' => '0.5',
            '/pricing' => '0.7',
            '/privacy' => '0.4',
            '/terms' => '0.4',
        ];
        foreach ($staticPages as $path => $priority) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>' . url($path) . '</loc>';
            $xml[] = '    <changefreq>monthly</changefreq>';
            $xml[] = '    <priority>' . $priority . '</priority>';
            $xml[] = '  </url>';
        }

        // Job list loops
        foreach ($jobs as $job) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>' . url('/jobs/' . $job->id) . '</loc>';
            $xml[] = '    <lastmod>' . ($job->updated_at ? $job->updated_at->toAtomString() : ($job->created_at ? $job->created_at->toAtomString() : now()->toAtomString())) . '</lastmod>';
            $xml[] = '    <changefreq>weekly</changefreq>';
            $xml[] = '    <priority>0.8</priority>';
            $xml[] = '  </url>';
        }

        // Employer list loops
        foreach ($companies as $company) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>' . url('/companies/' . $company->slug) . '</loc>';
            $xml[] = '    <lastmod>' . ($company->updated_at ? $company->updated_at->toAtomString() : ($company->created_at ? $company->created_at->toAtomString() : now()->toAtomString())) . '</lastmod>';
            $xml[] = '    <changefreq>monthly</changefreq>';
            $xml[] = '    <priority>0.7</priority>';
            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';

        return response(implode("\n", $xml), 200)
            ->header('Content-Type', 'text/xml');
    }

    /**
     * Generate dynamic robots.txt
     */
    public function robots()
    {
        $robots = [];
        $robots[] = 'User-agent: *';
        $robots[] = 'Allow: /';
        $robots[] = 'Disallow: /api/';
        $robots[] = 'Disallow: /admin/';
        $robots[] = 'Disallow: /dashboard/';
        $robots[] = '';
        $robots[] = 'Sitemap: ' . url('/sitemap.xml');

        return response(implode("\n", $robots), 200)
            ->header('Content-Type', 'text/plain');
    }
}
