<?php

use App\Models\Schedule;
use App\Models\UserPatrol;
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
        Schema::create('user_patrol_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(UserPatrol::class)->constrained();
            $table->foreignIdFor(Schedule::class)->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_patrol_schedules');
    }
};
