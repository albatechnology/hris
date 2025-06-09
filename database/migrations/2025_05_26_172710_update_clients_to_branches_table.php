<?php

use App\Models\AbsenceReminder;
use App\Models\Branch;
use App\Models\BranchLocation;
use App\Models\GuestBook;
use App\Models\Patrol;
use App\Models\PatrolLocation;
use App\Models\PayrollComponent;
use App\Models\PayrollSetting;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patrols', function (Blueprint $table) {
            try {
                $table->dropForeign(['client_id']);
            } catch (QueryException $e) {
                // Foreign key nggak ada, skip
            }
            $table->integer('client_id')->unsigned()->nullable()->change();
            $table->foreignIdFor(Branch::class)->after('id')->default(1)->constrained()->cascadeOnDelete();
        });

        Schema::table('patrol_locations', function (Blueprint $table) {
            $table->dropForeign(['client_location_id']);
            $table->integer('client_location_id')->unsigned()->nullable()->change();
            $table->foreignIdFor(BranchLocation::class)->after('patrol_id')->default(1)->constrained();
        });

        PatrolLocation::all()->each(function (PatrolLocation $patrolLocation) {
            $patrolLocation->update([
                'branch_location_id' => $patrolLocation->client_location_id
            ]);
        });

        Schema::table('absence_reminders', function (Blueprint $table) {
            try {
                $table->dropForeign(['client_id']);
            } catch (QueryException $e) {
                // Foreign key nggak ada, skip
            }
            $table->integer('client_id')->unsigned()->nullable()->change();
            $table->foreignIdFor(Branch::class)->after('company_id')->nullable()->constrained();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignIdFor(Branch::class)->after('company_id')->nullable()->constrained()->cascadeOnDelete();
        });

        Schema::table('guest_books', function (Blueprint $table) {
            try {
                $table->dropForeign(['client_id']);
            } catch (QueryException $e) {
                // Foreign key nggak ada, skip
            }
            $table->integer('client_id')->unsigned()->nullable()->change();
            $table->foreignIdFor(Branch::class)->after('id')->nullable()->constrained();
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->foreignIdFor(Branch::class)->after('id')->nullable()->constrained();
        });

        Schema::table('overtimes', function (Blueprint $table) {
            // try {
            //     $table->dropForeign(['client_id']);
            // } catch (QueryException $e) {
            //     // Foreign key nggak ada, skip
            // }
            $table->integer('client_id')->unsigned()->nullable()->change();
            $table->integer('branch_id')->after('company_id')->unsigned()->nullable();
        });

        Schema::table('panics', function (Blueprint $table) {
            try {
                $table->dropForeign(['client_id']);
            } catch (QueryException $e) {
                // Foreign key nggak ada, skip
            }
            $table->integer('client_id')->unsigned()->nullable()->change();
            $table->integer('branch_id')->after('id')->unsigned()->nullable();
        });

        Schema::table('payroll_components', function (Blueprint $table) {
            // try {
            //     $table->dropForeign(['client_id']);
            // } catch (QueryException $e) {
            //     // Foreign key nggak ada, skip
            // }
            $table->integer('client_id')->unsigned()->nullable()->change();
            $table->integer('branch_id')->after('company_id')->unsigned()->nullable();
        });

        Schema::table('payroll_settings', function (Blueprint $table) {
            // try {
            //     $table->dropForeign(['client_id']);
            // } catch (QueryException $e) {
            //     // Foreign key nggak ada, skip
            // }
            $table->integer('client_id')->unsigned()->nullable()->change();
            $table->integer('branch_id')->after('company_id')->unsigned()->nullable();
        });

        Schema::table('run_payrolls', function (Blueprint $table) {
            // try {
            //     $table->dropForeign(['client_id']);
            // } catch (QueryException $e) {
            //     // Foreign key nggak ada, skip
            // }
            $table->integer('client_id')->unsigned()->nullable()->change();
            $table->integer('branch_id')->after('company_id')->unsigned()->nullable();
        });

        Schema::table('update_payroll_components', function (Blueprint $table) {
            // try {
            //     $table->dropForeign(['client_id']);
            // } catch (QueryException $e) {
            //     // Foreign key nggak ada, skip
            // }
            $table->integer('client_id')->unsigned()->nullable()->change();
            $table->integer('branch_id')->after('company_id')->unsigned()->nullable();
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->string('pic_name', 50)->nullable();
            $table->string('pic_email', 50)->nullable();
            $table->string('pic_phone', 20)->nullable();
        });


        // =======================================================================
        BranchLocation::where('branch_id', 1)->update([
            'branch_id' => 6
        ]);
        BranchLocation::where('branch_id', 4)->update([
            'branch_id' => 7
        ]);
        BranchLocation::where('branch_id', 6)->update([
            'branch_id' => 5
        ]);
        BranchLocation::where('branch_id', 9)->update([
            'branch_id' => 8
        ]);

        Media::where('model_type', 'App\Models\ClientLocation')->update([
            'model_type' => 'App\Models\BranchLocation'
        ]);



        PayrollSetting::where('company_id', 1)->update([
            'branch_id' => 1
        ]);
        PayrollSetting::where('company_id', 2)->update([
            'branch_id' => 3
        ]);
        PayrollSetting::where('company_id', 3)->update([
            'branch_id' => 5
        ]);

        /** @var App\Models\PayrollSetting $payrollSetting */
        $payrollSetting = PayrollSetting::where('company_id', 4)->first();
        $payrollSetting->update([
            'branch_id' => 6
        ]);
        PayrollSetting::create([
            ...$payrollSetting->toArray(),
            'branch_id' => 7,
        ]);
        PayrollSetting::create([
            ...$payrollSetting->toArray(),
            'branch_id' => 8,
        ]);
        PayrollSetting::create([
            ...$payrollSetting->toArray(),
            'branch_id' => 10,
        ]);



        PayrollComponent::where('company_id', 1)->update([
            'branch_id' => 1
        ]);
        PayrollComponent::where('company_id', 2)->update([
            'branch_id' => 3
        ]);
        PayrollComponent::where('company_id', 3)->update([
            'branch_id' => 5
        ]);
        PayrollComponent::where('company_id', 4)->update([
            'branch_id' => 6
        ]);


        GuestBook::whereIn('client_id', [3])->delete();
        GuestBook::where('client_id', 1)->update([
            'branch_id' => 6
        ]);
        GuestBook::where('client_id', 4)->update([
            'branch_id' => 7
        ]);
        GuestBook::where('client_id', 6)->update([
            'branch_id' => 5
        ]);
        GuestBook::where('client_id', 7)->update([
            'branch_id' => 10
        ]);


        AbsenceReminder::whereIn('client_id', [2, 3, 5, 7, 8])->delete();
        AbsenceReminder::where('client_id', 1)->update([
            'branch_id' => 6
        ]);
        AbsenceReminder::where('client_id', 4)->update([
            'branch_id' => 7
        ]);
        AbsenceReminder::where('client_id', 6)->update([
            'branch_id' => 5
        ]);
        AbsenceReminder::where('client_id', 9)->update([
            'branch_id' => 8
        ]);
        AbsenceReminder::where('client_id', 10)->update([
            'branch_id' => 10
        ]);


        Patrol::where('client_id', 1)->update([
            'branch_id' => 6
        ]);
        Patrol::where('client_id', 4)->update([
            'branch_id' => 7
        ]);
        Patrol::where('client_id', 6)->update([
            'branch_id' => 5
        ]);
        Patrol::where('client_id', 9)->update([
            'branch_id' => 8
        ]);
        Patrol::where('client_id', 10)->update([
            'branch_id' => 10
        ]);
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

        Schema::table('incidents', function (Blueprint $table) {
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
