<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->onDelete('cascade');
            $table->foreignId('employer_id')->constrained('users');
            $table->foreignId('candidate_id')->constrained('users');
            $table->foreignId('job_application_id')->nullable()->constrained('job_applications')->onDelete('set null');

            $table->string('title');
            $table->text('project_scope')->nullable();
            $table->decimal('budget', 12, 2);
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->date('delivery_date')->nullable();
            $table->enum('status', [
                'draft', 'pending_employer', 'pending_candidate',
                'employer_signed', 'candidate_signed', 'active',
                'completed', 'terminated', 'disputed'
            ])->default('draft');

            // Contract clauses
            $table->text('ownership_clause')->nullable();
            $table->text('confidentiality_clause')->nullable();
            $table->text('penalty_clause')->nullable();
            $table->text('dispute_clause')->nullable();
            $table->text('additional_terms')->nullable();

            // E-signature fields
            $table->string('employer_otp')->nullable();
            $table->timestamp('employer_signed_at')->nullable();
            $table->string('candidate_otp')->nullable();
            $table->timestamp('candidate_signed_at')->nullable();

            $table->text('rejection_reason')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->json('meta_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
