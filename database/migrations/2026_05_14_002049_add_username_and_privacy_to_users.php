<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Unique username for clean URLs
            $table->string('username')->nullable()->unique()->after('name');
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            // Privacy control for the public portfolio
            $table->boolean('is_public')->default(true)->after('user_id');
            // Additional portfolio fields
            $table->json('skills')->nullable();
            $table->json('experience')->nullable();
            $table->json('education')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['is_public', 'skills', 'experience', 'education']);
        });
    }
};