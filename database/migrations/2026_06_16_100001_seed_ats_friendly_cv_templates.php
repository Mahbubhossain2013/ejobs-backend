<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $templates = [
            [
                'name' => 'Professional',
                'slug' => 'ats-professional',
                'preview_image_path' => 'templates/professional.png',
                'is_premium' => false,
                'price' => 0,
                'category' => 'professional',
                'is_active' => true,
                'is_ats_friendly' => true,
                'ats_compatible' => true,
                'is_featured' => true,
                'credits_required' => 0,
                'html_content' => file_exists(resource_path('views/cv_templates/ats-professional.blade.php'))
                    ? file_get_contents(resource_path('views/cv_templates/ats-professional.blade.php'))
                    : null,
            ],
            [
                'name' => 'Modern',
                'slug' => 'ats-modern',
                'preview_image_path' => 'templates/modern.png',
                'is_premium' => true,
                'price' => 250,
                'category' => 'professional',
                'is_active' => true,
                'is_ats_friendly' => true,
                'ats_compatible' => true,
                'is_featured' => false,
                'credits_required' => 5,
                'html_content' => file_exists(resource_path('views/cv_templates/ats-modern.blade.php'))
                    ? file_get_contents(resource_path('views/cv_templates/ats-modern.blade.php'))
                    : null,
            ],
            [
                'name' => 'Executive',
                'slug' => 'ats-executive',
                'preview_image_path' => 'templates/executive.png',
                'is_premium' => true,
                'price' => 350,
                'category' => 'professional',
                'is_active' => true,
                'is_ats_friendly' => true,
                'ats_compatible' => true,
                'is_featured' => false,
                'credits_required' => 5,
                'html_content' => file_exists(resource_path('views/cv_templates/ats-executive.blade.php'))
                    ? file_get_contents(resource_path('views/cv_templates/ats-executive.blade.php'))
                    : null,
            ],
            [
                'name' => 'Creative',
                'slug' => 'ats-creative',
                'preview_image_path' => 'templates/creative.png',
                'is_premium' => false,
                'price' => 0,
                'category' => 'creative',
                'is_active' => true,
                'is_ats_friendly' => true,
                'ats_compatible' => true,
                'is_featured' => false,
                'credits_required' => 0,
                'html_content' => file_exists(resource_path('views/cv_templates/ats-creative.blade.php'))
                    ? file_get_contents(resource_path('views/cv_templates/ats-creative.blade.php'))
                    : null,
            ],
            [
                'name' => 'Minimal',
                'slug' => 'ats-minimal',
                'preview_image_path' => 'templates/minimal.png',
                'is_premium' => false,
                'price' => 0,
                'category' => 'minimal',
                'is_active' => true,
                'is_ats_friendly' => true,
                'ats_compatible' => true,
                'is_featured' => false,
                'credits_required' => 0,
                'html_content' => file_exists(resource_path('views/cv_templates/ats-minimal.blade.php'))
                    ? file_get_contents(resource_path('views/cv_templates/ats-minimal.blade.php'))
                    : null,
            ],
        ];

        foreach ($templates as $t) {
            DB::table('cv_templates')->updateOrInsert(
                ['slug' => $t['slug']],
                array_merge($t, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        DB::table('cv_templates')->whereIn('slug', [
            'ats-professional',
            'ats-modern',
            'ats-executive',
            'ats-creative',
            'ats-minimal',
        ])->delete();
    }
};
