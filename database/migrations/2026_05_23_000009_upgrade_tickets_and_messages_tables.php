<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop existing support_tickets table if it exists (safe since it has 0 records)
        Schema::dropIfExists('support_tickets');

        // 2. Re-create support_tickets with full WHMCS features
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('subject');
            $table->string('category')->default('general');
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->string('status')->default('open'); // open, pending, answered, resolved, closed
            $table->timestamps();
        });

        // 3. Create ticket_messages table for replies
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('message');
            $table->boolean('is_admin_reply')->default(false);
            $table->string('admin_role_label')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });

        // 4. Add admin_role column to users table for transparent roles
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'admin_role')) {
                $table->string('admin_role')->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'admin_role')) {
                $table->dropColumn('admin_role');
            }
        });
    }
};
