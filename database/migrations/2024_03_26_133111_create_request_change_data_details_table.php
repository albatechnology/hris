<?php

use App\Models\RequestChangeData;
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
        Schema::create('request_change_data_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_change_data_id')->constrained('request_change_data')->cascadeOnDelete();
            $table->string('type');
            $table->string('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_change_data_details');
    }
};
