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
        Schema::create('user_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->index();
            $table->json('from')->nullable();
            $table->string('type', 20);
            $table->date('effective_date');
            $table->string('employment_status')->nullable();
            $table->unsignedInteger('approval_id')->nullable();
            $table->unsignedInteger('parent_id')->nullable();
            $table->text('reason')->nullable();
            $table->boolean('is_notify_manager')->default(0);
            $table->boolean('is_notify_user')->default(0);
            $table->string('approval_status')->default(ApprovalStatus::PENDING);
            $table->dateTime('approved_at')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_transfers');
    }
};
