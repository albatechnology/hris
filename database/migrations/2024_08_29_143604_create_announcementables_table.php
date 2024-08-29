<?php

use App\Models\Announcement;
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
        Schema::create('announcementables', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Announcement::class)->constrained()->cascadeOnDelete();
            $table->string('announcementable_type');
            $table->string('announcementable_id');
            
            $table->index('announcementable_id', 'announcementable_id');
            $table->index('announcementable_type', 'announcementable_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_receivers');
    }
};
