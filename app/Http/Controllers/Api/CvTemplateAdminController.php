<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CvTemplate;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CvTemplateAdminController extends Controller
{
    /**
     * Monaco Code Editor Template Save
     */
    public function saveEditorTemplate(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string',
                'slug' => 'required|string|alpha_dash',
                'html_content' => 'required|string',
                'css_content' => 'nullable|string',
                'meta_schema' => 'nullable|array',
                'category' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'is_premium' => 'nullable|boolean',
                'price' => 'nullable|numeric'
            ]);

            // Security scan code editor contents before saving!
            $renderer = app(\App\Services\Cv\CvRenderingService::class);
            $renderer->validateSecurity($request->html_content);

            $template = CvTemplate::updateOrCreate(
                ['slug' => $request->slug],
                [
                    'name' => $request->name,
                    'html_content' => $request->html_content,
                    'css_content' => $request->css_content,
                    'meta_schema' => $request->meta_schema ?? [],
                    'category' => $request->category ?? 'Corporate',
                    'is_active' => $request->is_active ?? true,
                    'is_premium' => $request->is_premium ?? false,
                    'price' => $request->price ?? 0,
                    'preview_image_path' => $request->preview_image_path ?? 'templates/minimalist.png'
                ]
            );

            return response()->json(['status' => true, 'message' => 'Template saved successfully!', 'data' => $template]);
        } catch (\Exception $e) {
            Log::error("Template Monaco Save Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to save template: ' . $e->getMessage()], 422);
        }
    }

    /**
     * ZIP Template Upload Parser & Security Scanner
     */
    public function uploadZipTemplate(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:zip|max:10240', // 10MB limit
            ]);

            $file = $request->file('file');
            $zip = new \ZipArchive();

            if ($zip->open($file->getRealPath()) !== true) {
                return response()->json(['status' => false, 'message' => 'Failed to open ZIP package.'], 422);
            }

            // Create safe temp extraction directory
            $tempDirName = 'cv_temp_' . Str::random(10);
            $tempPath = storage_path('app/temp/' . $tempDirName);
            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0755, true);
            }

            // Extract files safely - validate each entry
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_contains($name, '..') || str_starts_with($name, '/')) {
                    $zip->close();
                    $this->cleanupDir($tempPath);
                    return response()->json(['status' => false, 'message' => 'Invalid file path in ZIP.'], 422);
                }
            }
            $zip->extractTo($tempPath);
            $zip->close();

            // 1. Recursive security scan for any PHP files
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tempPath));
            foreach ($files as $fileInfo) {
                if ($fileInfo->isFile()) {
                    $ext = strtolower($fileInfo->getExtension());
                    if ($ext === 'php') {
                        $this->cleanupDir($tempPath);
                        return response()->json(['status' => false, 'message' => 'Security Threat: PHP files are not allowed inside template ZIP packages.'], 422);
                    }

                    // Check content of html/css files for PHP tags
                    if (in_array($ext, ['html', 'blade', 'css', 'json'])) {
                        $content = file_get_contents($fileInfo->getRealPath());
                        if (str_contains($content, '<?php') || str_contains($content, '<?') || str_contains($content, '<script')) {
                            $this->cleanupDir($tempPath);
                            return response()->json(['status' => false, 'message' => 'Security Threat: File "' . $fileInfo->getFilename() . '" contains malicious scripting blocks (PHP tags or HTML inline scripts).'], 422);
                        }
                    }
                }
            }

            // 2. Validate manifest existence
            $manifestPath = $tempPath . '/template.json';
            if (!file_exists($manifestPath)) {
                $this->cleanupDir($tempPath);
                return response()->json(['status' => false, 'message' => 'Invalid ZIP structure: missing "template.json" manifest at root.'], 422);
            }

            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (empty($manifest['name']) || empty($manifest['slug'])) {
                $this->cleanupDir($tempPath);
                return response()->json(['status' => false, 'message' => 'Invalid manifest format: missing template "name" or "slug".'], 422);
            }

            // 3. Extract core files
            $htmlPath = $tempPath . '/index.blade.php';
            if (!file_exists($htmlPath)) {
                $htmlPath = $tempPath . '/index.html';
            }

            if (!file_exists($htmlPath)) {
                $this->cleanupDir($tempPath);
                return response()->json(['status' => false, 'message' => 'Invalid ZIP structure: missing "index.blade.php" rendering file.'], 422);
            }

            $html = file_get_contents($htmlPath);
            $cssPath = $tempPath . '/styles.css';
            $css = file_exists($cssPath) ? file_get_contents($cssPath) : '';

            // Handle preview image extraction
            $previewPath = 'templates/minimalist.png'; // default fallback
            $previewFile = $tempPath . '/preview.png';
            if (file_exists($previewFile)) {
                $previewPath = 'templates/' . $manifest['slug'] . '.png';
                copy($previewFile, public_path('storage/' . $previewPath));
            }

            // Register/Update Template in DB
            $template = CvTemplate::updateOrCreate(
                ['slug' => $manifest['slug']],
                [
                    'name' => $manifest['name'],
                    'html_content' => $html,
                    'css_content' => $css,
                    'meta_schema' => $manifest['meta_schema'] ?? [],
                    'category' => $manifest['category'] ?? 'Corporate',
                    'is_premium' => $manifest['is_premium'] ?? false,
                    'price' => $manifest['price'] ?? 0,
                    'monetization_model' => $manifest['monetization_model'] ?? 'one_time',
                    'is_active' => $manifest['is_active'] ?? true,
                    'is_featured' => $manifest['is_featured'] ?? false,
                    'ats_compatible' => $manifest['ats_compatible'] ?? true,
                    'dark_mode_supported' => $manifest['dark_mode_supported'] ?? false,
                    'version' => $manifest['version'] ?? '1.0.0',
                    'author' => $manifest['author'] ?? 'Admin',
                    'preview_image_path' => $previewPath
                ]
            );

            // Cleanup
            $this->cleanupDir($tempPath);

            return response()->json([
                'status' => true, 
                'message' => 'Dynamic ZIP template successfully extracted, scanned, and registered!', 
                'data' => $template
            ]);
        } catch (\Exception $e) {
            Log::error("ZIP Upload Parse Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to parse ZIP template: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get Templates usage analytics
     */
    public function getUsageAnalytics()
    {
        try {
            $totalResumes = Resume::count();
            
            // Usage counts by template slug
            $usage = Resume::select('template_slug', DB::raw('count(*) as count'))
                ->groupBy('template_slug')
                ->orderBy('count', 'desc')
                ->get()
                ->map(function ($item) {
                    $template = CvTemplate::where('slug', $item->template_slug)->first();
                    return [
                        'slug' => $item->template_slug,
                        'name' => $template ? $template->name : $item->template_slug,
                        'count' => $item->count,
                        'category' => $template ? $template->category : 'Classic'
                    ];
                });

            // Calculate overall visual performance
            $totalViews = Resume::sum('views_count');
            $featuredCount = CvTemplate::where('is_featured', true)->count();
            $premiumCount = CvTemplate::where('is_premium', true)->count();

            return response()->json([
                'status' => true,
                'data' => [
                    'total_resumes' => $totalResumes,
                    'total_views' => $totalViews,
                    'featured_templates' => $featuredCount,
                    'premium_templates' => $premiumCount,
                    'template_usage' => $usage
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function cleanupDir($dirPath): void
    {
        if (!file_exists($dirPath)) return;
        
        $files = array_diff(scandir($dirPath), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dirPath/$file")) ? $this->cleanupDir("$dirPath/$file") : unlink("$dirPath/$file");
        }
        rmdir($dirPath);
    }
}
