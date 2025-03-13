<?php

use App\Enums\TaxMethod;
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
        Schema::create('run_payroll_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_payroll_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->double('basic_salary', 13, 2)->unsigned()->default(0);
            $table->double('gross_salary', 13, 2)->unsigned()->default(0);
            $table->double('allowance', 13, 2)->unsigned()->default(0);
            $table->double('additional_earning', 13, 2)->unsigned()->default(0);
            $table->double('deduction', 13, 2)->unsigned()->default(0);
            $table->double('benefit', 13, 2)->unsigned()->default(0);
            $table->double('tax', 13, 2)->unsigned()->default(0);
            $table->json('payroll_info')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('run_payroll_users');
    }
};
