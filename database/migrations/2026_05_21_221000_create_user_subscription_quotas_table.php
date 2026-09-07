<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_subscription_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_subscription_id')->constrained('user_subscriptions')->onDelete('cascade');
            $table->string('feature_key');
            $table->integer('used')->default(0);
            $table->integer('max_limit')->default(0); // e.g. 10 requests, 9999 for unlimited
            $table->timestamp('reset_at')->nullable();
            $table->timestamps();

            $table->unique(['user_subscription_id', 'feature_key'], 'user_sub_feature_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscription_quotas');
    }
};
