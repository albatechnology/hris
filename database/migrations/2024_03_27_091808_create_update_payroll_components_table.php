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
        // this table must have at least one relationship to update_payroll_component_details
        Schema::create('update_payroll_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('transaction_id')->unique();
            $table->string('type')->comment('Enum from UpdatePayrollComponentType::class. Allowed value is (adjustment, expired).');
            $table->text('description')->nullable();
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->date('backpay_date')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('update_payroll_components');
    }
};
