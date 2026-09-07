<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add new AI-related plan features
        $features = [
            ['name' => 'AI CV Builder', 'feature_key' => 'ai_cv_builder', 'description' => 'AI-powered CV generation from prompt', 'type' => 'boolean', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'AI Skill Assessment', 'feature_key' => 'ai_skill_assessment', 'description' => 'Number of skill assessments per month', 'type' => 'integer', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Certificate Generation', 'feature_key' => 'certificate_generation', 'description' => 'Generate certificates for completed assessments', 'type' => 'boolean', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'AI Interview Prep', 'feature_key' => 'ai_interview_prep', 'description' => 'Number of AI interview sessions per month', 'type' => 'integer', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('plan_features')->insert($features);
    }

    public function down(): void
    {
        DB::table('plan_features')->whereIn('feature_key', [
            'ai_cv_builder', 'ai_skill_assessment', 'certificate_generation', 'ai_interview_prep'
        ])->delete();
    }
};
