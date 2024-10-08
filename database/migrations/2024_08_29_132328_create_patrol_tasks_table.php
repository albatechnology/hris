<?php

use App\Enums\PatrolTaskStatus;
use App\Models\PatrolLocation;
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
        Schema::create('patrol_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PatrolLocation::class)->constrained();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default(PatrolTaskStatus::PENDING->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patrol_tasks');
    }
};
