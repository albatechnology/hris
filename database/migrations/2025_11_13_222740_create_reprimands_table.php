<?php

use App\Models\RunReprimand;
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
        Schema::create('reprimands', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(RunReprimand::class)->nullable()->constrained()->cascadeOnDelete();
            $table->string('month_type');
            $table->string('type');
            $table->mediumInteger('total_late_minutes')->unsigned();
            $table->date('effective_date');
            $table->date('end_date');
            $table->text('notes')->nullable();
            $table->json('details');
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
        Schema::dropIfExists('reprimands');
    }
};
