<?php

use App\Enums\ApprovalStatus;
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
        Schema::create('advanced_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class);
            // $table->foreignId('timeoff_period_regulation_id')->constrained();
            // $table->string('month', 2);
            $table->text('data')->nullable(); // isinya array dari timeoff regulation month new dan old
            $table->float('amount')->unsigned()->default(0);
            $table->string('approval_status')->default(ApprovalStatus::PENDING);
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advanced_leave_requests');
    }
};
