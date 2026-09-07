<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('orientation')->default('landscape');
            $table->string('background_color')->default('#FFFFFF');
            $table->string('primary_color')->default('#1E3A5F');
            $table->string('accent_color')->default('#D4AF37');
            $table->string('logo_path')->nullable();
            $table->string('watermark_path')->nullable();
            $table->json('layout_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('certificate_templates')->nullOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained('skill_enrollments')->nullOnDelete();
            $table->foreignId('attempt_id')->nullable()->constrained('skill_assessment_attempts')->nullOnDelete();
            $table->string('type')->default('course_completion');
            $table->string('recipient_name');
            $table->string('course_title');
            $table->text('description')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->string('issued_at');
            $table->string('expires_at')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_format')->default('pdf');
            $table->json('metadata')->nullable();
            $table->boolean('is_verified')->default(true);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index('certificate_number');
        });

        Schema::create('ssl_certificate_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('domain');
            $table->enum('cert_type', ['letsencrypt', 'self_signed', 'commercial', 'wildcard'])->default('letsencrypt');
            $table->enum('format', ['pem', 'pfx', 'p12', 'der'])->default('pem');
            $table->string('certificate_path');
            $table->string('private_key_path')->nullable();
            $table->string('chain_path')->nullable();
            $table->string('passphrase')->nullable();
            $table->string('passphrase_algorithm')->default('sha256');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('renewal_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('validation_result')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['domain', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssl_certificate_configs');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('certificate_templates');
    }
};
