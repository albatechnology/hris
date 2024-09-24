<?php

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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('pic_name')->nullable()->after('address');
            $table->string('pic_email')->nullable()->after('pic_name');
            $table->string('pic_phone')->nullable()->after('pic_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('pic_name');
            $table->dropColumn('pic_email');
            $table->dropColumn('pic_phone');
        });
    }
};
