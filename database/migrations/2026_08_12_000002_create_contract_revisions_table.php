<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('revised_by')->constrained('users');
            $table->text('changes_summary');
            $table->json('previous_data');
            $table->json('new_data');
            $table->boolean('requires_candidate_approval')->default(false);
            $table->enum('status', ['pending_approval', 'approved', 'rejected'])->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_revisions');
    }
};
