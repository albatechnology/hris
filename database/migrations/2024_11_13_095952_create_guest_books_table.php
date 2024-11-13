<?php

use App\Models\Client;
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
        Schema::create('guest_books', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Client::class);
            $table->foreignIdFor(User::class);
            $table->boolean('is_check_out')->default(0);
            $table->string('name', 100);
            $table->string('address')->nullable();
            $table->string('location_destination')->nullable();
            $table->string('room')->nullable();
            $table->string('person_destination')->nullable();
            $table->string('vehicle_number', 50)->nullable();
            $table->string('description')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->timestamps();

            // softDeletes must implement deleted_by
            $table->unsignedInteger('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_books');
    }
};
