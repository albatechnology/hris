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
            $table->integer('compensation_rate_per_day')->nullable();
            $table->string('rate_type')->comment('Enum from RateType::class valid value is (amount, salary, allowances), if the value is allowances then this table has many relation to overtime_allowances table');
            $table->unsignedFloat('rate_amount')->default(0);
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
