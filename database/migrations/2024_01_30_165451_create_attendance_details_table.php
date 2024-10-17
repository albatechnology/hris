<?php

use App\Enums\ApprovalStatus;
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
        Schema::create('attendance_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->boolean('is_clock_in')->default(1);
            $table->timestamp('time');
            $table->string('type', 20);
            $table->string('lat')->nullable();
            $table->string('lng')->nullable();
            // $table->string('approval_status')->default(ApprovalStatus::PENDING);
            // $table->dateTime('approved_at')->nullable();
            // $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_details');
    }
};
