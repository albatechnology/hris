<?php

use App\Enums\ApprovalStatus;
use App\Enums\ScheduleType;
use App\Models\Company;
use App\Models\User;
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
        Schema::create('request_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained();
            $table->foreignIdFor(User::class)->constrained();
            $table->string('type')->default(ScheduleType::ATTENDANCE->value);
            $table->string('name');
            $table->date('effective_date');
            $table->boolean('is_overide_national_holiday')->default(0);
            $table->boolean('is_overide_company_holiday')->default(0);
            $table->boolean('is_include_late_in')->default(0);
            $table->boolean('is_include_early_out')->default(0);
            $table->boolean('is_flexible')->default(0);
            $table->boolean('is_generate_timeoff')->default(0);
            
            $table->text('description')->nullable();
            $table->string('approval_status')->default(ApprovalStatus::PENDING);
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->datetime('approved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_schedules');
    }
};
