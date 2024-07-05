<?php

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
        Schema::create('user_transfer_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(UserTransfer::class)->constrained()->cascadeOnDelete();
            $table->unsignedInteger('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_transfer_branches');
    }
};
