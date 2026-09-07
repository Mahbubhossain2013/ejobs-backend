<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->decimal('salary_min', 12, 2)->nullable()->after('salary_range');
            $table->decimal('salary_max', 12, 2)->nullable()->after('salary_min');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['salary_min', 'salary_max']);
        });
    }
};
