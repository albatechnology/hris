<?php

use App\Enums\ResignationReason;
use App\Enums\ResignationType;
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
        Schema::create('user_resignations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class);
            $table->char('type', 6)->default(ResignationType::RESIGN->value);

            // FOR RESIGN
            $table->string('reason')->nullable();
            $table->date('resignation_date');
            $table->unsignedDouble('merit_pay_amount')->default(0);
            $table->unsignedDouble('severance_amount')->default(0);
            $table->unsignedDouble('compensation_amount')->default(0);
            $table->unsignedTinyInteger('total_day_timeoff_compensation')->default(0);
            $table->unsignedDouble('timeoff_amount_per_day')->default(0);
            $table->unsignedDouble('total_timeoff_compensation')->default(0);

            // FOR REHIRE
            $table->string('nik')->nullable();
            $table->integer('branch_id')->nullable();
            $table->integer('schedule_id')->nullable();
            $table->integer('department_id')->nullable();
            $table->integer('position_id')->nullable();
            $table->char('employment_status', 10)->nullable();
            $table->unsignedDouble('basic_salary')->default(0);

            $table->text('description')->nullable();
            $table->timestamps();
            // created/updated/deleted info
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->date('rehire_date')->nullable()->after('resign_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_resignations');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('rehire_date');
        });
    }
};
