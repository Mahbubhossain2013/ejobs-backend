<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'is_read', 'sender_id'], 'idx_messages_unread_count');
            $table->index(['conversation_id', 'created_at'], 'idx_messages_conv_time');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('idx_messages_unread_count');
            $table->dropIndex('idx_messages_conv_time');
        });
    }
};
