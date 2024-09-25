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
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('name');
            $table->string('work_email')->nullable()->after('email');
        });

        Schema::table('user_details', function (Blueprint $table) {
            $table->string('rhesus')->nullable()->after('blood_type');
        });

        Schema::table('user_payroll_infos', function (Blueprint $table) {
            $table->string('secondary_bank_name')->nullable()->after('bank_account_holder');
            $table->string('secondary_bank_account_no')->nullable()->after('secondary_bank_name');
            $table->string('secondary_bank_account_holder')->nullable()->after('secondary_bank_account_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_name');
            $table->dropColumn('work_email');
        });

        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn('rhesus');
        });

        Schema::table('user_payroll_infos', function (Blueprint $table) {
            $table->dropColumn('secondary_bank_name');
            $table->dropColumn('secondary_bank_account_no');
            $table->dropColumn('secondary_bank_account_holder');
        });
    }
};
