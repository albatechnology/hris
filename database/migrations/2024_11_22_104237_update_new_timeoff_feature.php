<?php

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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('timeoff_policies');

        Schema::create('timeoff_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('type', 50);
            $table->string('name');
            $table->string('code', 20);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('default_quota')->nullable()->comment('misal cuti SWC dapet quota 2 hari per tahun');
            // $table->unsignedSmallInteger('max_used')->nullable();
            $table->date('effective_date')->nullable();
            // $table->char('min_join_date_day', 2)->nullable();
            // $table->char('min_join_date_month', 2)->nullable();
            $table->char('expired_date_day', 2)->nullable();
            $table->char('expired_date_month', 2)->nullable();
            $table->char('expired_date_year', 4)->nullable();
            $table->unsignedSmallInteger('max_consecutively_day')->nullable();
            $table->boolean('is_allow_halfday')->default(0);
            // $table->boolean('is_for_all_user')->default(1)->comment('jika false, maka harus isi list user nya di table user_timeoff_policies');
            $table->unsignedSmallInteger('block_leave_take_days')->nullable()->comment('jumlah hari yang wajib diambil');
            // $table->boolean('is_enable_block_leave')->default(0);
            // $table->unsignedSmallInteger('block_leave_min_working_month')->default(0)->comment('minimal masa kerja(bulan) untuk dapat me-request. 0 berarti semua user dapat me-request');
            // $table->unsignedSmallInteger('max_used')->nullable()->comment('maksimal policy dapat digunakan');
            $table->timestamps();

            // created/updated/deleted info
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['total_timeoff', 'total_remaining_timeoff', 'approval_id']);

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
        //
    }
};
