<?php

use App\Models\TimeoffPeriodRegulation;
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
        Schema::create('timeoff_regulation_months', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->nullable()->cascadeOnDelete();
            $table->foreignIdFor(TimeoffPeriodRegulation::class)->nullable()->constrained()->cascadeOnDelete();
            $table->string('month', 2);
            $table->unsignedFloat('amount')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timeoff_regulation_months');
    }
};
