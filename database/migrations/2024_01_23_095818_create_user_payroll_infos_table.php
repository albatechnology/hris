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
        Schema::create('user_payroll_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('bpjs_ketenagakerjaan_no');
            $table->string('bpjs_kesehatan_no');
            $table->string('npwp');
            $table->string('bank_name');
            $table->string('bank_account_no');
            $table->string('bank_account_holder');
            $table->string('ptkp_status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_payroll_infos');
    }
};
