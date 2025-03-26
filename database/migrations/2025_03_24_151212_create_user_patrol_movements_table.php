<?php

use App\Models\UserPatrolBatch;
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
        Schema::create('user_patrol_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(UserPatrolBatch::class);
            $table->timestamp('datetime');
            $table->string('lat', 50)->nullable();
            $table->string('lng', 50)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_patrol_movements');
    }
};
