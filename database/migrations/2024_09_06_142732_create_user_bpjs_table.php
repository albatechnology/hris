<?php

use App\Models\Npp;
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
        Schema::create('user_bpjs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained();
            $table->integer('upah_bpjs_kesehatan')->default(0);
            $table->integer('upah_bpjs_ketenagakerjaan')->default(0);
            $table->string('bpjs_ketenagakerjaan_no');
            $table->foreignIdFor(Npp::class)->constrained();
            $table->date('bpjs_ketenagakerjaan_date');
            $table->string('bpjs_kesehatan_no');
            $table->string('bpjs_kesehatan_family_no');
            $table->date('bpjs_kesehatan_date');
            $table->string('bpjs_kesehatan_cost');
            $table->string('jht_cost');
            $table->string('jaminan_pensiun_cost');
            $table->date('jaminan_pensiun_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_bpjs');
    }
};
