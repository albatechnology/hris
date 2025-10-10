<?php

use App\Enums\PanicStatus;
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
        Schema::create('panics', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained();
            $table->string('lat');
            $table->string('lng');
            $table->string('status')->default(PanicStatus::PANIC->value);
            $table->text('description')->nullable();
            $table->foreignId('solved_by_id')->nullable()->constrained('users');
            $table->timestamp('solved_at')->nullable();
            $table->string('solved_lat')->nullable();
            $table->string('solved_lng')->nullable();
            $table->text('solved_description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('panics');
    }
};
