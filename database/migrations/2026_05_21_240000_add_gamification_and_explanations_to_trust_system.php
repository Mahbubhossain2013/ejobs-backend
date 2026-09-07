<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add fields to badges table
        Schema::table('badges', function (Blueprint $table) {
            $table->string('rarity')->default('common')->after('is_automatic'); // common, rare, legendary
            $table->boolean('is_hidden')->default(false)->after('rarity');
            $table->string('badge_type')->default('standard')->after('is_hidden'); // standard, seasonal, event
        });

        // 2. Add earned_at to badge_user pivot table
        Schema::table('badge_user', function (Blueprint $table) {
            $table->timestamp('earned_at')->nullable()->after('is_visible');
        });

        // 3. Add fields to user_profiles table (Candidates)
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->json('trust_explanations')->nullable()->after('profile_completion_percentage');
            $table->json('profile_strength_breakdown')->nullable()->after('trust_explanations');
        });

        // 4. Add fields to companies table (Employers)
        Schema::table('companies', function (Blueprint $table) {
            $table->json('trust_explanations')->nullable()->after('profile_completion_percentage');
            $table->json('profile_strength_breakdown')->nullable()->after('trust_explanations');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['trust_explanations', 'profile_strength_breakdown']);
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['trust_explanations', 'profile_strength_breakdown']);
        });

        Schema::table('badge_user', function (Blueprint $table) {
            $table->dropColumn(['earned_at']);
        });

        Schema::table('badges', function (Blueprint $table) {
            $table->dropColumn(['rarity', 'is_hidden', 'badge_type']);
        });
    }
};
