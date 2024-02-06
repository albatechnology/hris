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
        Schema::create('overtime_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_id')->constrained();
            // $table->foreignId('payroll_category_id')->constrained(); currently commented because payroll_categories is not created yet
            $table->foreignId('payroll_category_id')->nullable();
            $table->unsignedDouble('amount', 13, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_allowances');
    }
};
