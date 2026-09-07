<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_message_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->string('status_filter')->default('all');
            $table->enum('channel', ['email', 'sms'])->default('email');
            $table->enum('template', ['interview', 'update', 'custom'])->default('interview');
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->text('sms_message')->nullable();
            $table->string('interview_date')->nullable();
            $table->string('interview_location')->nullable();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('sms_count')->default(0);
            $table->decimal('sms_cost', 10, 2)->default(0);
            $table->enum('status_badge', ['pending', 'processing', 'completed', 'partial_failed'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bulk_message_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('bulk_message_batches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('job_applications')->nullOnDelete();
            $table->enum('channel', ['email', 'sms'])->default('email');
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->enum('status', ['pending', 'queued', 'sent', 'failed', 'skipped'])->default('pending');
            $table->unsignedInteger('sms_count')->default(0);
            $table->decimal('sms_cost', 10, 2)->default(0);
            $table->text('error')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_message_recipients');
        Schema::dropIfExists('bulk_message_batches');
    }
};