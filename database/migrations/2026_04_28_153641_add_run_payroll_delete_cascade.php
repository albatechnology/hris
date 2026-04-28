<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update tabel run_payroll_users
        Schema::table('run_payroll_users', function (Blueprint $table) {
            // Hapus constraint lama
            $table->dropForeign(['run_payroll_id']); 
            
            // Tambahkan kembali dengan cascade
            $table->foreign('run_payroll_id')
                  ->references('id')
                  ->on('run_payrolls') // pastikan nama tabel induknya benar
                  ->cascadeOnDelete();
        });

        // Update tabel run_payroll_user_components
        Schema::table('run_payroll_user_components', function (Blueprint $table) {
            $table->dropForeign(['run_payroll_user_id']);
            
            $table->foreign('run_payroll_user_id')
                  ->references('id')
                  ->on('run_payroll_users')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('run_payroll_users', function (Blueprint $table) {
            $table->dropForeign(['run_payroll_id']);
            $table->foreign('run_payroll_id')->constrained();
        });

        Schema::table('run_payroll_user_components', function (Blueprint $table) {
            $table->dropForeign(['run_payroll_user_id']);
            $table->foreign('run_payroll_user_id')->constrained();
        });
    }
};