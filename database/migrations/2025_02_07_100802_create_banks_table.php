<?php

use App\Enums\BankName;
use App\Models\Bank;
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
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->string('name')->default(BankName::OCBC->value);
            $table->string('account_no', 50);
            $table->string('account_holder', 100);
            $table->string('code', 50);
            $table->string('branch', 100);
            $table->timestamps();

            // created/updated/deleted info
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->softDeletes();
        });

        Schema::table('user_payroll_infos', function (Blueprint $table) {
            $table->foreignIdFor(Bank::class)->nullable()->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banks');

        Schema::table('user_payroll_infos', function (Blueprint $table) {
            $table->dropColumn('bank_id');
        });
    }
};
