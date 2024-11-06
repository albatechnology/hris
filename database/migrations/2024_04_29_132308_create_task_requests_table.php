<?php

use App\Enums\ApprovalStatus;
use App\Models\TaskHour;
use App\Models\User;
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
        Schema::create('task_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(TaskHour::class)->constrained();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->text('note')->nullable();
            // $table->string('approval_status')->default(ApprovalStatus::PENDING->value);
            // $table->foreignId('approved_by')->nullable()->constrained('users');
            // $table->datetime('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_requests');
    }
};
