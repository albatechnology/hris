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
        Schema::table('user_details', function (Blueprint $table) {
            $table->string('lat')->nullable()->after('tshirt_size');
            $table->string('lng')->nullable()->after('lat');
            $table->string('speed')->nullable()->after('lng');
            $table->string('battery')->nullable()->after('speed');
            $table->dateTime('detected_at')->nullable()->after('battery');
            $table->dateTime('last_absence_reminder_at')->nullable()->after('detected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn('lat');
            $table->dropColumn('lng');
            $table->dropColumn('speed');
            $table->dropColumn('battery');
            $table->dropColumn('detected_at');
            $table->dropColumn('last_absence_reminder_at');
        });
    }
};
