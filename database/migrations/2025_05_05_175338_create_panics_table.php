<?php

use App\Enums\PanicStatus;
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
        Schema::create('panics', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Client::class)->nullable()->constrained();
            $table->foreignIdFor(User::class)->constrained();
            $table->string('lat');
            $table->string('lng');
            $table->string('status')->default(PanicStatus::PANIC->value);
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
