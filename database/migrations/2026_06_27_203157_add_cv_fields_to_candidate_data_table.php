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
        Schema::table('candidate_data', function (Blueprint $table) {
            $table->json('certifications')->nullable()->after('projects');
            $table->json('languages')->nullable()->after('certifications');
            $table->json('awards')->nullable()->after('languages');
            $table->json('hobbies')->nullable()->after('awards');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_data', function (Blueprint $table) {
            $table->dropColumn(['certifications', 'languages', 'awards', 'hobbies']);
        });
    }
};
