<?php

use App\Enums\FormulaAmountType;
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
        Schema::create('formulas', function (Blueprint $table) {
            $table->id();
            $table->morphs('formulaable');
            $table->foreignId('parent_id')->nullable()->constrained('formulas');
            $table->string('component'); // FormulaComponentEnum::class
            $table->string('amount_type')->default(FormulaAmountType::NUMBER);
            $table->string('amount')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formulas');
    }
};
