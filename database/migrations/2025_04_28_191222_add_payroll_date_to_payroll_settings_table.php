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
        Schema::table('payroll_settings', function (Blueprint $table) {
            $table->char('payroll_start_date', 2)->default("01")->after('cut_off_attendance_end_date');
            $table->char('payroll_end_date', 2)->default("31")->after('payroll_start_date');
        });

        Schema::table('run_payrolls', function (Blueprint $table) {
            $table->date('payroll_start_date')->nullable()->after('cut_off_end_date');
            $table->date('payroll_end_date')->nullable()->after('payroll_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_settings', function (Blueprint $table) {
            $table->dropColumn(['payroll_start_date', 'payroll_end_date']);
        });

        Schema::table('run_payrolls', function (Blueprint $table) {
            $table->dropColumn(['payroll_start_date', 'payroll_end_date']);
        });
    }
};
