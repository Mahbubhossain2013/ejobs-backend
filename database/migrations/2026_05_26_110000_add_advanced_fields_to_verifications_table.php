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
        Schema::table('verifications', function (Blueprint $table) {
            // Make existing document fields nullable to support non-document (phone/email) flows
            $table->string('document_type')->nullable()->change();
            $table->string('document_path')->nullable()->change();

            // Add new advanced verification columns
            $table->string('verification_type')->default('nid')->after('company_id'); // nid, phone, email, employer
            $table->string('document_back_path')->nullable()->after('document_path');
            $table->string('nid_number')->nullable()->after('document_back_path');
            $table->date('dob')->nullable()->after('nid_number');
            $table->string('phone')->nullable()->after('dob');
            $table->string('email')->nullable()->after('phone');
            $table->string('otp_code')->nullable()->after('email');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            $table->integer('ai_confidence_score')->nullable()->after('otp_expires_at');
            $table->json('ai_analysis_data')->nullable()->after('ai_confidence_score');
            $table->string('ip_address')->nullable()->after('ai_analysis_data');
            $table->string('device_fingerprint')->nullable()->after('ip_address');
            $table->integer('reminder_count')->default(0)->after('device_fingerprint');
            $table->timestamp('last_reminder_sent_at')->nullable()->after('reminder_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verifications', function (Blueprint $table) {
            $table->string('document_type')->nullable(false)->change();
            $table->string('document_path')->nullable(false)->change();

            $table->dropColumn([
                'verification_type',
                'document_back_path',
                'nid_number',
                'dob',
                'phone',
                'email',
                'otp_code',
                'otp_expires_at',
                'ai_confidence_score',
                'ai_analysis_data',
                'ip_address',
                'device_fingerprint',
                'reminder_count',
                'last_reminder_sent_at',
            ]);
        });
    }
};
