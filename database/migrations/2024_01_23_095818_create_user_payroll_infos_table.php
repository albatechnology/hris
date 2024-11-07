<?php

use App\Enums\CurrencyCode;
use App\Enums\OvertimeSetting;
use App\Enums\PaymentSchedule;
use App\Enums\PtkpStatus;
use App\Enums\SalaryType;
use App\Enums\TaxMethod;
use App\Enums\TaxSalary;
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
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('basic_salary')->default(0);
            $table->string('salary_type')->default(SalaryType::MONTHLY);
            $table->string('payment_schedule')->default(PaymentSchedule::DEFAULT);
            $table->string('prorate_setting')->nullable();
            $table->string('overtime_setting')->default(OvertimeSetting::ELIGIBLE);
            $table->string('cost_center_category')->nullable();
            $table->string('currency')->default(CurrencyCode::IDR);
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('bank_account_holder')->nullable();
            $table->string('secondary_bank_name')->nullable();
            $table->string('secondary_bank_account_no')->nullable();
            $table->string('secondary_bank_account_holder')->nullable();
            $table->string('npwp')->nullable();
            $table->string('ptkp_status')->default(PtkpStatus::TK_0);
            $table->string('tax_method')->default(TaxMethod::GROSS);
            $table->string('tax_salary')->default(TaxSalary::TAXABLE);
            $table->date('taxable_date')->nullable();
            $table->string('employee_tax_status')->nullable();
            $table->integer('beginning_netto')->nullable();
            $table->integer('pph21_paid')->nullable();
            // $table->string('bpjs_ketenagakerjaan_no')->nullable();
            // $table->string('npp_bpjs_ketenagakerjaan')->default(NppBpjsKetenagakerjaan::DEFAULT);
            // $table->date('bpjs_ketenagakerjaan_date')->nullable();
            // $table->string('bpjs_kesehatan_no')->nullable();
            // $table->string('bpjs_kesehatan_family_no')->nullable();
            // $table->date('bpjs_kesehatan_date')->nullable();
            // $table->string('bpjs_kesehatan_cost')->default(BpjsKesehatanCost::DEFAULT);
            // $table->string('jht_cost')->default(JhtCost::DEFAULT);
            // $table->string('jaminan_pensiun_cost')->default(JaminanPensiunCost::DEFAULT);
            // $table->date('jaminan_pensiun_date')->nullable();
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
