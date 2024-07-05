<?php

use App\Models\Department;
use App\Models\Position;
use App\Models\UserTransfer;
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
        Schema::create('user_transfer_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(UserTransfer::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Department::class);
            $table->foreignIdFor(Position::class);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_transfer_positions');
    }
};
