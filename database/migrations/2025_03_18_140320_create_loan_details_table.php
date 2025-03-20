<?php

use App\Models\Loan;
use App\Models\RunPayrollUser;
use App\Models\UserContact;
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
        Schema::create('loan_details', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(RunPayrollUser::class)->nullable();
            $table->foreignIdFor(Loan::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(UserContact::class)->nullable();
            $table->char('payment_period_year', 4);
            $table->char('payment_period_month', 2);
            $table->double('basic_payment', 13, 2)->unsigned()->default(0);
            $table->float('interest')->unsigned()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_details');
    }
};
