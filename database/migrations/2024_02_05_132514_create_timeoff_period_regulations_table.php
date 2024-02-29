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
        Schema::create('timeoff_period_regulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timeoff_regulation_id')->constrained();
            $table->unsignedSmallInteger('min_working_month')->default(0);
            $table->unsignedSmallInteger('max_working_month');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timeoff_period_regulations');
    }
};
