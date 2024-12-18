<?php

use App\Enums\ApprovalStatus;
use App\Enums\ScheduleType;
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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('type')->default(ScheduleType::ATTENDANCE->value);
            $table->string('name');
            $table->date('effective_date');
            $table->boolean('is_overide_national_holiday')->default(0);
            $table->boolean('is_overide_company_holiday')->default(0);
            $table->boolean('is_include_late_in')->default(0);
            $table->boolean('is_include_early_out')->default(0);
            $table->boolean('is_flexible')->default(0);
            $table->boolean('is_generate_timeoff')->default(0);

            // columns description, approval_status(pending/approved/rejected), approved_by, approved_at used to supervisor request schedule for their descendant
            $table->text('description')->nullable();
            $table->boolean('is_need_approval')->default(0);
            $table->string('approval_status')->default(ApprovalStatus::APPROVED);
            $table->unsignedInteger('approved_by')->nullable();
            $table->datetime('approved_at')->nullable();

            $table->timestamps();

            // softDeletes must implement deleted_by
            // $table->unsignedInteger('deleted_by')->nullable();
            // $table->softDeletes();

            // created/updated/deleted info
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
