<?php

use App\Models\ClientLocation;
use App\Models\Company;
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
        Schema::table('incidents', function (Blueprint $table) {
            $table->foreignIdFor(Company::class)->after('id')->constrained()->cascadeOnDelete();

            $table->dropForeign('incidents_client_location_id_foreign');
            $table->dropColumn('client_location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropForeign('incidents_company_id_foreign');
            $table->dropColumn('company_id');
            $table->foreignIdFor(ClientLocation::class)->after('id')->constrained();
        });
    }
};
