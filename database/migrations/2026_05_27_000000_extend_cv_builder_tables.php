<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            if (!Schema::hasColumn('resumes', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->constrained('resumes')->onDelete('set null');
            }
            if (!Schema::hasColumn('resumes', 'is_public')) {
                $table->boolean('is_public')->default(false);
            }
            if (!Schema::hasColumn('resumes', 'password')) {
                $table->string('password')->nullable();
            }
            if (!Schema::hasColumn('resumes', 'expires_at')) {
                $table->timestamp('expires_at')->nullable();
            }
            if (!Schema::hasColumn('resumes', 'views_count')) {
                $table->integer('views_count')->default(0);
            }
            if (!Schema::hasColumn('resumes', 'theme_settings')) {
                $table->json('theme_settings')->nullable();
            }
        });

        Schema::table('cv_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('cv_templates', 'html_content')) {
                $table->longText('html_content')->nullable();
            }
            if (!Schema::hasColumn('cv_templates', 'css_content')) {
                $table->longText('css_content')->nullable();
            }
            if (!Schema::hasColumn('cv_templates', 'meta_schema')) {
                $table->json('meta_schema')->nullable();
            }
            if (!Schema::hasColumn('cv_templates', 'is_featured')) {
                $table->boolean('is_featured')->default(false);
            }
            if (!Schema::hasColumn('cv_templates', 'category')) {
                $table->string('category')->default('Corporate');
            }
            if (!Schema::hasColumn('cv_templates', 'ats_compatible')) {
                $table->boolean('ats_compatible')->default(true);
            }
            if (!Schema::hasColumn('cv_templates', 'dark_mode_supported')) {
                $table->boolean('dark_mode_supported')->default(false);
            }
            if (!Schema::hasColumn('cv_templates', 'version')) {
                $table->string('version')->default('1.0.0');
            }
            if (!Schema::hasColumn('cv_templates', 'author')) {
                $table->string('author')->default('Admin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'is_public', 'password', 'expires_at', 'views_count', 'theme_settings']);
        });

        Schema::table('cv_templates', function (Blueprint $table) {
            $table->dropColumn([
                'html_content', 'css_content', 'meta_schema', 'is_featured', 
                'category', 'ats_compatible', 'dark_mode_supported', 'version', 'author'
            ]);
        });
    }
};
