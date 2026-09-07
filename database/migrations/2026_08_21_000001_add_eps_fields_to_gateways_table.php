<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->string('eps_merchant_id')->nullable()->after('sslc_store_password');
            $table->string('eps_store_id')->nullable()->after('eps_merchant_id');
            $table->string('eps_hash_key')->nullable()->after('eps_store_id');
            $table->string('eps_user_name')->nullable()->after('eps_hash_key');
            $table->string('eps_password')->nullable()->after('eps_user_name');
        });
    }

    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn(['eps_merchant_id', 'eps_store_id', 'eps_hash_key', 'eps_user_name', 'eps_password']);
        });
    }
};
