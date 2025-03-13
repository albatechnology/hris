<?php

use App\Models\PayrollComponent;
use App\Models\RunThrUser;
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
        Schema::create('run_thr_user_components', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(RunThrUser::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(PayrollComponent::class)->constrained();
            $table->double('amount', 13, 2)->unsigned()->nullable();
            $table->boolean('is_editable')->default(0);
            $table->json('payroll_component');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('run_thr_user_components');
    }
};
