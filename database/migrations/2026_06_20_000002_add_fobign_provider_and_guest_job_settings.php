<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add Fobign as priority 1 AI provider
        $fobignExists = DB::table('ai_configs')->where('provider_key', 'fobign')->exists();
        if (!$fobignExists) {
            DB::table('ai_configs')->insert([
                'provider_name' => 'Fobign FIN',
                'provider_key' => 'fobign',
                'api_url' => 'https://api.fobign.com/v1',
                'model_code' => 'fin-1-pro',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Shift existing providers down in priority
        DB::table('ai_configs')->where('provider_key', 'gemini')->update(['priority' => 2, 'is_active' => true]);
        DB::table('ai_configs')->where('provider_key', 'openai')->update(['priority' => 3]);
        DB::table('ai_configs')->where('provider_key', 'openrouter')->update(['priority' => 4]);
        DB::table('ai_configs')->where('provider_key', 'fobign')->update([
            'provider_name' => 'Fobign FIN',
            'priority' => 1,
            'is_active' => true,
        ]);

        // 3. Add guest job posting settings
        $settings = [
            ['key' => 'guest_job_posting_enabled', 'value' => 'true', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'guest_job_post_limit', 'value' => '3', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'guest_job_requires_review', 'value' => 'true', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($settings as $setting) {
            $exists = DB::table('settings')->where('key', $setting['key'])->exists();
            if (!$exists) {
                DB::table('settings')->insert($setting);
            }
        }
    }

    public function down(): void
    {
        DB::table('ai_configs')->where('provider_key', 'fobign')->delete();

        // Restore original priorities
        DB::table('ai_configs')->where('provider_key', 'gemini')->update(['priority' => 1]);
        DB::table('ai_configs')->where('provider_key', 'openai')->update(['priority' => 2]);
        DB::table('ai_configs')->where('provider_key', 'openrouter')->update(['priority' => 3]);

        DB::table('settings')->whereIn('key', [
            'guest_job_posting_enabled',
            'guest_job_post_limit',
            'guest_job_requires_review',
        ])->delete();
    }
};
