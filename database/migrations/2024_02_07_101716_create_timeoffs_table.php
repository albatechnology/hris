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
        Schema::create('timeoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('timeoff_policy_id')->nullable()->constrained();
            $table->unsignedFloat('total_days', 8, 1)->default(0);
            $table->string('request_type');
            $table->timestamp('start_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('end_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->text('reason')->nullable();
            // $table->string('approval_status')->default(ApprovalStatus::PENDING);
            // $table->foreignId('approved_by')->nullable();
            // $table->timestamp('approved_at')->nullable();
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
        Schema::dropIfExists('timeoffs');
    }
};
