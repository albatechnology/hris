<?php

use App\Models\Schedule;
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
        Schema::create('request_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(Schedule::class)->constrained();
            $table->foreignId('old_shift_id')->constrained('shifts');
            $table->foreignId('new_shift_id')->constrained('shifts');
            $table->boolean('is_for_replace')->default(0);
            $table->date('date');
            $table->text('description')->nullable();
            $table->timestamps();
            // created/updated/deleted info
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_shifts');
    }
};
