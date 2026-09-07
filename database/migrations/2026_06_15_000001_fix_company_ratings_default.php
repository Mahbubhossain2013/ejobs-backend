<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Set rating to 0.00 for companies with no approved reviews
        DB::table('companies')
            ->whereNotIn('id', function ($q) {
                $q->select('company_id')->from('company_reviews')->where('status', 'approved');
            })
            ->update(['rating' => 0.00]);
    }

    public function down(): void
    {
        // No rollback needed
    }
};
