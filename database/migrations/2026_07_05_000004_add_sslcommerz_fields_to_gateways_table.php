<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->string('sslc_store_id')->nullable()->after('callback_url');
            $table->string('sslc_store_password')->nullable()->after('sslc_store_id');
        });
    }

    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn(['sslc_store_id', 'sslc_store_password']);
        });
    }
};
