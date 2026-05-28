<?php

use App\Models\Department;
use App\Models\Position;
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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignIdFor(Department::class)->after('overtime_id')->constrained();
            $table->foreignIdFor(Position::class)->after('overtime_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('department_id');
            $table->dropForeign('position_id');
            $table->dropColumn(['department_id', 'position_id']);
        });
    }
};
