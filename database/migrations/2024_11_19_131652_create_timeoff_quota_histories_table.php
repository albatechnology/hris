<?php

use App\Models\TimeoffQuota;
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
        Schema::create('timeoff_quota_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(TimeoffQuota::class)->constrained();
            $table->boolean('is_increment');
            $table->boolean('is_automatic')->default(0);
            $table->unsignedFloat('old_balance', 8, 1)->default(0);
            $table->unsignedFloat('new_balance', 8, 1)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            // created/updated/deleted info
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timeoff_quota_histories');
    }
};
