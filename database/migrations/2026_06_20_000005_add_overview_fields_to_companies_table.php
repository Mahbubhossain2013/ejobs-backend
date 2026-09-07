<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->json('why_join_us')->nullable()->after('values');
            $table->json('top_skills')->nullable()->after('why_join_us');
            $table->string('city')->nullable()->after('location');
            $table->string('facebook')->nullable()->after('website');
            $table->string('linkedin')->nullable()->after('facebook');
            $table->unsignedInteger('profile_views_count')->default(0)->after('linkedin');
            $table->unsignedInteger('response_rate')->default(0)->after('profile_views_count');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'why_join_us', 'top_skills', 'city', 'facebook', 'linkedin',
                'profile_views_count', 'response_rate',
            ]);
        });
    }
};
