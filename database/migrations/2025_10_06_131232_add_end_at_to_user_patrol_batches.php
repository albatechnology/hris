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
        Schema::table('user_patrol_batches', function (Blueprint $table) {
            $table->timestamp('end_at')->nullable()->after('datetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user-patrol-batches', function (Blueprint $table) {
            $table->dropColumn('end_at');
        });
    }
};
