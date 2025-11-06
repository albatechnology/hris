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
        Schema::table('overtimes', function (Blueprint $table) {
            $table->integer("min_auto_overtime_minute")->nullable();
        });

        Schema::table('user_overtimes', function (Blueprint $table) {
            $table->boolean("is_default")->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropColumn("min_auto_overtime_minute");
        });

        Schema::table('user_overtimes', function (Blueprint $table) {
            $table->dropColumn("is_default");
        });
    }
};
