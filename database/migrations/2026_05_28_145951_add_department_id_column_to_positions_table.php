<?php

use App\Models\Department;
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
        Schema::table('divisions', function (Blueprint $table) {
            $table->foreignIdFor(User::class)->after('id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('order')->after('name')->default(1);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignIdFor(User::class)->after('id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('order')->after('name')->default(1);
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->foreignIdFor(Department::class)->after('id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'order']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'order']);
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['department_id']);
        });
    }
};
