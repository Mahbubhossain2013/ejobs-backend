<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->index('offer_status', 'contracts_offer_status_index');
        });

        Schema::table('escrows', function (Blueprint $table) {
            $table->unsignedBigInteger('contract_id')->nullable()->after('job_id');
            $table->foreign('contract_id')->references('id')->on('contracts')->nullOnDelete();
            $table->index('contract_id', 'escrows_contract_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('escrows', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropColumn('contract_id');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['offer_status']);
            $table->dropForeign(['category_id']);
        });
    }
};
