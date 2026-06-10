<?php

use App\Models\JobLevel;
use App\Models\JobPosition;
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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignIdFor(JobPosition::class)->after('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(JobLevel::class)->after('job_position_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(JobPosition::class, 'job_position_id');
            $table->dropConstrainedForeignIdFor(JobLevel::class, 'job_level_id');
        });
    }
};
