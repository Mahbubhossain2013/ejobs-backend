<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->unique(); // One data record per candidate
            
            // Structured Data for Auto-Generation
            $table->json('personal_info')->nullable(); // name, title, phone, email, address
            $table->json('skills')->nullable(); // array of strings
            $table->json('experience')->nullable(); // array of objects {title, company, duration, desc}
            $table->json('education')->nullable(); // array of objects {degree, institution, year}
            $table->json('projects')->nullable(); // array of objects
            $table->json('social_links')->nullable(); // github, linkedin etc.

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_data');
    }
};