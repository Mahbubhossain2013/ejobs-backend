<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->string('drive_type')->default('automation')->after('name');
            $table->text('instruction')->nullable()->after('display_name');
            $table->string('personal_number')->nullable()->after('instruction');
        });
    }

    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn(['drive_type', 'instruction', 'personal_number']);
        });
    }
};
