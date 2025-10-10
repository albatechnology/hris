<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\RunReprimand;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reprimands', function (Blueprint $table) {
            $table->foreignIdFor(RunReprimand::class)->after('user_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reprimands', function (Blueprint $table) {
            $table->dropForeign(['run_reprimand_id']);
            $table->dropColumn('run_reprimand_id');
        });
    }
};
