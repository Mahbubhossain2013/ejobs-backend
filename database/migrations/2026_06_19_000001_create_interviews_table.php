<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->constrained('job_applications')->cascadeOnDelete();
            $table->foreignId('employer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['in_person', 'video', 'phone'])->default('video');
            $table->dateTime('scheduled_at');
            $table->integer('duration_minutes')->default(30);
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->enum('candidate_response', ['pending', 'accepted', 'declined'])->default('pending');
            $table->text('candidate_note')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->text('outcome')->nullable();
            $table->timestamps();

            $table->index(['job_id', 'status']);
            $table->index(['candidate_id', 'candidate_response']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
