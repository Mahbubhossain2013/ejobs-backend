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
        Schema::create('company_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // candidate user
            $table->foreignId('company_id')->constrained()->onDelete('cascade'); // employer company
            $table->integer('rating'); // 1-5 stars
            $table->text('comment');
            $table->boolean('is_anonymous')->default(false);
            $table->string('status')->default('approved'); // approved, pending, flagged, rejected
            $table->timestamps();

            $table->unique(['user_id', 'company_id']);
            $table->index('user_id');
            $table->index('company_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_reviews');
    }
};
