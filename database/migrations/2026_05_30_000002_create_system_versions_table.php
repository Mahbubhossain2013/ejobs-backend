<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version');
            $table->string('build_number')->nullable();
            $table->text('release_notes')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamp('installed_at');
            $table->timestamps();
        });

        Schema::create('system_update_logs', function (Blueprint $table) {
            $table->id();
            $table->string('from_version');
            $table->string('to_version');
            $table->enum('status', ['success', 'failed', 'pending']);
            $table->text('notes')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('performed_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_update_logs');
        Schema::dropIfExists('system_versions');
    }
};
