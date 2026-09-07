<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->string('username')->nullable()->after('api_key');
            $table->string('password')->nullable()->after('username');
            $table->string('app_key')->nullable()->after('password');
            $table->string('app_secret')->nullable()->after('app_key');
        });
    }

    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn(['username', 'password', 'app_key', 'app_secret']);
        });
    }
};
