<?php

use App\Models\ClientLocation;
use App\Models\Patrol;
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
        Schema::create('patrol_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Patrol::class)->constrained();
            $table->foreignIdFor(ClientLocation::class)->constrained();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patrol_locations');
    }
};
