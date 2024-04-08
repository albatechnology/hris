<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->date('date')->useCurrent();
            // $table->boolean('is_clock_in')->default(1);
            // $table->timestamp('time');
            // $table->string('type', 20);
            // $table->string('lat')->nullable();
            // $table->string('lng')->nullable();
            // $table->string('approval_status')->default(ApprovalStatus::PENDING);
            // $table->foreignId('approved_by')->nullable()->constrained('users');
            // $table->text('note')->nullable();
            $table->timestamps();

            // softDeletes must implement deleted_by
            $table->unsignedInteger('deleted_by')->nullable();
            $table->softDeletes();
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
