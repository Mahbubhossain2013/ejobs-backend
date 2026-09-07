<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::table('conversations', function (Blueprint $table) {
            // Drop constraints safely
            $table->dropForeign(['job_id']);
            $table->dropUnique(['job_id', 'employer_id', 'candidate_id']);
        });

        // Make column nullable
        DB::statement('ALTER TABLE conversations MODIFY job_id BIGINT UNSIGNED NULL;');

        Schema::table('conversations', function (Blueprint $table) {
            // Re-apply foreign key
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
            // Re-apply unique constraint
            $table->unique(['job_id', 'employer_id', 'candidate_id'], 'conv_unique_index');
        });
    }
};