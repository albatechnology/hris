<?php

use App\Enums\ApprovalStatus;
use App\Enums\OvertimeRequestType;
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
            $table->foreignId('user_id')->constrained();
            $table->foreignId('schedule_id')->constrained();
            $table->foreignId('shift_id')->constrained();
            // $table->foreignId('overtime_id')->constrained();
            $table->string('type')->default(OvertimeRequestType::OVERTIME->value);
            // $table->date('date');
            $table->boolean('is_after_shift')->default(true);
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            // $table->time('duration');
            $table->text('note')->nullable();
            $table->string('approval_status')->default(ApprovalStatus::PENDING->value);
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
