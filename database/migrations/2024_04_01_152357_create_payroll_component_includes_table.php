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
        Schema::create('payroll_component_includes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_component_id')->constrained();
            $table->foreignId('included_payroll_component_id');
            $table->string('type'); // PayrollComponentIncludedType::class
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_component_includes');
    }
};
