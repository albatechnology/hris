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
            $table->string('request_type');
            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('delegate_to')->nullable();
            $table->timestamps();
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
