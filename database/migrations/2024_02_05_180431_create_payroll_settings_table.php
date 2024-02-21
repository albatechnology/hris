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
            // $table->foreignId('company_id')->constrained();
            // $table->integer('payroll_schedule')->nullable();
            // $table->boolean('is_default_cutoff')->default(0);
            // $table->integer('cutoff_attendance_start_date')->nullable();
            // $table->integer('cutoff_attendance_end_date')->nullable();
            // $table->boolean('is_attendance_pay_last_month')->default(0);
            // $table->integer('cutoff_payroll_end_date')->nullable();
            // $table->integer('cutoff_payroll_end_date')->nullable();
            // $table->string('default_employee_tax_setting')->nullable(); // DefaultEmployeeTaxSetting::class
            // $table->string('default_employee_salary_tax_setting')->nullable(); // DefaultEmployeeTaxSetting::class
            // $table->string('default_oas_setting')->nullable(); // DefaultEmployeeTaxSetting::class
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
