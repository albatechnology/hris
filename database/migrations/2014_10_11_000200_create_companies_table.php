<?php

use App\Enums\CurrencyCode;
use App\Enums\JkkTier;
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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained();
            $table->string('name', 100);
            $table->unsignedSmallInteger('jkk_tier')->default(JkkTier::VERY_LOW->value);
            $table->foreignId('country_id')->nullable();
            $table->string('country')->nullable();
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('lat')->nullable();
            $table->string('lng')->nullable();
            $table->text('address')->nullable();
            $table->char('currency_code', 3)->default(CurrencyCode::IDR);
            $table->timestamps();

            // softDeletes must implement deleted_by
            $table->unsignedInteger('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
