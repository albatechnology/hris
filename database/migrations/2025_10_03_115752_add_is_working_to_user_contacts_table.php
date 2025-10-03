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
        Schema::table('user_contacts', function (Blueprint $table) {
            $table->boolean('is_working')->nullable()->after('phone');
        });

        Schema::table('user_details', function (Blueprint $table) {
            $table->date('issue_date')->nullable()->after('passport_expired');
        });

        Schema::table('user_payroll_infos', function (Blueprint $table) {
            $table->string('tabungan_haji_no')->nullable()->after('pph21_paid');
            $table->string('epf_no')->nullable()->after('pph21_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_contacts', function (Blueprint $table) {
            $table->dropColumn('is_working');
        });

        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn('issue_date');
        });

        Schema::table('user_payroll_infos', function (Blueprint $table) {
            $table->dropColumn('tabungan_haji_no');
            $table->dropColumn('tepf_no');
        });
    }
};
