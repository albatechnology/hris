<?php

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
        // Schema::create('divisions', function (Blueprint $table) {
        //     $table->id();
        //     // $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete();
        //     $table->foreignId('company_id')->constrained();
        //     $table->string('name');
        //     $table->timestamps();

        //     // softDeletes must implement deleted_by_id
        //     $table->unsignedInteger('deleted_by_id')->nullable();
        //     $table->softDeletes();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::dropIfExists('divisions');
    }
};
