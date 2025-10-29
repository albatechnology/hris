<?php

use App\Models\Reprimand;
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
        // temporary table to record user reprimands
        Schema::create('reprimand_records', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(Reprimand::class)->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->timestamps();

            // created/updated/deleted info
            // $table->unsignedInteger('created_by')->nullable();
            // $table->unsignedInteger('updated_by')->nullable();
            // $table->unsignedInteger('deleted_by')->nullable();
            // $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reprimand_records');
    }
};
