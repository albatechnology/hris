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
        Schema::create('update_payroll_component_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('update_payroll_component_id')->constrained()->index('updatepayrollcomponentdetails_updatepayrollcomponentid_foreign'); // because the default update_payroll_component_details_update_payroll_component_id_foreign is too long (68/65 characters)
            $table->foreignId('user_id')->constrained();
            $table->foreignId('payroll_component_id')->constrained();
            $table->unsignedDouble('current_amount', 13, 2)->nullable();
            $table->unsignedDouble('new_amount', 13, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('update_payroll_component_details');
    }
};
