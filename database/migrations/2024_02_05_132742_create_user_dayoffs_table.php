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
        Schema::create('user_dayoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('timeoff_policy_id')->constrained();
            $table->float('total_amount')->unsigned()->default(0);
            $table->date('expired_at');
            $table->float('used_amount')->unsigned()->default(0);
            $table->string('approval_status')->default(ApprovalStatus::PENDING);
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->dateTime('approved_at');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_dayoffs');
    }
};
