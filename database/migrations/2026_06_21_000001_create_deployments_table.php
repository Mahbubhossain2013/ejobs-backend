<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('employer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->string('job_title');
            $table->string('company_name')->nullable();
            $table->string('destination_country');
            $table->string('destination_city')->nullable();
            $table->enum('status', ['initiated', 'in_progress', 'completed', 'on_hold', 'cancelled'])->default('initiated');
            $table->date('expected_joining_date')->nullable();
            $table->date('actual_joining_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('deployment_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deployment_id')->constrained('deployments')->cascadeOnDelete();
            $table->string('stage_name'); // visa, medical, ticketing, flight, etc.
            $table->string('stage_label'); // Human readable: "Visa Processing"
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->integer('order')->default(0);
            $table->date('started_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->date('deadline')->nullable();
            $table->string('document_path')->nullable();
            $table->text('remarks')->nullable();
            $table->json('metadata')->nullable(); // extra data like flight number, visa number etc
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_stages');
        Schema::dropIfExists('deployments');
    }
};
