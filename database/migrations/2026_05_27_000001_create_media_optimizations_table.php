<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_optimizations', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->string('unique_hash')->unique();
            $table->unsignedBigInteger('original_size');
            $table->unsignedBigInteger('optimized_size');
            $table->unsignedBigInteger('saved_bytes');
            $table->string('format')->default('webp');
            $table->string('status')->default('success'); // success, failed, pending
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_optimizations');
    }
};
