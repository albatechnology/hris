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
            $table->char('cut_off_attendance_start_date', 2)->default("01")->after('company_id');
            $table->char('cut_off_attendance_end_date', 2)->default("28")->after('cut_off_attendance_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_settings', function (Blueprint $table) {
            $table->dropColumn(['cut_off_attendance_start_date', 'cut_off_attendance_end_date']);
        });
    }
};
