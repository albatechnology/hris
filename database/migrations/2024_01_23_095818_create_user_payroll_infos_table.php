<?php

use App\Enums\PaymentSchedule;
use App\Enums\SalaryType;
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
        Schema::create('user_payroll_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained();
            $table->string('basic_salary')->nullable();
            $table->string('salary_type')->default(SalaryType::MONTHLY);
            $table->string('payment_schedule')->default(PaymentSchedule::DEFAULT);
            $table->string('prorate_setting')->nullable();
            $table->string('overtime_setting')->default();
            $table->string('cost_center_category')->nullable();
            $table->string('currency')->default();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('bank_account_holder')->nullable();
            $table->string('npwp')->nullable();
            $table->string('ptkp_status')->default();
            $table->string('tax_method')->default();
            $table->string('tax_salary')->default();
            $table->date('taxable_date')->nullable();
            $table->string('employee_tax_status')->nullable();
            $table->integer('beginning_netto')->nullable();
            $table->integer('pph21_paid')->nullable();
            $table->string('bpjs_ketenagakerjaan_no')->nullable();
            $table->string('npp_bpjs_ketenagakerjaan')->default();
            $table->date('bpjs_ketenagakerjaan_date')->nullable();
            $table->string('bpjs_kesehatan_no')->nullable();
            $table->string('bpjs_kesehatan_family_no')->nullable();
            $table->date('bpjs_kesehatan_date')->nullable();
            $table->string('bpjs_kesehatan_cost')->default();
            $table->string('jht_cost')->default();
            $table->string('jaminan_pensiun_cost')->default();
            $table->date('jaminan_pensiun_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_payroll_infos');
    }
};
