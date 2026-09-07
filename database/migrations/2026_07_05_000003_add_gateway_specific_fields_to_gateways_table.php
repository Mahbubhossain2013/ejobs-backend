<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            // Nagad fields
            $table->string('nagad_merchant_id')->nullable()->after('app_secret');
            $table->text('nagad_private_key')->nullable()->after('nagad_merchant_id');
            $table->text('nagad_pg_public_key')->nullable()->after('nagad_private_key');
            
            // Rocket fields
            $table->string('rocket_merchant_id')->nullable()->after('nagad_pg_public_key');
            $table->string('rocket_api_password')->nullable()->after('rocket_merchant_id');
            $table->string('rocket_api_key')->nullable()->after('rocket_api_password');
            
            // Callback URL
            $table->string('callback_url')->nullable()->after('rocket_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn([
                'nagad_merchant_id', 'nagad_private_key', 'nagad_pg_public_key',
                'rocket_merchant_id', 'rocket_api_password', 'rocket_api_key',
                'callback_url'
            ]);
        });
    }
};
