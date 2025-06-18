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
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->char('cut_off_attendance_start_date', 2)->default("01");
            $table->char('cut_off_attendance_end_date', 2)->default("28");
            $table->char('cut_off_date', 2)->default("20");
            $table->boolean('is_attendance_pay_last_month')->default(false);
            // $table->boolean('is_default_cutoff')->default(0);
            // $table->char('cut_off_attendance_start_date', 2)->nullable();
            // $table->char('cut_off_attendance_end_date', 2)->nullable();
            // $table->boolean('is_attendance_pay_last_month')->default(0);
            // $table->integer('cutoff_payroll_end_date')->nullable();
            // $table->integer('cutoff_payroll_end_date')->nullable();
            $table->string('default_employee_tax_setting')->nullable(); // DefaultEmployeeTaxSetting::class
            $table->string('default_employee_salary_tax_setting')->nullable(); // DefaultEmployeeTaxSetting::class
            $table->string('default_oas_setting')->nullable(); // DefaultEmployeeTaxSetting::class
            $table->string('prorate_setting')->nullable();
            $table->boolean('is_count_national_holiday_as_working_day')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_settings');
    }
};
