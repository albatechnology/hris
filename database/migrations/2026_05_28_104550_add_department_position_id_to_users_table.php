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
        //     $table->foreignIdFor(Department::class)
        //         ->nullable()
        //         ->after('overtime_id')
        //         ->constrained()
        //         ->nullOnDelete();

        //     $table->foreignIdFor(Position::class)
        //         ->nullable()
        //         ->after('department_id')
        //         ->constrained()
        //         ->nullOnDelete();
        // });

        // /*
        // |--------------------------------------------------------------------------
        // | Copy existing data
        // |--------------------------------------------------------------------------
        // */

        // DB::statement("
        //     UPDATE users u
        //     INNER JOIN user_department_positions udp
        //         ON udp.user_id = u.id
        //     SET
        //         u.department_id = udp.department_id,
        //         u.position_id = udp.position_id
        // ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('users', function (Blueprint $table) {
        //     $table->dropForeign(['department_id']);
        //     $table->dropForeign(['position_id']);

        //     $table->dropColumn([
        //         'department_id',
        //         'position_id',
        //     ]);
        // });
    }
};