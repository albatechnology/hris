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
        Schema::table('attendances', function (Blueprint $table) {
            // created/updated/deleted info
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->boolean('is_show_in_request')->default(0);
            $table->boolean('is_show_in_request_for_all')->default(0);
            $table->text('show_in_request_branch_ids')->nullable();
            $table->text('show_in_request_department_ids')->nullable();
            $table->text('show_in_request_position_ids')->nullable();
        });

        Schema::table('request_shifts', function (Blueprint $table) {
            $table->boolean('is_for_replace')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['is_show_in_request', 'is_show_in_request_for_all', 'show_in_request_branch_ids', 'show_in_request_department_ids', 'show_in_request_position_ids']);
        });

        Schema::table('request_shifts', function (Blueprint $table) {
            $table->dropColumn('is_for_replace');
        });
    }
};
