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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('name');
            $table->time('clock_in');
            $table->time('clock_out')->nullable();
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->char('color', 7)->default('#ffffff');
            $table->text('description')->nullable();
            $table->boolean('is_enable_validation')->default(0);
            $table->unsignedSmallInteger('clock_in_min_before')->default(0);
            $table->unsignedSmallInteger('clock_out_max_after')->default(0);
            $table->boolean('is_enable_grace_period')->default(0);
            $table->unsignedSmallInteger('clock_in_dispensation')->default(0);
            $table->unsignedSmallInteger('clock_out_dispensation')->default(0);
            $table->boolean('is_enable_auto_overtime')->default(0);
            $table->time('overtime_before')->nullable();
            $table->time('overtime_after')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};