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
            $table->integer('client_id')->unsigned()->nullable()->index()->after('company_id');
        });

        Schema::table('payroll_components', function (Blueprint $table) {
            $table->integer('client_id')->unsigned()->nullable()->index()->after('company_id');
        });

        Schema::table('update_payroll_components', function (Blueprint $table) {
            $table->integer('client_id')->unsigned()->nullable()->index()->after('company_id');
        });

        Schema::table('run_payrolls', function (Blueprint $table) {
            $table->integer('client_id')->unsigned()->nullable()->index()->after('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->dropColumn('client_id');
        });

        Schema::table('run_payrolls', function (Blueprint $table) {
            $table->dropColumn('client_id');
        });

        Schema::table('update_payroll_components', function (Blueprint $table) {
            $table->dropColumn('client_id');
        });

        Schema::table('payroll_settings', function (Blueprint $table) {
            $table->dropColumn('client_id');
        });
    }
};
