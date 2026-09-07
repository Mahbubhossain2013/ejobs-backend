<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add fields to badges table
        Schema::table('badges', function (Blueprint $table) {
            $table->string('role')->default('both')->after('badge_key'); // candidate, employer, both
            $table->string('icon_type')->default('class')->after('icon'); // class (Lucide/Heroicon), image (Uploaded file)
            $table->string('icon_path')->nullable()->after('icon_type'); // Uploaded image path
            $table->json('rules')->nullable()->after('is_active'); // Conditions like {"completed_jobs": 5, "rating": 4.5}
            $table->boolean('is_automatic')->default(false)->after('rules'); // Automatically assigned by AI / system
        });

        // 2. Add fields to user_profiles table (Candidates)
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->integer('trust_score')->default(100)->after('address');
            $table->decimal('rating', 3, 2)->default(5.00)->after('trust_score');
            $table->integer('completed_jobs_count')->default(0)->after('rating');
            $table->decimal('total_earnings', 12, 2)->default(0.00)->after('completed_jobs_count');
            $table->string('reputation_status')->default('good')->after('total_earnings'); // excellent, good, fair, suspicious, under_review
            $table->boolean('is_featured')->default(false)->after('reputation_status');
            $table->integer('profile_completion_percentage')->default(0)->after('is_featured');
        });

        // 3. Add fields to companies table (Employers)
        Schema::table('companies', function (Blueprint $table) {
            $table->integer('trust_score')->default(100)->after('is_featured');
            $table->decimal('rating', 3, 2)->default(5.00)->after('trust_score');
            $table->integer('completed_jobs_count')->default(0)->after('rating');
            $table->decimal('total_spend', 12, 2)->default(0.00)->after('completed_jobs_count');
            $table->string('reputation_status')->default('good')->after('total_spend'); // excellent, good, fair, suspicious, under_review
            $table->integer('profile_completion_percentage')->default(0)->after('reputation_status');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'trust_score',
                'rating',
                'completed_jobs_count',
                'total_spend',
                'reputation_status',
                'profile_completion_percentage'
            ]);
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'trust_score',
                'rating',
                'completed_jobs_count',
                'total_earnings',
                'reputation_status',
                'is_featured',
                'profile_completion_percentage'
            ]);
        });

        Schema::table('badges', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'icon_type',
                'icon_path',
                'rules',
                'is_automatic'
            ]);
        });
    }
};
