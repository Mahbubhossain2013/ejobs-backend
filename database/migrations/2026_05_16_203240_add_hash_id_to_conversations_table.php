<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // This code safely checks if the columns exist before trying to add them
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'uuid')) {
                $table->string('uuid')->unique()->after('id')->nullable();
            }
            if (!Schema::hasColumn('conversations', 'hash_id')) {
                // If you prefer hash_id, you can keep this, but we will standardize on uuid
                // $table->string('hash_id')->unique()->after('id')->nullable();
            }
            if (!Schema::hasColumn('conversations', 'settings')) {
                $table->json('settings')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['hash_id', 'settings']);
        });
    }
};
