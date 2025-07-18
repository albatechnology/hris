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
            $table->json('context')->nullable();
            $table->string('type', 20);
            $table->date('effective_date');
            $table->integer('company_id')->unsigned()->nullable();
            $table->integer('branch_id')->unsigned()->nullable();
            $table->integer('supervisor_id')->unsigned()->nullable();
            $table->integer('position_id')->unsigned()->nullable();
            $table->integer('department_id')->unsigned()->nullable();
            $table->string('employment_status')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            // created/updated/deleted info
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->softDeletes();
            // $table->unsignedInteger('approval_id')->nullable();
            // $table->unsignedInteger('parent_id')->nullable();
            // $table->boolean('is_notify_manager')->default(0);
            // $table->boolean('is_notify_user')->default(0);
            // $table->string('approval_status')->default(ApprovalStatus::PENDING);
            // $table->dateTime('approved_at')->nullable();
            // $table->unsignedInteger('approved_by')->nullable();
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
