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
        Schema::create('run_payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('code')->unique();
            $table->string('period');
            $table->date('cut_off_start_date')->nullable();
            $table->date('cut_off_end_date')->nullable();
            $table->date('payment_schedule');
            $table->string('status', 50); // Enum from RunPayrollStatus::class
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('run_payrolls');
    }
};
