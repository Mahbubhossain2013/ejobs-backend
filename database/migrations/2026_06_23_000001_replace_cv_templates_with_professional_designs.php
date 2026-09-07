<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $oldSlugs = [
        'minimalist-free',
        'minimalist-pro',
        'modern-premium',
        'creative-developer',
        'ats-professional',
        'ats-modern',
        'ats-executive',
        'ats-creative',
        'ats-minimal',
    ];

    private array $newTemplates = [
        [
            'name' => 'Sidebar Pro',
            'slug' => 'sidebar-pro',
            'category' => 'professional',
            'is_premium' => false,
            'price' => 0,
            'is_active' => true,
            'is_featured' => true,
            'is_ats_friendly' => true,
            'ats_compatible' => true,
            'dark_mode_supported' => false,
            'version' => '2.0',
            'author' => 'system',
        ],
        [
            'name' => 'Academic',
            'slug' => 'academic',
            'category' => 'academic',
            'is_premium' => false,
            'price' => 0,
            'is_active' => true,
            'is_featured' => false,
            'is_ats_friendly' => true,
            'ats_compatible' => true,
            'dark_mode_supported' => false,
            'version' => '2.0',
            'author' => 'system',
        ],
        [
            'name' => 'Executive',
            'slug' => 'executive',
            'category' => 'executive',
            'is_premium' => true,
            'price' => 150,
            'is_active' => true,
            'is_featured' => true,
            'is_ats_friendly' => true,
            'ats_compatible' => true,
            'dark_mode_supported' => false,
            'version' => '2.0',
            'author' => 'system',
        ],
        [
            'name' => 'Corporate Clean',
            'slug' => 'corporate-clean',
            'category' => 'corporate',
            'is_premium' => false,
            'price' => 0,
            'is_active' => true,
            'is_featured' => false,
            'is_ats_friendly' => true,
            'ats_compatible' => true,
            'dark_mode_supported' => false,
            'version' => '2.0',
            'author' => 'system',
        ],
        [
            'name' => 'Modern Two-Column',
            'slug' => 'modern-twocol',
            'category' => 'modern',
            'is_premium' => true,
            'price' => 200,
            'is_active' => true,
            'is_featured' => false,
            'is_ats_friendly' => true,
            'ats_compatible' => true,
            'dark_mode_supported' => false,
            'version' => '2.0',
            'author' => 'system',
        ],
        [
            'name' => 'Creative Pro',
            'slug' => 'creative-pro',
            'category' => 'creative',
            'is_premium' => true,
            'price' => 250,
            'is_active' => true,
            'is_featured' => false,
            'is_ats_friendly' => false,
            'ats_compatible' => false,
            'dark_mode_supported' => false,
            'version' => '2.0',
            'author' => 'system',
        ],
        [
            'name' => 'Minimal Elegant',
            'slug' => 'minimal-elegant',
            'category' => 'minimal',
            'is_premium' => false,
            'price' => 0,
            'is_active' => true,
            'is_featured' => true,
            'is_ats_friendly' => true,
            'ats_compatible' => true,
            'dark_mode_supported' => false,
            'version' => '2.0',
            'author' => 'system',
        ],
        [
            'name' => 'Bold Professional',
            'slug' => 'bold-professional',
            'category' => 'professional',
            'is_premium' => true,
            'price' => 150,
            'is_active' => true,
            'is_featured' => false,
            'is_ats_friendly' => true,
            'ats_compatible' => true,
            'dark_mode_supported' => false,
            'version' => '2.0',
            'author' => 'system',
        ],
    ];

    public function up(): void
    {
        DB::table('cv_templates')->whereIn('slug', $this->oldSlugs)->delete();

        foreach ($this->newTemplates as $t) {
            $filePath = resource_path("views/cv_templates/{$t['slug']}.blade.php");
            $htmlContent = file_exists($filePath) ? file_get_contents($filePath) : null;

            DB::table('cv_templates')->updateOrInsert(
                ['slug' => $t['slug']],
                array_merge($t, [
                    'html_content' => $htmlContent,
                    'css_content' => null,
                    'preview_image_path' => "templates/{$t['slug']}.png",
                    'credits_required' => 0,
                    'monetization_model' => $t['is_premium'] ? 'subscription' : 'one_time',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        DB::table('cv_templates')->whereIn('slug', array_column($this->newTemplates, 'slug'))->delete();

        foreach ($this->oldSlugs as $slug) {
            DB::table('cv_templates')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('-', ' ', $slug)),
                    'slug' => $slug,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
};
