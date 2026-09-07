<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->decimal('expected_salary', 12, 2)->nullable()->after('delivery_days');
            $table->string('portfolio_link', 500)->nullable()->after('expected_salary');
            $table->json('meta_data')->nullable()->after('portfolio_link');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn(['expected_salary', 'portfolio_link', 'meta_data']);
        });
    }
};
