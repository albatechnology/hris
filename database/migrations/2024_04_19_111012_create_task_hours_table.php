<?php

use App\Models\Task;
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
        Schema::create('task_hours', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('hours')->nullable();
            $table->foreignIdFor(Task::class)->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('min_working_hour')->default(0);
            $table->unsignedSmallInteger('max_working_hour')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_hours');
    }
};
