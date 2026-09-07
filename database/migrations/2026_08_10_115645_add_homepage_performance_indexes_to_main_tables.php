<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('jobs', function (Blueprint $table) {
                $table->index('job_type');
                $table->index(['is_active', 'created_at']);
                $table->index(['company_id', 'is_active']);
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('notices', function (Blueprint $table) {
                $table->index('category');
                $table->index(['category', 'created_at']);
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('categories', function (Blueprint $table) {
                if (Schema::hasColumn('categories', 'is_active')) {
                    $table->index('is_active');
                }
                if (Schema::hasColumn('categories', 'is_active') && Schema::hasColumn('categories', 'is_highlighted')) {
                    $table->index(['is_active', 'is_highlighted']);
                }
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('company_reviews', function (Blueprint $table) {
                $table->index(['company_id', 'status']);
            });
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['job_type']);
            $table->dropIndex(['is_active', 'created_at']);
            $table->dropIndex(['company_id', 'is_active']);
        });

        Schema::table('notices', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['category', 'created_at']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['is_active', 'is_highlighted']);
        });

        Schema::table('company_reviews', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'status']);
        });
    }
};
