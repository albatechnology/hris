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
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('no_ktp', 20);
            $table->string('kk_no')->nullable();
            $table->string('job_position');
            $table->string('job_level');
            $table->string('employment_status');
            $table->date('join_date');
            $table->date('sign_date');
            $table->string('passport_no')->nullable();
            $table->date('passport_expired')->nullable();
            $table->text('address', 20)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};
