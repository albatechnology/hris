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
        Schema::table('panics', function (Blueprint $table) {
            // $table->foreignId('solved_by_id')->nullable()->constrained('users');
            // $table->timestamp('solved_at')->nullable();
            // $table->string('solved_lat')->nullable();
            // $table->string('solved_lng')->nullable();
            // $table->text('solved_description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('panics', function (Blueprint $table) {
            $table->dropColumn([
                'solved_by_id',
                'solved_at',
                'solved_lat',
                'solved_lng',
                'solved_description',
            ]);
        });
    }
};
