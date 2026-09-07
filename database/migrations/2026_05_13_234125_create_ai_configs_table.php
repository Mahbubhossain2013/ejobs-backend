<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_configs', function (Blueprint $table) {
            $table->id();
            $table->string('provider_name'); // Gemini, OpenAI, OpenRouter
            $table->string('provider_key');  // Unique key for selection
            $table->text('api_key')->nullable();
            $table->string('api_url')->nullable();
            $table->string('model_code')->nullable(); // e.g., gemini-1.5-pro, gpt-4
            $table->integer('timeout')->default(30);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // Seed default providers
        DB::table('ai_configs')->insert([
            ['provider_name' => 'Google Gemini', 'provider_key' => 'gemini', 'is_active' => true, 'created_at' => now()],
            ['provider_name' => 'OpenAI GPT', 'provider_key' => 'openai', 'is_active' => false, 'created_at' => now()],
            ['provider_name' => 'OpenRouter', 'provider_key' => 'openrouter', 'is_active' => false, 'created_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_configs');
    }
};