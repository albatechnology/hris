<?php

use App\Models\PatrolTask;
use App\Models\Schedule;
use App\Models\Shift;
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
        Schema::create('user_patrol_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(PatrolTask::class);
            $table->foreignIdFor(Schedule::class);
            $table->foreignIdFor(Shift::class);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_patrol_tasks');
    }
};
