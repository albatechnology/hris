<?php

use App\Models\RunThr;
use App\Models\User;
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
        Schema::create('run_thr_users', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(RunThr::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained();
            $table->unsignedDouble('basic_salary', 13, 2)->default(0);
            $table->unsignedDouble('gross_salary', 13, 2)->default(0);
            $table->unsignedDouble('allowance', 13, 2)->default(0);
            $table->unsignedDouble('additional_earning', 13, 2)->default(0);
            $table->unsignedDouble('deduction', 13, 2)->default(0);
            $table->unsignedDouble('benefit', 13, 2)->default(0);
            $table->unsignedDouble('tax', 13, 2)->default(0);
            $table->json('payroll_info')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('run_thr_users');
    }
};
