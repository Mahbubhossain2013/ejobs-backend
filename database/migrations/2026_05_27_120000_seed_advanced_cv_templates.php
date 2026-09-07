<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $templates = [
            [
                'name' => 'Minimalist Free',
                'slug' => 'minimalist-free',
                'preview_image_path' => 'templates/minimalist.png',
                'is_premium' => false,
                'price' => 0,
                'category' => 'Academic',
                'is_active' => true,
            ],
            [
                'name' => 'Minimalist Pro',
                'slug' => 'minimalist-pro',
                'preview_image_path' => 'templates/minimalist.png',
                'is_premium' => true,
                'price' => 150,
                'category' => 'Elegant',
                'is_active' => true,
            ],
            [
                'name' => 'Modern Premium',
                'slug' => 'modern-premium',
                'preview_image_path' => 'templates/modern.png',
                'is_premium' => true,
                'price' => 250,
                'category' => 'Corporate',
                'is_active' => true,
            ],
            [
                'name' => 'Creative Developer',
                'slug' => 'creative-developer',
                'preview_image_path' => 'templates/modern.png',
                'is_premium' => true,
                'price' => 350,
                'category' => 'Developer',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $t) {
            $filePath = resource_path("views/cv_templates/{$t['slug']}.blade.php");
            $htmlContent = file_exists($filePath) ? file_get_contents($filePath) : null;

            DB::table('cv_templates')->updateOrInsert(
                ['slug' => $t['slug']],
                array_merge($t, [
                    'html_content' => $htmlContent,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        DB::table('cv_templates')->whereIn('slug', ['minimalist-pro', 'creative-developer'])->delete();
    }
};
