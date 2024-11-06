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
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('no_ktp', 20)->nullable();
            $table->string('kk_no')->nullable();
            $table->text('postal_code', 20)->nullable();
            $table->text('address', 20)->nullable();
            $table->text('address_ktp', 20)->nullable();
            $table->string('job_position')->nullable();
            $table->string('job_level')->nullable();
            $table->string('employment_status')->nullable();
            $table->string('passport_no')->nullable();
            $table->date('passport_expired')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('birthdate')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('blood_type')->nullable();
            $table->string('rhesus')->nullable();
            $table->string('religion')->nullable();
            $table->string('batik_size')->nullable();
            $table->string('tshirt_size')->nullable();
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
