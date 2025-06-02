<?php

use App\Models\Branch;
use App\Models\BranchLocation;
use App\Models\PatrolLocation;
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
        Schema::table('patrols', function (Blueprint $table) {
            // $table->dropColumn('client_id');
            $table->foreignIdFor(Branch::class)->after('id')->default(1)->constrained()->cascadeOnDelete();
        });

        Schema::table('patrol_locations', function (Blueprint $table) {
            // $table->dropColumn('client_location_id');
            $table->foreignIdFor(BranchLocation::class)->after('patrol_id')->default(1)->constrained();
        });

        PatrolLocation::all()->each(function (PatrolLocation $patrolLocation) {
            $patrolLocation->update([
                'branch_location_id' => $patrolLocation->client_location_id
            ]);
        });

        Schema::table('absence_reminders', function (Blueprint $table) {
            // $table->dropColumn('client_id');
            $table->foreignIdFor(Branch::class)->after('company_id')->nullable()->constrained();
        });

        Schema::table('events', function (Blueprint $table) {
            // $table->dropColumn('client_id');
            $table->integer('branch_id')->after('company_id')->unsigned()->nullable();
        });

        Schema::table('guest_books', function (Blueprint $table) {
            // $table->dropColumn('client_id');
            $table->foreignIdFor(Branch::class)->after('id')->nullable()->constrained();
        });

        Schema::table('overtimes', function (Blueprint $table) {
            // $table->dropColumn('client_id');
            $table->integer('branch_id')->after('company_id')->unsigned()->nullable();
        });

        Schema::table('panics', function (Blueprint $table) {
            // $table->dropColumn('client_id');
            $table->integer('branch_id')->after('id')->unsigned()->nullable();
        });

        Schema::table('payroll_components', function (Blueprint $table) {
            // $table->dropColumn('client_id');
            $table->integer('branch_id')->after('company_id')->unsigned()->nullable();
        });

        Schema::table('payroll_settings', function (Blueprint $table) {
            // $table->dropColumn('client_id');
            $table->integer('branch_id')->after('company_id')->unsigned()->nullable();
        });

        Schema::table('run_payrolls', function (Blueprint $table) {
            // $table->dropColumn('client_id');
            $table->integer('branch_id')->after('company_id')->unsigned()->nullable();
        });

        Schema::table('update_payroll_components', function (Blueprint $table) {
            // $table->dropColumn('client_id');
            $table->integer('branch_id')->after('company_id')->unsigned()->nullable();
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->string('pic_name', 50)->nullable();
            $table->string('pic_email', 50)->nullable();
            $table->string('pic_phone', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absence_reminders', function (Blueprint $table) {
            $table->dropColumn([
                'branch_id',
            ]);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'branch_id',
            ]);
        });

        Schema::table('guest_books', function (Blueprint $table) {
            $table->dropColumn([
                'branch_id',
            ]);
        });

        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropColumn([
                'branch_id',
            ]);
        });

        Schema::table('panics', function (Blueprint $table) {
            $table->dropColumn([
                'branch_id',
            ]);
        });

        Schema::table('patrols', function (Blueprint $table) {
            $table->dropColumn([
                'branch_id',
            ]);
        });

        Schema::table('patrol_locations', function (Blueprint $table) {
            $table->dropColumn([
                'branch_location_id',
            ]);
        });

        Schema::table('payroll_components', function (Blueprint $table) {
            $table->dropColumn([
                'branch_id',
            ]);
        });

        Schema::table('payroll_settings', function (Blueprint $table) {
            $table->dropColumn([
                'branch_id',
            ]);
        });

        Schema::table('run_payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'branch_id',
            ]);
        });

        Schema::table('update_payroll_components', function (Blueprint $table) {
            $table->dropColumn([
                'branch_id',
            ]);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'pic_name',
                'pic_email',
                'pic_phone',
            ]);
        });
    }
};
