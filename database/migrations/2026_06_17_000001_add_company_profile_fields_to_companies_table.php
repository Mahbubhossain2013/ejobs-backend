<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('founded_year')->nullable()->after('industry');
            $table->string('size')->nullable()->after('founded_year');
            $table->text('mission')->nullable()->after('size');
            $table->text('vision')->nullable()->after('mission');
            $table->text('values')->nullable()->after('vision');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'founded_year',
                'size',
                'mission',
                'vision',
                'values',
            ]);
        });
    }
};
