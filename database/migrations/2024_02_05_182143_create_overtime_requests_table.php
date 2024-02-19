<?php

use App\Enums\OvertimeStatus;
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
            $table->date('date');
            $table->foreignId('shift_id')->constrained();
            $table->foreignId('overtime_id')->constrained();
            $table->time('start_at');
            $table->time('end_at');
            $table->text('note')->nullable();
            $table->string('status')->nullable()->default(OvertimeStatus::PENDING)->comment('Enum from OvertimeStatus::class. Allowed value is (pending, approved, rejected).');
            $table->foreignId('status_updated_by')->nullable()->constrained('users');
            $table->datetime('status_updated_at')->nullable();
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
