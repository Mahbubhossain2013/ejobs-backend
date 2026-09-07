<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('admin_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->string('target_audience');
            $table->integer('sent_count');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('admin_notification_logs'); }
};