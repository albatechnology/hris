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
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('name');
            $table->boolean('is_rounding')->default(0);
            $table->double('compensation_rate_per_day', 13, 2)->unsigned()->nullable();
            $table->string('rate_type')->nullable()->comment('Enum from RateType::class. Allowed value is (amount, salary, allowances, formula). If the value is allowances then this table has many relation to overtime_allowances table, also for formula type.');
            $table->double('rate_amount', 13, 2)->unsigned()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtimes');
    }
};
