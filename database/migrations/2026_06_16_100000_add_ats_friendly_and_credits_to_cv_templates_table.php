<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cv_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('cv_templates', 'is_ats_friendly')) {
                $table->boolean('is_ats_friendly')->default(true);
            }
            if (!Schema::hasColumn('cv_templates', 'credits_required')) {
                $table->integer('credits_required')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('cv_templates', function (Blueprint $table) {
            $table->dropColumn(['is_ats_friendly', 'credits_required']);
        });
    }
};
