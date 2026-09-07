<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('badge_key')->unique(); // e.g. verified, premium, pro, top_employer
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // e.g. heroicon-o-shield-check
            $table->string('color')->default('gray'); // CSS/hex color
            $table->integer('priority')->default(0); // for visual sorting order
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('badge_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('badge_id')->constrained('badges')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('assigned_by')->default('system'); // system, admin
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['badge_id', 'user_id']);
        });

        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->string('document_type'); // trade_license, business_registration, identity_proof
            $table->string('document_path');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('admin_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifications');
        Schema::dropIfExists('badge_user');
        Schema::dropIfExists('badges');
    }
};
