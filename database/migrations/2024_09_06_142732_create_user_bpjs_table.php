<?php

use App\Enums\BpjsKesehatanCost;
use App\Enums\JaminanPensiunCost;
use App\Enums\JhtCost;
use App\Enums\NppBpjsKetenagakerjaan;
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
            $table->string('bpjs_ketenagakerjaan_no')->nullable();
            // $table->foreignIdFor(Npp::class)->constrained();
            $table->string('npp_bpjs_ketenagakerjaan')->default(NppBpjsKetenagakerjaan::DEFAULT);
            $table->date('bpjs_ketenagakerjaan_date')->nullable();
            $table->string('bpjs_kesehatan_no')->nullable();
            $table->string('bpjs_kesehatan_family_no')->nullable();
            $table->date('bpjs_kesehatan_date')->nullable();
            $table->string('bpjs_kesehatan_cost')->default(BpjsKesehatanCost::DEFAULT);
            $table->string('jht_cost')->default(JhtCost::DEFAULT);
            $table->string('jaminan_pensiun_cost')->default(JaminanPensiunCost::DEFAULT);
            $table->date('jaminan_pensiun_date')->nullable();
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
