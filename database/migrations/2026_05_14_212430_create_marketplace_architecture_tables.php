<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Extend Jobs Table to support Remote Projects
        Schema::table('jobs', function (Blueprint $table) {
            $table->boolean('is_remote_project')->default(false)->after('job_type');
            $table->decimal('budget', 12, 2)->nullable()->after('salary_range');
            
            // Marketplace Lifecycle States
            $table->enum('project_status', [
                'draft', 
                'published', 
                'pending_approval', 
                'in_progress', 
                'submitted', 
                'revision_requested', 
                'completed', 
                'disputed', 
                'cancelled'
            ])->nullable()->after('is_active');
            
            // The candidate hired for the project
            $table->foreignId('assigned_to')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->after('project_status');
        });

        // 2. Wallets System (For both Employers and Candidates)
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 12, 2)->default(0); // Available balance
            $table->decimal('locked_balance', 12, 2)->default(0); // Balance locked in active escrows or withdrawals
            $table->timestamps();
        });

        // 3. Wallet Transactions Log (Double-entry accounting foundation)
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 12, 2);
            $table->string('reference_type'); // e.g., 'escrow_deposit', 'project_payout', 'gateway_deposit'
            $table->unsignedBigInteger('reference_id')->nullable(); // Links to specific escrow or gateway record
            $table->string('description');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('completed');
            $table->timestamps();
        });

        // 4. Escrow System (Holding funds securely during project execution)
        Schema::create('escrows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade'); // The Project
            $table->foreignId('employer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('candidate_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->decimal('amount', 12, 2); // Amount held for the freelancer
            $table->decimal('platform_fee', 12, 2)->default(0); // Commission held for Admin
            
            $table->enum('status', [
                'pending',  // Awaiting employer payment
                'funded',   // Money is securely locked in Admin Escrow
                'released', // Paid out to Candidate
                'refunded', // Returned to Employer
                'disputed'  // Locked pending Admin resolution
            ])->default('pending');
            
            $table->timestamps();
        });

        // 5. Project Delivery System (Tracking the actual work submission)
        Schema::create('project_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->foreignId('candidate_id')->constrained('users')->onDelete('cascade');
            
            $table->text('message'); // Candidate's delivery note
            $table->json('attachments')->nullable(); // URLs of delivered files (ZIP/PDF)
            
            $table->enum('status', [
                'submitted', 
                'approved', 
                'revision_requested', 
                'rejected'
            ])->default('submitted');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_deliveries');
        Schema::dropIfExists('escrows');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn(['is_remote_project', 'budget', 'project_status', 'assigned_to']);
        });
    }
};