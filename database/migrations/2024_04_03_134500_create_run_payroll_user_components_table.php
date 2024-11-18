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
        Schema::create('run_payroll_user_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_payroll_user_id')->constrained();
            $table->foreignId('payroll_component_id')->constrained();
            $table->unsignedDouble('amount', 13, 2)->nullable();
            $table->boolean('is_editable')->default(0);
            $table->json('payroll_component');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('run_payroll_user_components');
    }
};
