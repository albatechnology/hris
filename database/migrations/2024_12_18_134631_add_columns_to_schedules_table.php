<?php

use App\Enums\ApprovalStatus;
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
        Schema::table('schedules', function (Blueprint $table) {
            $table->text('description')->nullable()->after('is_generate_timeoff');
            $table->boolean('is_need_approval')->default(0)->after('description');
            $table->string('approval_status')->default(ApprovalStatus::APPROVED)->after('is_need_approval');
            $table->unsignedInteger('approved_by')->nullable()->after('approval_status');
            $table->datetime('approved_at')->nullable()->after('approved_by');

            $table->unsignedInteger('created_by')->nullable()->after('updated_at');
            $table->unsignedInteger('updated_by')->nullable()->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            //
        });
    }
};
