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
        Schema::table('timeoff_policies', function (Blueprint $table) {
            $table->date('expired_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timeoff_policies', function (Blueprint $table) {
            $table->dropColumn('expired_date');

            if (Schema::hasColumn('timeoff_policies', 'expired_date_day')) {
                $table->dropColumn('expired_date_day');
            }
            if (Schema::hasColumn('timeoff_policies', 'expired_date_month')) {
                $table->dropColumn('expired_date_month');
            }
            if (Schema::hasColumn('timeoff_policies', 'expired_date_year')) {
                $table->dropColumn('expired_date_year');
            }
        });
    }
};
