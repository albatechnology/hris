<?php

namespace Database\Seeders;

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
            "type" => "allowance",
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
            "type" => "allowance",
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
    }
}
