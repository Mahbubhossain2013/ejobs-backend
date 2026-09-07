<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('skill_courses')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('passing_score')->default(70);
            $table->integer('time_limit_minutes')->nullable();
            $table->integer('max_attempts')->default(3);
            $table->integer('questions_to_show')->nullable();
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('show_results_immediately')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('skill_assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('skill_assessments')->cascadeOnDelete();
            $table->text('question');
            $table->enum('type', ['multiple_choice', 'true_false', 'short_answer'])->default('multiple_choice');
            $table->json('options')->nullable();
            $table->string('correct_answer');
            $table->integer('points')->default(1);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('skill_assessment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained('skill_assessments')->cascadeOnDelete();
            $table->enum('status', ['in_progress', 'completed', 'timed_out'])->default('in_progress');
            $table->integer('score')->nullable();
            $table->integer('total_points')->nullable();
            $table->boolean('is_passed')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('time_spent_seconds')->nullable();
            $table->timestamps();
        });

        Schema::create('skill_assessment_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('skill_assessment_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('skill_assessment_questions')->cascadeOnDelete();
            $table->text('user_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->integer('points_earned')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_assessment_answers');
        Schema::dropIfExists('skill_assessment_attempts');
        Schema::dropIfExists('skill_assessment_questions');
        Schema::dropIfExists('skill_assessments');
    }
};
