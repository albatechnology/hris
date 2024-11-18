<?php

use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentSetting;
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
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('name');
            $table->string('type'); // PayrollComponentType::class
            $table->string('category')->default(PayrollComponentCategory::DEFAULT->value); // PayrollComponentCategory::class
            $table->string('setting')->default(PayrollComponentSetting::DEFAULT->value); // PayrollComponentSetting::class
            $table->unsignedDouble('amount', 13, 2)->default(0);
            $table->boolean('is_taxable')->default(0);
            $table->string('period_type'); // PayrollComponentPeriodType::class
            $table->boolean('is_monthly_prorate')->default(0)->comment('Only for the data which had the "monthly" value of period_type column');
            $table->boolean('is_daily_default')->default(0)->comment('Only for the data which had the "daily" value of period_type column');
            $table->string('daily_maximum_amount_type')->comment('Only for the data which had the "daily" value of period_type column and is_daily_default is false (0)'); // PayrollComponentDailyMaximumAmountType::class
            $table->unsignedDouble('daily_maximum_amount', 13, 2)->default(0)->comment('Only for the data which had the "daily" value of period_type column and is_daily_default is false (0) and daily_maximum_amouont_type IN(basic_salary_percentage, custom_amount)');
            $table->boolean('is_one_time_bonus')->default(0)->comment('Only for the data which had the "one_time" value of period_type column');
            $table->boolean('is_include_backpay')->default(0)->comment('Only for the data which had the "bpjs" value of category column');;
            $table->boolean('is_default')->default(0);
            $table->boolean('is_hidden')->default(0);
            // $table->boolean('is_calculateable')->default(1);
            $table->timestamps();

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
        Schema::dropIfExists('payroll_components');
    }
};
