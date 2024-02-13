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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('schedule_id')->constrained();
            $table->foreignId('shift_id')->constrained();
            $table->foreignId('timeoff_id')->nullable();
            $table->foreignId('event_id')->nullable();
            $table->string('code')->nullable();
            $table->boolean('is_clock_in')->default(1);
            $table->timestamp('time');
            $table->string('type', 20);
            $table->string('lat')->nullable();
            $table->string('lng')->nullable();
            $table->boolean('is_approved')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
