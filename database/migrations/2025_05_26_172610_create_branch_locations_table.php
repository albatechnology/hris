<?php

use App\Models\Branch;
use App\Models\BranchLocation;
use App\Models\ClientLocation;
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
        Schema::create('branch_locations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->foreignIdFor(Branch::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('lat');
            $table->string('lng');
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            // softDeletes must implement deleted_by
            $table->unsignedInteger('deleted_by')->nullable();
            $table->softDeletes();
        });

        ClientLocation::all()->each(function (ClientLocation $clientLocation) {
            BranchLocation::create([
                'uuid' => $clientLocation->uuid,
                'branch_id' => $clientLocation->client_id,
                'name' => $clientLocation->name,
                'lat' => $clientLocation->lat,
                'lng' => $clientLocation->lng,
                'address' => $clientLocation->address,
                'description' => $clientLocation->description,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_locations');
    }
};
