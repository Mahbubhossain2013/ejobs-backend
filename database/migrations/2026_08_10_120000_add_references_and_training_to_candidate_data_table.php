<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_data', function (Blueprint $table) {
            $table->json('references')->nullable()->after('hobbies');
            $table->json('training')->nullable()->after('references');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_data', function (Blueprint $table) {
            $table->dropColumn(['references', 'training']);
        });
    }
};
