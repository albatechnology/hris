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
        // Schema::table('users', function (Blueprint $table) {
        //     $table->foreignIdFor(Division::class)
        //         ->nullable()
        //         ->after('overtime_id')
        //         ->constrained()
        //         ->nullOnDelete();
        // });

        // /*
        // |--------------------------------------------------------------------------
        // | Copy division_id from departments
        // |--------------------------------------------------------------------------
        // */

        // DB::statement("
        //     UPDATE users u
        //     INNER JOIN departments d
        //         ON d.id = u.department_id
        //     SET
        //         u.division_id = d.division_id
        // ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('users', function (Blueprint $table) {
        //     $table->dropForeign(['division_id']);

        //     $table->dropColumn([
        //         'division_id',
        //     ]);
        // });
    }
};