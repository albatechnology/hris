<?php

use App\Models\AbsenceReminder;
use App\Models\Client;
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
        Schema::create('absence_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained();
            $table->foreignIdFor(Client::class)->nullable()->constrained();
            $table->boolean('is_active')->default(0);
            $table->smallInteger('minutes_before')->unsigned()->default(60);
            $table->smallInteger('minutes_repeat')->unsigned()->default(10);
            $table->timestamps();
            $table->unsignedInteger('updated_by')->nullable();
        });

        // Client::get()->each(function ($client) {
        //     if (AbsenceReminder::where('client_id', $client->id)->doesntExist()) {
        //         AbsenceReminder::create([
        //             'company_id' => $client->company_id,
        //             'client_id' => $client->id,
        //             'minutes_before' => 60,
        //             'minutes_repeat' => 60,
        //         ]);
        //     }
        // });

        // Company::get()->each(function ($company) {
        //     if (AbsenceReminder::where('company_id', $company->id)->doesntExist()) {
        //         AbsenceReminder::create([
        //             'company_id' => $company->id,
        //             'minutes_before' => 60,
        //             'minutes_repeat' => 60,
        //         ]);
        //     }
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absence_reminders');
    }
};
