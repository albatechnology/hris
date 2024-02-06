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
        Schema::create('overtime_multipliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_id')->constrained();
            $table->boolean('is_weekday')->default(0);
            $table->integer('start_hour');
            $table->integer('end_hour');
            $table->integer('multiply')->comment('Used to multiply overtime payroll');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_multipliers');
    }
};
