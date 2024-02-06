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
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('shift_id')->constrained();
            $table->date('date');
            $table->time('start_at');
            $table->time('end_at');
            $table->text('note')->nullable();
            $table->boolean('is_approved')->nullable()->comment('null = pending, 0 = rejected, 1 = approved');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->datetime('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
    }
};
