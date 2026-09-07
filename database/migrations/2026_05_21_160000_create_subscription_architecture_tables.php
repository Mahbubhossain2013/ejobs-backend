<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('billing_cycle'); // monthly, yearly, lifetime, trial
            $table->integer('duration_days')->nullable(); // e.g. 30, 365, null
            $table->integer('trial_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('feature_key')->unique(); // e.g. ai_career_tools
            $table->text('description')->nullable();
            $table->string('type')->default('boolean'); // boolean, integer, string
            $table->timestamps();
        });

        Schema::create('feature_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->foreignId('plan_feature_id')->constrained('plan_features')->onDelete('cascade');
            $table->string('value'); // true, false, 50, unlimited etc.
            $table->timestamps();

            $table->unique(['subscription_plan_id', 'plan_feature_id'], 'plan_feature_unique');
        });

        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('billing_cycle');
            $table->string('payment_status')->default('paid'); // paid, pending, failed
            $table->string('status')->default('active'); // active, expired, canceled, pending
            $table->boolean('is_recurring')->default(true);
            $table->timestamp('canceled_at')->nullable();
            $table->json('plan_details')->nullable(); // Snapshot of plan metadata & active features
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('feature_values');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('subscription_plans');
    }
};
