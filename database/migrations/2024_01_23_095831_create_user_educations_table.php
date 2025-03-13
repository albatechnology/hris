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
        Schema::create('user_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('type');
            $table->string('level')->nullable();
            $table->string('name')->nullable();
            $table->string('institution_name');
            $table->string('majors')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('expired_date')->nullable();
            $table->string('score')->nullable();
            $table->double('fee', 12, 2)->unsigned()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_educations');
    }
};
