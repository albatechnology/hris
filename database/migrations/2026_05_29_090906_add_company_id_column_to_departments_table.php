<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Schema::table('departments', function (Blueprint $table) {
        //     $table->foreignIdFor(Company::class)->after('id')->nullable()->constrained()->nullOnDelete();
        // });

        // // set company_id based on division_id
        // DB::statement("
        //     UPDATE departments dept
        //     INNER JOIN divisions d
        //         ON d.id = dept.division_id
        //     SET
        //         dept.company_id = d.company_id
        // ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('departments', function (Blueprint $table) {
        //     $table->dropForeign(['company_id']);
        //     $table->dropColumn(['company_id']);
        // });
    }
};
