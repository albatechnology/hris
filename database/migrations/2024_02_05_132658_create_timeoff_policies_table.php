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
        Schema::create('timeoff_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('type', 20);
            $table->string('name');
            $table->string('code', 20);
            $table->text('description')->nullable();
            $table->date('effective_date');
            $table->date('expired_date')->nullable();
            $table->boolean('is_for_all_user')->default(1)->comment('jika false, maka harus isi list user nya di table user_timeoff_policies');
            $table->boolean('is_enable_block_leave')->default(0);
            $table->boolean('is_unlimited_day')->default(0)->comment('jika false, maka max day akan ambil dari kolom max_consecutively_day table timeoff_regulations');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timeoff_policies');
    }
};
