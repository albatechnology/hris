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
        Schema::table('branches', function (Blueprint $table) {
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('bank_account_holder')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('bank_branch')->nullable();
        });

        Schema::table('user_payroll_infos', function (Blueprint $table) {
            $table->string('payroll_branch_id')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'bank_account_no',
                'bank_account_holder',
                'bank_code',
                'bank_branch',
            ]);
        });

        Schema::table('user_payroll_infos', function (Blueprint $table) {
            $table->dropColumn('payroll_branch_id');
        });
    }
};
