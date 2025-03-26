<?php

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
        Schema::create('patrol_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Patrol::class)->constrained();
            $table->time('start_hour');
            $table->time('end_hour');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->integer('created_by')->unsigned()->nullable();
            $table->integer('updated_by')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patrol_hours');
    }
};
