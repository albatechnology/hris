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
        Schema::create('timeoff_regulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('renew_type', 15);
            $table->unsignedSmallInteger('total_day')->default(0);
            $table->char('start_period', 5)->comment('periode awal timeoff (format date: m-d). diisi jika renew_type == annual');
            $table->char('end_period', 5)->comment('periode awal timeoff (format date: m-d). diisi jika renew_type == annual');
            $table->unsignedSmallInteger('max_consecutively_day')->nullable()->comment('Maksimal pengajuan cuti tahunan (hari) berturut-turut');
            // $table->boolean('is_allow_halfday')->default(1);
            $table->text('halfday_not_applicable_in')->nullable()->comment('hari(array) yang tidak bisa diajukan cuti setengah hari (is_allow_halfday harus true)');
            $table->boolean('is_expired_in_end_period')->default(1)->comment('jika true, maka cuti akan hangus diakhir periode');
            $table->unsignedSmallInteger('expired_max_month')->nullable()->comment('jika null, maka sisa cuti tahun sebelumnya akan aktif tanpa batas waktu');
            $table->integer('min_working_month')->default(3)->comment('minimal masa kerja untuk dapat mendapatkan cuti');
            $table->char('cut_off_date', 2)->default('01')->comment('tanggal(day) cut off tiap bulannya');
            $table->unsignedSmallInteger('min_advance_leave_working_month')->nullable()->comment('minimal (bulan) kerja berturut-turut untuk dapat mengajukan advanced leave (hutang cuti)');
            $table->unsignedSmallInteger('max_advance_leave_request')->default(0)->comment('jika renew_type == monthly maka max_advance_leave_request adalah jumlah bulan kedepan. selain itu, max_advance_leave_request adalah total hari yang dapat diajukan');
            $table->unsignedSmallInteger('dayoff_consecutively_working_day')->nullable()->comment('minimal (hari) kerja berturut-turut untuk dapat mendapatkan day off (libur tanpa memotong cuti)');
            $table->integer('dayoff_consecutively_amount')->default(0)->comment('jumlah hari dayoff yang akan didapatkan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timeoff_regulations');
    }
};
