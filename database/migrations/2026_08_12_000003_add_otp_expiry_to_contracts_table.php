<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->timestamp('employer_otp_expires_at')->nullable()->after('employer_otp');
            $table->timestamp('candidate_otp_expires_at')->nullable()->after('candidate_otp');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['employer_otp_expires_at', 'candidate_otp_expires_at']);
        });
    }
};
