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
        Schema::table('ai_configs', function (Blueprint $table) {
            $table->string('purpose')->default('default'); // 'default', 'support', 'cv_builder', 'career_guide'
            $table->text('system_instruction')->nullable(); // This is the "Knowledgebase/Behavior"
            $table->dropColumn('is_active'); // We'll manage activity via purpose
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
