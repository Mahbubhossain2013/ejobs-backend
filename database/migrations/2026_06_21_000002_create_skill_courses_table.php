<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('category'); // welding, driving, catering, electrician, etc.
            $table->string('difficulty')->default('beginner'); // beginner, intermediate, advanced
            $table->integer('duration_hours')->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency')->default('BDT');
            $table->string('instructor_name')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_certified')->default(true);
            $table->integer('enrollment_count')->default(0);
            $table->integer('max_enrollments')->nullable();
            $table->timestamps();
        });

        Schema::create('skill_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('skill_courses')->cascadeOnDelete();
            $table->enum('status', ['enrolled', 'in_progress', 'completed', 'dropped'])->default('enrolled');
            $table->decimal('progress', 5, 2)->default(0); // 0-100
            $table->date('enrolled_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->string('certificate_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
        });

        Schema::create('skill_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('skill_courses')->cascadeOnDelete();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('video_url')->nullable();
            $table->integer('order')->default(0);
            $table->integer('duration_minutes')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_lessons');
        Schema::dropIfExists('skill_enrollments');
        Schema::dropIfExists('skill_courses');
    }
};
