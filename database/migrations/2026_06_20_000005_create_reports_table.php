<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');
            $table->string('reportable_type'); // e.g. Job, Company, User
            $table->unsignedBigInteger('reportable_id');
            $table->string('reason'); // spam, scam, inappropriate, fake, duplicate, other
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // pending, reviewed, resolved, dismissed
            $table->text('admin_notes')->nullable();
            $table->string('action_taken')->nullable(); // warning, job_removed, user_banned, no_action
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
