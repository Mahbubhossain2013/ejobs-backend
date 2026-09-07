<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('rating', 3, 2)->default(0.00)->change();
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->decimal('rating', 3, 2)->default(0.00)->change();
        });

        DB::table('companies')
            ->whereNotIn('id', function ($q) {
                $q->select('company_id')->from('company_reviews')->where('status', 'approved');
            })
            ->update(['rating' => 0.00]);

        DB::table('user_profiles')
            ->whereNotIn('user_id', function ($q) {
                $q->select('rated_id')->from('project_ratings');
            })
            ->update(['rating' => 0.00]);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('rating', 3, 2)->default(5.00)->change();
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->decimal('rating', 3, 2)->default(5.00)->change();
        });
    }
};
