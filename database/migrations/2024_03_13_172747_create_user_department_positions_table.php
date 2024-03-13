<?php

use App\Models\Department;
use App\Models\Position;
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
        Schema::create('user_department_positions', function (Blueprint $table) {
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(Department::class);
            $table->foreignIdFor(Position::class);
            $table->timestamps();

            $table->primary(['user_id', 'department_id', 'position_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_department_positions');
    }
};
