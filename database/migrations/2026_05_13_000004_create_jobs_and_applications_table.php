<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jobs Table
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('salary_range')->nullable();
            $table->string('job_type'); // Full-time, Part-time, Contract
            $table->string('location');
            $table->date('deadline');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Applications Table
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Candidate
            $table->string('resume_path');
            $table->enum('status', ['pending', 'shortlisted', 'rejected', 'interview'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('jobs');
    }
};