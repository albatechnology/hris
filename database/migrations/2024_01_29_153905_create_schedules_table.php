<?php

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
            $table->timestamps();

            // softDeletes must implement deleted_by
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
