<?php

use App\Models\Company;
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
        Schema::create('extra_offs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained();
            $table->json('user_ids');
            $table->timestamps();
            // created/updated/deleted info
            $table->unsignedInteger('created_by')->nullable();
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
        Schema::dropIfExists('extra_offs');
    }
};
