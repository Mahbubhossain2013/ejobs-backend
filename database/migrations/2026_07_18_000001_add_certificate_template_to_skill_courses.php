<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skill_courses', function (Blueprint $table) {
            $table->foreignId('certificate_template_id')->nullable()->after('is_certified')->constrained('certificate_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('skill_courses', function (Blueprint $table) {
            $table->dropForeign(['certificate_template_id']);
            $table->dropColumn('certificate_template_id');
        });
    }
};
