<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('user_profiles', 'upazila')) {
                $table->string('upazila')->nullable()->after('division');
            }
            if (!Schema::hasColumn('user_profiles', 'union')) {
                $table->string('union')->nullable()->after('upazila');
            }
            if (!Schema::hasColumn('user_profiles', 'post_office')) {
                $table->string('post_office')->nullable()->after('union');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['upazila', 'union', 'post_office']);
        });
    }
};
