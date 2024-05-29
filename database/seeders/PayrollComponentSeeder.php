<?php

namespace Database\Seeders;

use App\Enums\PayrollComponentType;
use App\Models\PayrollComponent;
use App\Services\FormulaService;
use Illuminate\Database\Seeder;

class PayrollComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $payrollComponent = PayrollComponent::create([
            "company_id" => "1",
            "name" => "daily attendance",
            "type" => PayrollComponentType::ALLOWANCE,
            "category" => "default",
            "setting" => "default",
            "is_taxable" => false,
            "period_type" => "daily",
            "is_monthly_prorate" => false,
            "is_daily_default" => false,
            "daily_maximum_amount_type" => "not_use",
            "daily_maximum_amount" => "0",
            "is_one_time_bonus" => false,
            "is_include_backpay" => false,
        ]);
        $formulas = [
            [
                "component" => "daily_attendance",
                // "value" => "present,alpha",
                "value" => "present",
                "amount" => "1000"
            ]
        ];

        FormulaService::sync($payrollComponent, $formulas);

        $payrollComponent = PayrollComponent::create([
            "company_id" => "1",
            "name" => "shift",
            "type" => PayrollComponentType::ALLOWANCE,
            "category" => "default",
            "setting" => "default",
            "is_taxable" => false,
            "period_type" => "daily",
            "is_monthly_prorate" => false,
            "is_daily_default" => false,
            "daily_maximum_amount_type" => "not_use",
            "daily_maximum_amount" => "0",
            "is_one_time_bonus" => true,
            "is_include_backpay" => false,
        ]);
        $formulas = [
            [
                "component" => "shift",
                "value" => "2,3,4",
                "amount" => "5000"
            ]
        ];

        FormulaService::sync($payrollComponent, $formulas);

        $payrollComponent = PayrollComponent::create([
            "company_id" => "1",
            "name" => "makan siang gratis",
            "type" => PayrollComponentType::ALLOWANCE,
            "category" => "default",
            "setting" => "default",
            "amount" => 5000,
            "is_taxable" => false,
            "period_type" => "daily",
            "is_monthly_prorate" => false,
            "is_daily_default" => false,
            "daily_maximum_amount_type" => "not_use",
            "daily_maximum_amount" => "0",
            "is_one_time_bonus" => true,
            "is_include_backpay" => false,
        ]);

        $payrollComponent = PayrollComponent::create([
            "company_id" => "1",
            "name" => "Potongan 1000",
            "type" => PayrollComponentType::DEDUCTION,
            "category" => "default",
            "setting" => "default",
            "amount" => 1000,
            "is_taxable" => false,
            "period_type" => "daily",
            "is_monthly_prorate" => false,
            "is_daily_default" => false,
            "daily_maximum_amount_type" => "not_use",
            "daily_maximum_amount" => "0",
            "is_one_time_bonus" => true,
            "is_include_backpay" => false,
        ]);

        $payrollComponent = PayrollComponent::create([
            "company_id" => "1",
            "name" => "Benefit 2000",
            "type" => PayrollComponentType::BENEFIT,
            "category" => "default",
            "setting" => "default",
            "amount" => 2000,
            "is_taxable" => false,
            "period_type" => "daily",
            "is_monthly_prorate" => false,
            "is_daily_default" => false,
            "daily_maximum_amount_type" => "not_use",
            "daily_maximum_amount" => "0",
            "is_one_time_bonus" => true,
            "is_include_backpay" => false,
        ]);
    }
}
