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
        Schema::table('run_payroll_users', function (Blueprint $table) {
            $table->dropForeign(['run_payroll_id']);
            $table->foreign('run_payroll_id')
                ->references('id')
                ->on('run_payrolls')
                ->cascadeOnDelete();
        });

        Schema::table('run_payroll_user_components', function (Blueprint $table) {
            $table->dropForeign(['run_payroll_user_id']);
            $table->foreign('run_payroll_user_id')
                ->references('id')
                ->on('run_payroll_users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('run_payroll_user_components', function (Blueprint $table) {
            $table->dropForeign(['run_payroll_user_id']);
            $table->foreign('run_payroll_user_id')
                ->references('id')
                ->on('run_payroll_users');
        });

        Schema::table('run_payroll_users', function (Blueprint $table) {
            $table->dropForeign(['run_payroll_id']);
            $table->foreign('run_payroll_id')
                ->references('id')
                ->on('run_payrolls');
        });
    }
};
