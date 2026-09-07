<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $templates = [
            [
                'name' => 'Modern Noir Curve',
                'slug' => 'black-curved-modern',
                'preview_image_path' => 'templates/black-curved-modern.png',
                'is_premium' => false,
                'price' => 0,
                'category' => 'technical',
                'is_active' => true,
                'is_ats_friendly' => true,
                'ats_compatible' => true,
                'is_featured' => true,
                'credits_required' => 0,
                'html_content' => null,
            ],
            [
                'name' => 'Black & Amber Duo',
                'slug' => 'black-orange-duo',
                'preview_image_path' => 'templates/black-orange-duo.png',
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
                'name' => 'Navy Gold Executive',
                'slug' => 'navy-gold-executive',
                'preview_image_path' => 'templates/navy-gold-executive.png',
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
            [
                'name' => 'Three Band Horizon',
                'slug' => 'three-band-horizontal',
                'preview_image_path' => 'templates/three-band-horizontal.png',
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
                'name' => 'Editorial Taupe Wave',
                'slug' => 'curved-taupe-minimal',
                'preview_image_path' => 'templates/curved-taupe-minimal.png',
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
            'black-curved-modern',
            'black-orange-duo',
            'navy-gold-executive',
            'three-band-horizontal',
            'curved-taupe-minimal',
        ];
        DB::table('cv_templates')->whereIn('slug', $slugs)->delete();
    }
};
