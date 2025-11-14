<?php

use App\Models\Reprimand;
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
        Schema::create('reprimand_watchers', function (Blueprint $table) {
            $table->foreignIdFor(Reprimand::class)->constrained()->cascadeOnDelete();
            $table->integer("user_id")->unsigned()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reprimand_watchers');
    }
};
