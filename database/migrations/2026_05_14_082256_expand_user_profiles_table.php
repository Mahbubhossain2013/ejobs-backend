<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('current_company')->nullable();
            $table->string('current_position')->nullable();
            $table->json('projects')->nullable();
            $table->json('interests')->nullable();
            $table->json('notification_settings')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['current_company', 'current_position', 'projects', 'interests', 'notification_settings']);
        });
    }
};