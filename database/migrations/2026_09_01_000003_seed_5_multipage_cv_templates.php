<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $templates = [
            [
                'name' => 'Charcoal Ribbon Executive',
                'slug' => 'dark-charcoal-ribbon',
                'preview_image_path' => 'templates/dark-charcoal-ribbon.png',
                'is_premium' => false,
                'price' => 0,
                'category' => 'creative',
                'is_active' => true,
                'is_ats_friendly' => true,
                'ats_compatible' => true,
                'is_featured' => true,
                'credits_required' => 0,
                'html_content' => null,
            ],
            [
                'name' => 'Corporate Monochrome Pro',
                'slug' => 'corporate-monochrome-pro',
                'preview_image_path' => 'templates/corporate-monochrome-pro.png',
                'is_premium' => false,
                'price' => 0,
                'category' => 'professional',
                'is_active' => true,
                'is_ats_friendly' => true,
                'ats_compatible' => true,
                'is_featured' => true,
                'credits_required' => 0,
                'html_content' => null,
            ],
            [
                'name' => 'Arch Ribbon Modern',
                'slug' => 'arch-ribbon-grey',
                'preview_image_path' => 'templates/arch-ribbon-grey.png',
                'is_premium' => false,
                'price' => 0,
                'category' => 'modern',
                'is_active' => true,
                'is_ats_friendly' => true,
                'ats_compatible' => true,
                'is_featured' => true,
                'credits_required' => 0,
                'html_content' => null,
            ],
            [
                'name' => 'Cyan Ocean Wave',
                'slug' => 'cyan-ocean-wave',
                'preview_image_path' => 'templates/cyan-ocean-wave.png',
                'is_premium' => false,
                'price' => 0,
                'category' => 'creative',
                'is_active' => true,
                'is_ats_friendly' => true,
                'ats_compatible' => true,
                'is_featured' => true,
                'credits_required' => 0,
                'html_content' => null,
            ],
            [
                'name' => 'Red Slate Executive Dual',
                'slug' => 'red-slate-executive',
                'preview_image_path' => 'templates/red-slate-executive.png',
                'is_premium' => false,
                'price' => 0,
                'category' => 'executive',
                'is_active' => true,
                'is_ats_friendly' => true,
                'ats_compatible' => true,
                'is_featured' => true,
                'credits_required' => 0,
                'html_content' => null,
            ],
        ];

        foreach ($templates as $t) {
            DB::table('cv_templates')->updateOrInsert(
                ['slug' => $t['slug']],
                array_merge($t, [
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        $slugs = [
            'dark-charcoal-ribbon',
            'corporate-monochrome-pro',
            'arch-ribbon-grey',
            'cyan-ocean-wave',
            'red-slate-executive',
        ];
        DB::table('cv_templates')->whereIn('slug', $slugs)->delete();
    }
};
