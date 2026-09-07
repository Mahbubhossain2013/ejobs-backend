<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('jobs', 'is_guest_post')) {
                $table->boolean('is_guest_post')->default(false)->after('company_id');
            }
            if (!Schema::hasColumn('jobs', 'guest_email')) {
                $table->string('guest_email')->nullable()->after('is_guest_post');
            }
            if (!Schema::hasColumn('jobs', 'contact_email')) {
                $table->string('contact_email')->nullable()->after('contact_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['is_guest_post', 'guest_email', 'contact_email']);
        });
    }
};
