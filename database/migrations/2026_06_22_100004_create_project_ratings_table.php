<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->onDelete('set null');
            $table->foreignId('rater_id')->constrained('users');
            $table->foreignId('rated_id')->constrained('users');
            $table->enum('rater_role', ['employer', 'candidate']);
            $table->unsignedTinyInteger('skill_rating')->comment('1-5');
            $table->unsignedTinyInteger('communication_rating')->comment('1-5');
            $table->unsignedTinyInteger('delivery_rating')->comment('1-5');
            $table->unsignedTinyInteger('professionalism_rating')->comment('1-5');
            $table->decimal('overall_rating', 3, 2)->virtualAs('ROUND((skill_rating + communication_rating + delivery_rating + professionalism_rating) / 4, 2)');
            $table->text('comment')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->unique(['job_id', 'rater_id', 'rated_id'], 'unique_project_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_ratings');
    }
};
