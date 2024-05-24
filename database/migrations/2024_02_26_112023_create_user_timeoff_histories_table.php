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
        Schema::create('user_timeoff_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->boolean('is_for_total_timeoff')->default(1);
            $table->boolean('is_increment')->default(0);
            $table->unsignedSmallInteger('value')->default(0);
            $table->text('properties')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_timeoff_histories');
    }
};
