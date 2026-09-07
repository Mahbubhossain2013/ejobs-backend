<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('user_profiles', 'trade_license_number')) {
                $table->string('trade_license_number')->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'trade_license_document')) {
                $table->string('trade_license_document')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['trade_license_number', 'trade_license_document']);
        });
    }
};