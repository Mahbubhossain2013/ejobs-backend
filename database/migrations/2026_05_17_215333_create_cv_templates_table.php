<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Added to prevent class not found error

return new class extends Migration
{
    public function up(): void
    {
        // THE FIX: This line guarantees the table is gone before we try to create it.
        Schema::dropIfExists('cv_templates');

        Schema::create('cv_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('preview_image_path');
            $table->boolean('is_premium')->default(false);
            $table->decimal('price', 8, 2)->default(0);
            $table->enum('monetization_model', ['one_time', 'subscription'])->default('one_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default templates safely
        DB::table('cv_templates')->insert([
            [
                'name' => 'Minimalist Free',
                'slug' => 'minimalist-free',
                'preview_image_path' => 'templates/minimalist.png',
                'is_premium' => false,
                'price' => 0,
                'monetization_model' => 'one_time',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Modern Premium',
                'slug' => 'modern-premium',
                'preview_image_path' => 'templates/modern.png',
                'is_premium' => true,
                'price' => 250,
                'monetization_model' => 'one_time',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_templates');
    }
};
