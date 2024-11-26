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
        Schema::table('timeoffs', function (Blueprint $table) {
            $table->unsignedFloat('total_days', 8, 1)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timeoffs', function (Blueprint $table) {
            $table->dropColumn('total_days');
        });
    }
};
