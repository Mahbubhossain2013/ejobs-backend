<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // jobs: add indexes only for columns that exist
        Schema::table('jobs', function (Blueprint $table) {
            if (Schema::hasColumn('jobs', 'status') && Schema::hasColumn('jobs', 'type')) {
                $table->index(['status', 'type'], 'jobs_status_type_index');
            }
            if (Schema::hasColumn('jobs', 'company_id')) {
                $table->index('company_id', 'jobs_company_id_index');
            }
            if (Schema::hasColumn('jobs', 'location')) {
                $table->index('location', 'jobs_location_index');
            }
            if (Schema::hasColumn('jobs', 'is_active')) {
                $table->index('is_active', 'jobs_is_active_index');
            }
            if (Schema::hasColumn('jobs', 'is_remote_project')) {
                $table->index('is_remote_project', 'jobs_is_remote_project_index');
            }
        });

        // escrows: filtering by status and employer/candidate
        if (Schema::hasTable('escrows')) {
            Schema::table('escrows', function (Blueprint $table) {
                if (Schema::hasColumn('escrows', 'status') && Schema::hasColumn('escrows', 'employer_id')) {
                    $table->index(['status', 'employer_id'], 'escrows_status_employer_index');
                }
                if (Schema::hasColumn('escrows', 'status') && Schema::hasColumn('escrows', 'candidate_id')) {
                    $table->index(['status', 'candidate_id'], 'escrows_status_candidate_index');
                }
                if (Schema::hasColumn('escrows', 'job_id')) {
                    $table->index('job_id', 'escrows_job_id_index');
                }
            });
        }

        // wallet_transactions: ledger queries by wallet, type, status
        if (Schema::hasTable('wallet_transactions')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('wallet_transactions', 'wallet_id') && Schema::hasColumn('wallet_transactions', 'type')) {
                    $table->index(['wallet_id', 'type'], 'wallet_transactions_wallet_type_index');
                }
                if (Schema::hasColumn('wallet_transactions', 'status')) {
                    $table->index('status', 'wallet_transactions_status_index');
                }
                if (Schema::hasColumn('wallet_transactions', 'reference_type')) {
                    $table->index('reference_type', 'wallet_transactions_reference_type_index');
                }
            });
        }

        // job_applications: user applications and status filtering
        if (Schema::hasTable('job_applications')) {
            Schema::table('job_applications', function (Blueprint $table) {
                if (Schema::hasColumn('job_applications', 'user_id') && Schema::hasColumn('job_applications', 'status')) {
                    $table->index(['user_id', 'status'], 'job_applications_user_status_index');
                }
                if (Schema::hasColumn('job_applications', 'job_id')) {
                    $table->index('job_id', 'job_applications_job_id_index');
                }
            });
        }

        // notifications: polymorphic index for notification queries
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (Schema::hasColumn('notifications', 'notifiable_type') && Schema::hasColumn('notifications', 'notifiable_id')) {
                    $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read_index');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (Schema::hasIndex('jobs', 'jobs_status_type_index')) $table->dropIndex('jobs_status_type_index');
            if (Schema::hasIndex('jobs', 'jobs_company_id_index')) $table->dropIndex('jobs_company_id_index');
            if (Schema::hasIndex('jobs', 'jobs_location_index')) $table->dropIndex('jobs_location_index');
            if (Schema::hasIndex('jobs', 'jobs_is_active_index')) $table->dropIndex('jobs_is_active_index');
            if (Schema::hasIndex('jobs', 'jobs_is_remote_project_index')) $table->dropIndex('jobs_is_remote_project_index');
        });

        if (Schema::hasTable('escrows')) {
            Schema::table('escrows', function (Blueprint $table) {
                if (Schema::hasIndex('escrows', 'escrows_status_employer_index')) $table->dropIndex('escrows_status_employer_index');
                if (Schema::hasIndex('escrows', 'escrows_status_candidate_index')) $table->dropIndex('escrows_status_candidate_index');
                if (Schema::hasIndex('escrows', 'escrows_job_id_index')) $table->dropIndex('escrows_job_id_index');
            });
        }

        if (Schema::hasTable('wallet_transactions')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                if (Schema::hasIndex('wallet_transactions', 'wallet_transactions_wallet_type_index')) $table->dropIndex('wallet_transactions_wallet_type_index');
                if (Schema::hasIndex('wallet_transactions', 'wallet_transactions_status_index')) $table->dropIndex('wallet_transactions_status_index');
                if (Schema::hasIndex('wallet_transactions', 'wallet_transactions_reference_type_index')) $table->dropIndex('wallet_transactions_reference_type_index');
            });
        }

        if (Schema::hasTable('job_applications')) {
            Schema::table('job_applications', function (Blueprint $table) {
                if (Schema::hasIndex('job_applications', 'job_applications_user_status_index')) $table->dropIndex('job_applications_user_status_index');
                if (Schema::hasIndex('job_applications', 'job_applications_job_id_index')) $table->dropIndex('job_applications_job_id_index');
            });
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (Schema::hasIndex('notifications', 'notifications_notifiable_read_index')) $table->dropIndex('notifications_notifiable_read_index');
            });
        }
    }
};
