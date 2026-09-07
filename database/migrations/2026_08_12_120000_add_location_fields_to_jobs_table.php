<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->string('division', 100)->nullable()->after('location');
            $table->string('district', 100)->nullable()->after('division');
            $table->string('upazila', 100)->nullable()->after('district');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['division', 'district', 'upazila']);
        });
    }
};
