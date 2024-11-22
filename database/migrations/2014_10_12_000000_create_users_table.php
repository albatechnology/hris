<?php

use App\Enums\Gender;
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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->nullable()->constrained();
            $table->foreignId('company_id')->nullable()->constrained();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->foreignId('live_attendance_id')->nullable()->constrained();
            $table->foreignId('overtime_id')->nullable();
            // $table->foreignId('approval_id')->nullable();
            // $table->nestedSet(); // parent_id as manager_id
            // $table->foreignId('manager_id')->nullableid()->constrained('users');
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('work_email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->string('fcm_token')->nullable();
            $table->string('type');
            $table->string('nik', 20)->nullable();
            $table->string('phone', 18)->nullable();
            $table->string('gender', 6)->default(Gender::MALE->value);
            $table->date('join_date')->nullable();
            $table->date('sign_date')->nullable();
            $table->date('end_contract_date')->nullable();
            $table->date('resign_date')->nullable();
            // $table->unsignedSmallInteger('total_timeoff')->default(0);
            // $table->unsignedSmallInteger('total_remaining_timeoff')->default(0);
            $table->timestamps();

            // created/updated/deleted info
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
