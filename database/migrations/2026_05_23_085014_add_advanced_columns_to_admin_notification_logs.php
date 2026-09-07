<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admin_notification_logs', function (Blueprint $table) {
            $table->string('status')->default('sent')->after('sent_count');
            $table->json('channels')->nullable()->after('status');
            $table->string('action_url')->nullable()->after('channels');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_notification_logs', function (Blueprint $table) {
            $table->dropColumn(['status', 'channels', 'action_url']);
        });
    }
};
