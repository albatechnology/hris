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
        Schema::create('timeoff_regulation_months', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timeoff_period_regulation_id')->constrained();
            $table->string('month', 2);
            $table->unsignedFloat('amount')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timeoff_regulation_months');
    }
};
