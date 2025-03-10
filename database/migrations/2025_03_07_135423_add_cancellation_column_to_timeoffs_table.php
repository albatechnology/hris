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
            $table->boolean('is_cancelled')->default(0);
            $table->foreignId('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('timeoff_quota_histories')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timeoffs', function (Blueprint $table) {
            $table->dropColumn(['is_cancelled', 'cancelled_by', 'cancelled_at', 'timeoff_quota_histories']);
        });
    }
};
