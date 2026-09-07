<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Extend company_reviews
        Schema::table('company_reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('company_reviews', 'rating_work_culture')) {
                $table->integer('rating_work_culture')->default(5)->after('rating');
                $table->integer('rating_salary')->default(5)->after('rating_work_culture');
                $table->integer('rating_management')->default(5)->after('rating_salary');
                $table->integer('rating_growth')->default(5)->after('rating_management');
                $table->integer('rating_work_life_balance')->default(5)->after('rating_growth');
                $table->decimal('ai_toxicity_score', 5, 2)->nullable()->after('comment');
                $table->decimal('ai_fake_probability', 5, 2)->nullable()->after('ai_toxicity_score');
                $table->decimal('ai_duplicate_score', 5, 2)->nullable()->after('ai_fake_probability');
                $table->text('moderation_details')->nullable()->after('ai_duplicate_score');
            }
        });

        // 2. Create profile_views table
        Schema::create('profile_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('employer_id')->constrained('users')->onDelete('cascade');
            $table->string('recruiter_role')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->integer('view_count')->default(1);
            $table->timestamps();

            $table->index('candidate_id');
            $table->index('employer_id');
        });

        // 3. Create company_updates table
        Schema::create('company_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->text('content');
            $table->string('media_path')->nullable();
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->timestamps();

            $table->index('company_id');
        });

        // 4. Create company_update_reactions table
        Schema::create('company_update_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('update_id')->constrained('company_updates')->onDelete('cascade');
            $table->string('reaction_type')->default('like');
            $table->timestamps();

            $table->unique(['user_id', 'update_id']);
        });

        // 5. Create company_update_comments table
        Schema::create('company_update_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('update_id')->constrained('company_updates')->onDelete('cascade');
            $table->text('comment');
            $table->timestamps();
        });

        // 6. Create company_brochures table
        Schema::create('company_brochures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('title');
            $table->string('file_path');
            $table->integer('download_count')->default(0);
            $table->timestamps();
        });

        // 7. Create company_culture_photos table
        Schema::create('company_culture_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('file_path');
            $table->string('caption')->nullable();
            $table->timestamps();
        });

        // 8. Create company_awards table
        Schema::create('company_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('title');
            $table->string('issuer');
            $table->integer('year');
            $table->text('description')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        // 9. Create candidate_interviews table
        Schema::create('candidate_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('interview_type'); // technical, hr
            $table->json('history'); // questions and answers transcript
            $table->integer('ai_score');
            $table->json('weak_areas');
            $table->json('practice_topics');
            $table->text('communication_feedback')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_reviews', function (Blueprint $table) {
            $table->dropColumn([
                'rating_work_culture', 'rating_salary', 'rating_management',
                'rating_growth', 'rating_work_life_balance', 'ai_toxicity_score',
                'ai_fake_probability', 'ai_duplicate_score', 'moderation_details'
            ]);
        });

        Schema::dropIfExists('profile_views');
        Schema::dropIfExists('company_update_reactions');
        Schema::dropIfExists('company_update_comments');
        Schema::dropIfExists('company_updates');
        Schema::dropIfExists('company_brochures');
        Schema::dropIfExists('company_culture_photos');
        Schema::dropIfExists('company_awards');
        Schema::dropIfExists('candidate_interviews');
    }
};
