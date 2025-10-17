<?php

namespace App\Services;

use App\Enums\CountrySettingKey;
use App\Enums\JaminanPensiunCost;
use App\Enums\OvertimeSetting;
use App\Enums\PaidBy;
use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentPeriodType;
use App\Enums\PayrollComponentType;
use App\Enums\PtkpStatus;
use App\Enums\RunPayrollStatus;
use App\Enums\TaxMethod;
use App\Enums\TaxSalary;
use App\Models\Loan;
use App\Models\PayrollComponent;
use App\Models\PayrollSetting;
use App\Models\RunPayroll;
use App\Models\RunPayrollUser;
use App\Models\RunPayrollUserComponent;
use App\Models\UpdatePayrollComponentDetail;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RunPayrollService
{
    public static function generateDate(string $startDate, string $endDate, string $period, bool $isSubMonth = false): array
    {
        $start = Carbon::parse($startDate . '-' . $period);
        $end = Carbon::parse($endDate . '-' . $period);
        if ($start->greaterThan($end)) {
            $start->subMonthNoOverflow();
        }

        $endBase = Carbon::parse("01-{$period}"); // ambil awal bulan
        $daysInMonth = $endBase->daysInMonth;

        if ((int) $endDate > $daysInMonth) {
            $end = $endBase->endOfMonth();
        } else {
            $end = Carbon::parse("{$endDate}-{$period}");
        }

        if ($isSubMonth) {
            $start->subMonthNoOverflow();
            $end->subMonthNoOverflow();

            if ((int) $endDate > $daysInMonth) {
                $end->endOfMonth();
            }
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * execute run payroll
     *
     * @param  array $request
     */
    public static function execute(array $request): RunPayroll | Exception | JsonResponse
    {
        $payrollSetting = PayrollSetting::with('company')
            ->whereCompany($request['company_id'])
            ->whenBranch($request['branch_id'] ?? null)
            ->first();
        // if (!$payrollSetting->cut_off_attendance_start_date || !$payrollSetting->cut_off_attendance_end_date) {
        //     return response()->json([
        //         'success' => false,
        //         'data' => 'Please set your Payroll cut off date before submit Run Payroll',
        //     ]);
        // }
        $cutOffAttendance = self::generateDate($payrollSetting->cut_off_attendance_start_date, $payrollSetting->cut_off_attendance_end_date, $request['period'], $payrollSetting->is_attendance_pay_last_month);
        $payrollDate = self::generateDate($payrollSetting->payroll_start_date, $payrollSetting->payroll_end_date, $request['period']);

        $request = array_merge($request, [
            'cut_off_start_date' => $cutOffAttendance['start'],
            'cut_off_end_date' => $cutOffAttendance['end'],
            'payroll_start_date' => $payrollDate['start'],
            'payroll_end_date' => $payrollDate['end'],
        ]);

        DB::beginTransaction();
        try {
            $runPayroll = self::createRunPayroll($request);

            $runPayrollDetail = self::createDetails($payrollSetting, $runPayroll, $request);

            // check if there's json error response
            if (!$runPayrollDetail->getData()?->success) {
                DB::rollBack();
                return response()->json($runPayrollDetail->getData());
            }

            DB::commit();

            return $runPayroll;
        } catch (Exception $e) {
            DB::rollBack();

            throw new Exception($e);
        }
    }

    /**
     * create run payroll
     *
     * @param  Request  $request
     */
    public static function createRunPayroll(array $request): RunPayroll
    {
        return auth('sanctum')->user()->runPayrolls()->create([
            'branch_id' => $request['branch_id'] ?? null,
            'company_id' => $request['company_id'],
            'period' => $request['period'],
            'payment_schedule' => $request['payment_schedule'],
            'status' => RunPayrollStatus::REVIEW,
            'cut_off_start_date' => $request['cut_off_start_date'],
            'cut_off_end_date' => $request['cut_off_end_date'],
            'payroll_start_date' => $request['payroll_start_date'],
            'payroll_end_date' => $request['payroll_end_date'],
        ]);
    }

    public static function calculateProrateTotalDays(int $totalWorkingDays, Carbon $startDate, Carbon $endDate, bool $isSubOneDay = false): int
    {
        // if ($isSubOneDay) {
        //     $period = CarbonPeriod::between($startDate, $endDate->subDays(2));
        // } else {
        //     $period = CarbonPeriod::between($startDate, $endDate);
        // }
        $period = CarbonPeriod::between($startDate, $endDate);

        if ($totalWorkingDays > 21) {
            $wd = collect($period)->filter(function (Carbon $tanggal) {
                return !$tanggal->isSunday(); // sunday is not included
            })->count();
        } else {
            $wd = collect($period)->filter(function (Carbon $tanggal) {
                return !$tanggal->isWeekend(); // weekends is not included
            })->count();
        }

        if ($isSubOneDay) return $wd -= 1;

        return max($wd, 0);
    }

    public static function prorate(int|float $basicAmount, int|float $updatePayrollComponentAmount, int $totalWorkingDays, Carbon $startDate, Carbon $endDate, Carbon $startEffectiveDate, Carbon|null $endEffectiveDate, bool $isDebug = false): int|float
    {
        // effective_date is between period
        if ($startEffectiveDate->between($startDate, $endDate)) {
            // jika terdapat end_date
            if ($endEffectiveDate && $endEffectiveDate->lessThanOrEqualTo($endDate)) {
                $totalDaysFromStartDateToStartEffectiveDate = self::calculateProrateTotalDays($totalWorkingDays, $startDate, $startEffectiveDate, true);
                $startSalary = ($totalDaysFromStartDateToStartEffectiveDate / $totalWorkingDays) * $basicAmount;

                $totalDaysFromStartEffectiveDateToEndEffectiveDate = self::calculateProrateTotalDays($totalWorkingDays, $startEffectiveDate, $endEffectiveDate);
                $middleSalary = ($totalDaysFromStartEffectiveDateToEndEffectiveDate / $totalWorkingDays) * $updatePayrollComponentAmount;

                $endSalary = 0;
                if ($endEffectiveDate->lessThan($endDate)) {
                    $totalDaysFromEndEffectiveDateToEndDate = self::calculateProrateTotalDays($totalWorkingDays, $endEffectiveDate, $endDate, true);
                    $endSalary = ($totalDaysFromEndEffectiveDateToEndDate / $totalWorkingDays) * $basicAmount;
                }

                $basicAmount = $startSalary + $middleSalary + $endSalary;
            } else {
                // NORMAL CALCULATION
                $totalDaysFromStartDateToStartEffectiveDate = self::calculateProrateTotalDays($totalWorkingDays, $startDate, $startEffectiveDate, true);
                $startSalary = ($totalDaysFromStartDateToStartEffectiveDate / $totalWorkingDays) * $basicAmount;
                // $totalDaysFromStartEffectiveDateToEndDate = self::calculateProrateTotalDays($totalWorkingDays, $startEffectiveDate, $endDate);
                $totalDaysFromStartEffectiveDateToEndDate = $totalWorkingDays - $totalDaysFromStartDateToStartEffectiveDate;
                $endSalary = ($totalDaysFromStartEffectiveDateToEndDate / $totalWorkingDays) * $updatePayrollComponentAmount;

                $basicAmount = $startSalary + $endSalary;
            }
        } else {
            if ($endEffectiveDate && $endEffectiveDate->lessThanOrEqualTo($endDate)) {
                $totalDaysFromStartDateToEndEffectiveDate = self::calculateProrateTotalDays($totalWorkingDays, $startDate, $endEffectiveDate);
                $startSalary = ($totalDaysFromStartDateToEndEffectiveDate / $totalWorkingDays) * $updatePayrollComponentAmount;

                // $totalDaysFromEndEffectiveDateToEndDate = self::calculateProrateTotalDays($totalWorkingDays, $endEffectiveDate, $endDate, true);
                $totalDaysFromEndEffectiveDateToEndDate = $totalWorkingDays - $totalDaysFromStartDateToEndEffectiveDate;
                $endSalary = ($totalDaysFromEndEffectiveDateToEndDate / $totalWorkingDays) * $basicAmount;

                $basicAmount = $startSalary + $endSalary;
            } else {
                $basicAmount = $updatePayrollComponentAmount;
            }
        }

        return $basicAmount;
    }

        /**
     * create run payroll details
     *
     * @param  PayrollSetting   $payrollSetting
     * @param  RunPayroll   $runPayroll
     * @param  Request      $request
     * @return JsonResponse
     */

    public static function createDetailsMatt($payrollSetting, $runPayroll,array  $request): JsonResponse
    {
        $company = $payrollSetting->company;
        $max_upahBpjsKesehatan = $company->countryTable->countrySettings()->firstWhere('key',CountrySettingKey::BPJS_KESEHATAN_MAXIMUM_SALARY)?->value;
        $max_jp = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::JP_MAXIMUM_SALARY)?->value;

        $userIds = isset($request['user_ids']) && !empty($request['user_ids'])
            ?(is_array($request['user_ids']) ? $request['user_ids'] : explode(',', $request['user_ids']))
            : User::where('company_id', $runPayroll->company_id)
            ->whenBranch($runPayroll->branch_id)
            ->pluck('id')->toArray();

            if(empty($userIds)){
                 return response()->json([
            'success' => true,
            'data' => null,
        ]);
            }

        $users = User::whereIn('id',$userIds)
            ->whereHas('payrollInfo')
            ->with([
                'payrollInfo',
                'userBpjs'
            ])->get()->keyBy('id');

        if($users->isEmpty()){
            return response()->json(['success' => true, 'data' => null]);
        }

        $updatePayrollComponentDetailsGrouped = UpdatePayrollComponentDetail::with('updatePayrollComponent')
        ->whereIn('user_id',$users->keys()->toArray())
        ->whereHas('updatePayrollComponent', fn($q)=> $q->whereCompany($runPayroll->company_id)
        ->whenBranch($runPayroll->branch_id)
        ->whereActive($runPayroll->payroll_start_date, $runPayroll->payroll_end_date)
        )
        ->orderByDesc('id')
        ->get()
        ->groupBy('user_id');

        $basicSalaryComponent = PayrollComponent::tenanted()
            ->whereCompany($runPayroll->company_id)
            ->whenBranch($runPayroll->branch_id)
            ->where('category', PayrollComponentCategory::BASIC_SALARY)
            ->first();

        $reimbursementComponent = PayrollComponent::tenanted()
            ->whereCompany($runPayroll->company_id)
            ->whenBranch($runPayroll->branch_id)
            ->where('category', PayrollComponentCategory::REIMBURSEMENT)
            ->first();

        $payrollComponents = PayrollComponent::tenanted()
            ->where('company_id', $runPayroll->company_id)
            ->whenBranch($runPayroll->branch_id)
            ->whereNotDefault()
            ->get();

        $alpaComponent = PayrollComponent::tenanted()
            ->where('company_id', $runPayroll->company_id)
            ->whenBranch($runPayroll->branch_id)
            ->where('category', PayrollComponentCategory::ALPA)
            ->first();

        $loanComponent = PayrollComponent::tenanted()
            ->where('company_id',$runPayroll->company_id)
            ->whenBranch($runPayroll->branch_id)
            ->where('category',PayrollComponentCategory::LOAN)
            ->first();

        $insuranceComponent = PayrollComponent::tenanted()
            ->where('company_id', $runPayroll->company_id)
            ->whenBranch($runPayroll->branch_id)
            ->where('category', PayrollComponentCategory::INSURANCE)->first();

        $bpjsPayrollComponents = PayrollComponent::tenanted()
            ->whereCompany($runPayroll->company_id)
            ->whenBranch($runPayroll->branch_id)
            ->whereBpjs()->get();

        $overtimePayrollComponent = PayrollComponent::tenanted()
            ->whereCompany($runPayroll->company_id)
            ->whenBranch($runPayroll->branch_id)
            ->where('category', PayrollComponentCategory::OVERTIME)->first();

        $bpjsKesehatanFamilyComponent = PayrollComponent::tenanted()
            ->whereCompany($runPayroll->company_id)
            ->whenBranch($runPayroll->branch_id)
            ->where('category', PayrollComponentCategory::BPJS_KESEHATAN_FAMILY)->first();

        foreach($users->chunk(50) as $chunk){
            foreach($chunk as $user){
                $cutOffStartDate = $runPayroll->cut_off_start_date;
                $cutOffEndDate = $runPayroll->cut_off_end_date;
                $startDate = $runPayroll->payroll_start_date;
                $endDate = $runPayroll->payroll_end_date;

                if(!$user->payrollInfo) continue;

                $resignDate= null;

                if($user->join_date){
                    $joinDate = Carbon::parse($user->join_date);
                    if($joinDate->greaterThan($endDate)){
                        continue;
                }
            }
                if($user->resign_date){
                    $resignDate = Carbon::parse($user->resign_date);
                    if($resignDate->lessThan($cutOffEndDate)){
                        continue;
                    }
                }
            $runPayrollUser = self::assignUser($runPayroll, $user->id);

            $userBasicSalary = $user->payrollInfo?->basic_salary;
            $isTaxable= $user->payrollInfo?->tax_salary->is(TaxSalary::TAXABLE) ?? true;

            $isFirstTimePayroll = self::isFirstTimePayroll($user);
            $joinDate = Carbon::parse($user->join_date);

            if($isFirstTimePayroll && $joinDate->between($startDate, $endDate)){
                $cutOffStartDate = $joinDate;
                $cutOffEndDate = $endDate;
                $totalWorkingDays = AttendanceService::getTotalWorkingDaysNewUser($user, $cutOffStartDate, $cutOffEndDate);
            }elseif($resignDate && $resignDate->between($startDate, $endDate)){
                $cutOffStartDate = $startDate;
                $cutOffEndDate = $resignDate;
                $totalWorkingDays = AttendanceService::getTotalWorkingDays($user, $cutOffStartDate, $cutOffEndDate);
                $totalPresent = AttendanceService::getTotalAttend($user, $cutOffStartDate, $cutOffEndDate);

                $userBasicSalary = ($userBasicSalary / $totalWorkingDays) * $totalPresent;
            }else{
                $totalWorkingDays = AttendanceService::getTotalAttend($user, $cutOffStartDate, $cutOffEndDate);
            }

            $updatePayrollComponentDetails = $updatePayrollComponentDetailsGrouped->get($user->id) ?? collect();

            if(!$basicSalaryComponent){
                continue;
            }

            $updatePayrollComponentDetail = $updatePayrollComponentDetails->where('payroll_component_id', $basicSalaryComponent->id)->first();

            if($isFirstTimePayroll && $joinDate->between($cutOffStartDate, $cutOffEndDate)){
                $userBasicSalary = $totalWorkingDays / $user->payrollInfo->total_working_days * $userBasicSalary;
            }

            if($updatePayrollComponentDetail){
                $startEffectiveDate = Carbon::parse($updatePayrollComponentDetail->updatePayrollComponent->effective_date);
                $endEffectiveDate = $updatePayrollComponentDetail->updatePayrollComponent->end_date ? Carbon::parse($updatePayrollComponentDetail->updatePayrollComponent->end_date) : null;
                $userBasicSalary = self::prorate($userBasicSalary, $updatePayrollComponentDetail->new_amount, $totalWorkingDays, $startDate, $endDate, $startEffectiveDate, $endEffectiveDate);
            }

            $amount =self::calculatePayrollComponentPeriodType($basicSalaryComponent, $userBasicSalary, $totalWorkingDays, $runPayrollUser);
            self::createComponent($runPayrollUser, $basicSalaryComponent, $amount);

            if($reimbursementComponent){
                $amount = app(\App\Http\Services\Reimbursement\ReimbursementService::class)
                        ->getTotalReimbursementTaken(userId: $user, startDate: $cutOffStartDate, endDate: $cutOffEndDate);
                self::createComponent($runPayrollUser, $reimbursementComponent, $amount);
            }

            $payrollComponents->each(function($payrollComponent) use ($user, $updatePayrollComponentDetails, $runPayrollUser, $totalWorkingDays, $cutOffStartDate, $cutOffEndDate){
                if($payrollComponent->amount == 0 && count($payrollComponent->formulas)){
                    $amount = FormulaService::calculate(user:$user, model: $payrollComponent, formulas: $payrollComponent->formulas, startPeriod: $cutOffStartDate, endPeriod: $cutOffEndDate);
                }else{
                    $amount = $payrollComponent->amount;
                }

                $updatePayrollComponentDetail = $updatePayrollComponentDetails->where('payroll_component_id', $payrollComponent->id)->first();

                if($updatePayrollComponentDetail){
                    $amount = self::calculatePayrollComponentPeriodType($payrollComponent, $updatePayrollComponentDetail->new_amount, $totalWorkingDays, $runPayrollUser, $updatePayrollComponentDetail);
                }else{
                    $amount = self::calculatePayrollComponentPeriodType($payrollComponent, $amount, $totalWorkingDays, $runPayrollUser);
                }
                self::createComponent($runPayrollUser, $payrollComponent, $amount);
            });

            if($user->payrollInfo?->is_ignore_alpa == false && !$isFirstTimePayroll && !$joinDate->between($cutOffStartDate, $cutOffEndDate) && $alpaComponent){
                $alpaUpdateComponent = $updatePayrollComponentDetails->where('payroll_component_id',$alpaComponent->id)->first();
                if($alpaUpdateComponent){
                    $amount = $alpaUpdateComponent->new_amount;
                }else{
                    $totalWorkingDaysForAlpa = ScheduleService::getTotalWorkingDaysInPeriod($user,$cutOffStartDate, $cutOffEndDate);
                    $totalAlpa = AttendanceService::getTotalAlpa($user, $cutOffStartDate, $cutOffEndDate);
                    $totalAllowance = $runPayrollUser->components()->whereHas('payrollComponent', fn($q)=> $q->where('type', PayrollComponentType::ALLOWANCE)->whereNotIn('category',[PayrollComponentCategory::BASIC_SALARY, PayrollComponentCategory::REIMBURSEMENT, PayrollComponentCategory::OVERTIME]))->sum('amount');
                    $amount = round(max(($totalAlpa/$totalWorkingDaysForAlpa) * ($userBasicSalary + $totalAllowance), 0));
                }

                $amount = self::calculatePayrollComponentPeriodType($alpaComponent, $amount, $totalWorkingDays, $runPayrollUser);
                self::createComponent($runPayrollUser, $alpaComponent, $amount);
            }

            if ($loanComponent) {
                $whereHas = fn($q)=>$q->whereNull('run_payroll_user_id')->where('payment_period_year', $startDate->format('Y'))->where('payment_period_month', $startDate->format('m'));
                $loans = Loan::where('user_id',$user->id)->whereLoan()->whereHas('details', $whereHas)->get(['id']);
                if($loans->count()){
                    $loans->load(['details'=>$whereHas]);
                    $amount = $loans->sum(fn($loan)=> $loan->details->sum('total'));
                    self::createComponent($runPayrollUser, $loanComponent, $amount, ["loans"=>$loans->toArray()]);
                }
            }

             if ($insuranceComponent) {
                    $whereHas = fn($q) => $q->whereNull('run_payroll_user_id')->where('payment_period_year', $startDate->format('Y'))->where('payment_period_month', $startDate->format('m'));
                    $insurances = Loan::where('user_id', $user->id)->whereInsurance()->whereHas('details', $whereHas)->get(['id']);
                    if ($insurances->count()) {
                        $insurances->load(['details' => $whereHas]);
                        $amount = $insurances->sum(fn($loan) => $loan->details->sum('total'));
                        self::createComponent($runPayrollUser, $insuranceComponent, $amount, ["insurances" => $insurances->toArray()]);
                    }
                }
            if ($company->countryTable?->id == 1 && $user->userBpjs) {
                    $isEligibleToCalculateBpjsKesehatan = false;
                    if (
                        !empty($user->userBpjs->bpjs_kesehatan_no)
                        && !empty($user->userBpjs->bpjs_kesehatan_date)
                        && (
                            date('Y', strtotime($user->userBpjs->bpjs_kesehatan_date)) < date('Y', strtotime($runPayroll->payroll_start_date))
                            || (date('Y', strtotime($user->userBpjs->bpjs_kesehatan_date)) == date('Y', strtotime($runPayroll->cut_off_end_date)) && date('m', strtotime($user->userBpjs->bpjs_kesehatan_date)) <= date('m', strtotime($runPayroll->payroll_start_date)))
                        )
                    ) {
                        $isEligibleToCalculateBpjsKesehatan = true;
                    }

                    $isEligibleToCalculateBpjsKetenagakerjaan = false;
                    if (
                        !empty($user->userBpjs->bpjs_ketenagakerjaan_no)
                        && !empty($user->userBpjs->bpjs_ketenagakerjaan_date)
                        && (
                            date('Y', strtotime($user->userBpjs->bpjs_ketenagakerjaan_date)) < date('Y', strtotime($runPayroll->payroll_start_date))
                            || (date('Y', strtotime($user->userBpjs->bpjs_ketenagakerjaan_date)) == date('Y', strtotime($runPayroll->cut_off_end_date)) && date('m', strtotime($user->userBpjs->bpjs_ketenagakerjaan_date)) <= date('m', strtotime($runPayroll->payroll_start_date)))
                        )
                    ) {
                        $isEligibleToCalculateBpjsKetenagakerjaan = true;
                    }

                    $current_upahBpjsKesehatan = $user->userBpjs->upah_bpjs_kesehatan;
                    if ($max_upahBpjsKesehatan && $current_upahBpjsKesehatan > $max_upahBpjsKesehatan) $current_upahBpjsKesehatan = $max_upahBpjsKesehatan;

                    $current_upahBpjsKetenagakerjaan = $user->userBpjs->upah_bpjs_ketenagakerjaan;
                    if ($max_jp && $current_upahBpjsKetenagakerjaan > $max_jp) $current_upahBpjsKetenagakerjaan = $max_jp;

                    $company_percentageBpjsKesehatan = (float) ($countrySettings[CountrySettingKey::COMPANY_BPJS_KESEHATAN_PERCENTAGE] ?? 0);
                    $employee_percentageBpjsKesehatan = (float) ($countrySettings[CountrySettingKey::EMPLOYEE_BPJS_KESEHATAN_PERCENTAGE] ?? 0);
                    if (!$isTaxable) {
                        $company_percentageBpjsKesehatan += $employee_percentageBpjsKesehatan;
                        $employee_percentageBpjsKesehatan = 0;
                    }

                    $company_totalBpjsKesehatan = $current_upahBpjsKesehatan * ($company_percentageBpjsKesehatan / 100);
                    $employee_totalBpjsKesehatan = $current_upahBpjsKesehatan * ($employee_percentageBpjsKesehatan / 100);
                    if ($user->userBpjs->bpjs_kesehatan_cost->is(PaidBy::COMPANY)) {
                        $company_totalBpjsKesehatan += $employee_totalBpjsKesehatan;
                        $employee_totalBpjsKesehatan = 0;
                    }

                    $company_percentageJkk = $company->jkk_tier->getValue() ?? 0;
                    $company_totalJkk = $user->userBpjs->upah_bpjs_ketenagakerjaan * ($company_percentageJkk / 100);

                    $company_percentageJkm = (float) ($countrySettings[CountrySettingKey::COMPANY_JKM_PERCENTAGE] ?? 0);
                    $company_totalJkm = $user->userBpjs->upah_bpjs_ketenagakerjaan * ($company_percentageJkm / 100);

                    $company_percentageJht = (float) ($countrySettings[CountrySettingKey::COMPANY_JHT_PERCENTAGE] ?? 0);
                    $employee_percentageJht = (float) ($countrySettings[CountrySettingKey::EMPLOYEE_JHT_PERCENTAGE] ?? 0);
                    if (!$isTaxable) {
                        $company_percentageJht += $employee_percentageJht;
                        $employee_percentageJht = 0;
                    }

                    $company_totalJht = $user->userBpjs->upah_bpjs_ketenagakerjaan * ($company_percentageJht / 100);
                    $employee_totalJht = $user->userBpjs->upah_bpjs_ketenagakerjaan * ($employee_percentageJht / 100);
                    if ($user->userBpjs->jht_cost->is(PaidBy::COMPANY)) {
                        $company_totalJht += $employee_totalJht;
                        $employee_totalJht = 0;
                    }

                    $company_totalJp = 0;
                    $employee_totalJp = 0;
                    if (!$user->userBpjs->jaminan_pensiun_cost->is(JaminanPensiunCost::NOT_PAID)) {
                        $company_percentageJp = (float) ($countrySettings[CountrySettingKey::COMPANY_JP_PERCENTAGE] ?? 0);
                        $employee_percentageJp = (float) ($countrySettings[CountrySettingKey::EMPLOYEE_JP_PERCENTAGE] ?? 0);
                        if (!$isTaxable) {
                            $company_percentageJp += $employee_percentageJp;
                            $employee_percentageJp = 0;
                        }

                        $company_totalJp = $current_upahBpjsKetenagakerjaan * ($company_percentageJp / 100);
                        $employee_totalJp = $current_upahBpjsKetenagakerjaan * ($employee_percentageJp / 100);

                        if ($user->userBpjs->jaminan_pensiun_cost->is(JaminanPensiunCost::COMPANY)) {
                            $company_totalJp += $employee_totalJp;
                            $employee_totalJp = 0;
                        }
                    }

                    foreach ($bpjsPayrollComponents as $bpjsPayrollComponent) {
                        $amount = 0;
                        if ($isEligibleToCalculateBpjsKesehatan) {
                            if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_BPJS_KESEHATAN)) $amount = $company_totalBpjsKesehatan;
                            if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::EMPLOYEE_BPJS_KESEHATAN)) $amount = $employee_totalBpjsKesehatan;
                        }

                        if ($isEligibleToCalculateBpjsKetenagakerjaan) {
                            if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JKK)) $amount = $company_totalJkk;
                            if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JKM)) $amount = $company_totalJkm;
                            if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JHT)) $amount = $company_totalJht;
                            if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::EMPLOYEE_JHT)) $amount = $employee_totalJht;
                            if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JP)) $amount = $company_totalJp;
                            if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::EMPLOYEE_JP)) $amount = $employee_totalJp;
                        }

                        $amount = self::calculatePayrollComponentPeriodType($bpjsPayrollComponent, $amount, $totalWorkingDays, $runPayrollUser);
                        self::createComponent($runPayrollUser, $bpjsPayrollComponent, $amount);
                    }
                }

                // overtime
                if ($overtimePayrollComponent && $user->payrollInfo->overtime_setting->is(OvertimeSetting::ELIGIBLE)) {
                    $amount = OvertimeService::calculate($user, $cutOffStartDate, $cutOffEndDate, $userBasicSalary);
                    self::createComponent($runPayrollUser, $overtimePayrollComponent, $amount);
                }

                // task overtime (app specific)
                if (config('app.name') == 'SUNSHINE') {
                    $taskOvertimePayrollComponent = PayrollComponent::tenanted()
                        ->whereCompany($runPayroll->company_id)
                        ->whenBranch($runPayroll->branch_id)
                        ->where('category', PayrollComponentCategory::TASK_OVERTIME)->first();

                    if ($taskOvertimePayrollComponent) {
                        $amount = OvertimeService::calculateTaskOvertime($user, $cutOffStartDate, $cutOffEndDate);
                        self::createComponent($runPayrollUser, $taskOvertimePayrollComponent, $amount);
                    }
                }

                // BPJS FAMILY
                if ($user->userBpjs && !$user->userBpjs->bpjs_kesehatan_family_no->is(\App\Enums\BpjsKesehatanFamilyNo::ZERO) && $bpjsKesehatanFamilyComponent) {
                    $current_upahBpjsKesehatan = $user->userBpjs->upah_bpjs_kesehatan;
                    if ($max_upahBpjsKesehatan && $current_upahBpjsKesehatan > $max_upahBpjsKesehatan) $current_upahBpjsKesehatan = $max_upahBpjsKesehatan;

                    $amount = ($current_upahBpjsKesehatan * 0.01) * $user->userBpjs->bpjs_kesehatan_family_no->value;
                    self::createComponent($runPayrollUser, $bpjsKesehatanFamilyComponent, $amount);
                }

                // refresh totals
                self::refreshRunPayrollUser($runPayrollUser);
        }
    }
    return response()->json([
            'success' => true,
            'data' => null,
        ]);
}
    /**
     * create run payroll details
     *
     * @param  PayrollSetting   $payrollSetting
     * @param  RunPayroll   $runPayroll
     * @param  Request      $request
     * @return JsonResponse
     */
    public static function createDetails(PayrollSetting $payrollSetting, RunPayroll $runPayroll, array $request): JsonResponse
    {
        // $cutoffDiffDay = $cutOffStartDate->diff($cutOffEndDate)->days;
        $company = $payrollSetting->company;

        $max_upahBpjsKesehatan = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::BPJS_KESEHATAN_MAXIMUM_SALARY)?->value;
        $max_jp = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::JP_MAXIMUM_SALARY)?->value;

        $userIds = isset($request['user_ids']) && !empty($request['user_ids']) ? explode(',', $request['user_ids']) : User::where('company_id', $runPayroll->company_id)
            ->whenBranch($runPayroll->branch_id)
            ->pluck('id')->toArray();

        // calculate for each user
        foreach ($userIds as $userId) {
            $cutOffStartDate = $runPayroll->cut_off_start_date;
            $cutOffEndDate = $runPayroll->cut_off_end_date;
            $startDate = $runPayroll->payroll_start_date;
            $endDate = $runPayroll->payroll_end_date;

            /** @var \App\Models\User $user */
            $user = User::where('id', $userId)->has('payrollInfo')->with('payrollInfo')->first();
            if (!$user) continue;
            $resignDate = null;

            if ($user->join_date) {
                $joinDate = Carbon::parse($user->join_date);
                if ($joinDate->greaterThan($endDate)) {
                    continue;
                }
            }

            if ($user->resign_date) {
                $resignDate = Carbon::parse($user->resign_date);
                if ($resignDate->lessThan($cutOffEndDate)) {
                    continue;
                }
            }

            $runPayrollUser = self::assignUser($runPayroll, $userId);

            $userBasicSalary = $user->payrollInfo?->basic_salary;

            $isTaxable = $user->payrollInfo?->tax_salary->is(TaxSalary::TAXABLE) ?? true;

            $isFirstTimePayroll = self::isFirstTimePayroll($user);
            $joinDate = Carbon::parse($user->join_date);
            if ($isFirstTimePayroll && $joinDate->between($startDate, $endDate)) {
                $cutOffStartDate = $joinDate;
                $cutOffEndDate = $endDate;
                $totalWorkingDays = AttendanceService::getTotalWorkingDaysNewUser($user, $cutOffStartDate, $cutOffEndDate);
            } elseif ($resignDate && $resignDate->between($startDate, $endDate)) {
                $cutOffStartDate = $startDate;
                $cutOffEndDate = $resignDate;
                $totalWorkingDays = AttendanceService::getTotalWorkingDays($user, $cutOffStartDate, $cutOffEndDate);
                $totalPresent = AttendanceService::getTotalAttend($user, $cutOffStartDate, $cutOffEndDate);

                $userBasicSalary = ($userBasicSalary / $totalWorkingDays) * $totalPresent;
            } else {
                // $totalWorkingDays = AttendanceService::getTotalWorkingDays($user, $cutOffStartDate, $cutOffEndDate);
                $totalWorkingDays = AttendanceService::getTotalAttend($user, $cutOffStartDate, $cutOffEndDate);
            }

            $updatePayrollComponentDetails = UpdatePayrollComponentDetail::with('updatePayrollComponent')
                ->where('user_id', $userId)
                ->whereHas(
                    'updatePayrollComponent',
                    fn($q) => $q->whereCompany($runPayroll->company_id)
                        ->whenBranch($runPayroll->branch_id)
                        ->whereActive($startDate, $endDate)
                )
                ->orderByDesc('id')->get();

            /**
             * first, calculate basic salary. for now basic salary component is required
             */
            $basicSalaryComponent = PayrollComponent::tenanted()
                ->where('company_id', $runPayroll->company_id)
                ->whenBranch($runPayroll->branch_id)
                ->where('category', PayrollComponentCategory::BASIC_SALARY)->firstOrFail();

            $updatePayrollComponentDetail = $updatePayrollComponentDetails->where('payroll_component_id', $basicSalaryComponent->id)->first();

            if ($isFirstTimePayroll && $joinDate->between($cutOffStartDate, $cutOffEndDate)) {
                $userBasicSalary = $totalWorkingDays / $user->payrollInfo->total_working_days * $userBasicSalary;
            }

            if ($updatePayrollComponentDetail) {
                $startEffectiveDate = Carbon::parse($updatePayrollComponentDetail->updatePayrollComponent->effective_date);

                // end_date / endEffectiveDate can be null
                $endEffectiveDate = $updatePayrollComponentDetail->updatePayrollComponent->end_date ? Carbon::parse($updatePayrollComponentDetail->updatePayrollComponent->end_date) : null;

                // calculate prorate
                $userBasicSalary = self::prorate($userBasicSalary, $updatePayrollComponentDetail->new_amount, $totalWorkingDays, $startDate, $endDate, $startEffectiveDate, $endEffectiveDate);
            }

            $amount = self::calculatePayrollComponentPeriodType($basicSalaryComponent, $userBasicSalary, $totalWorkingDays, $runPayrollUser);
            self::createComponent($runPayrollUser, $basicSalaryComponent, $amount);

            /**
             * five, calculate reimbursement
             */
            $reimbursementComponent = PayrollComponent::tenanted()
                ->whereCompany($runPayroll->company_id)
                ->whenBranch($runPayroll->branch_id)
                ->where('category', PayrollComponentCategory::REIMBURSEMENT)->first();

            if ($reimbursementComponent) {
                $amount = app(\App\Http\Services\Reimbursement\ReimbursementService::class)->getTotalReimbursementTaken(userId: $user, startDate: $cutOffStartDate, endDate: $cutOffEndDate);

                self::createComponent($runPayrollUser, $reimbursementComponent, $amount);
            }
            // END

            /**
             * second, calculate payroll component where not default
             */
            $payrollComponents = PayrollComponent::tenanted()
                ->where('company_id', $runPayroll->company_id)
                ->whenBranch($runPayroll->branch_id)
                ->whereNotDefault()->get();

            $payrollComponents->each(function ($payrollComponent) use ($user, $updatePayrollComponentDetails, $runPayrollUser,  $totalWorkingDays, $cutOffStartDate, $cutOffEndDate) {

                if ($payrollComponent->amount == 0 && count($payrollComponent->formulas)) {
                    $amount = FormulaService::calculate(user: $user, model: $payrollComponent, formulas: $payrollComponent->formulas, startPeriod: $cutOffStartDate, endPeriod: $cutOffEndDate);
                } else {
                    $amount = $payrollComponent->amount;
                }

                $updatePayrollComponentDetail = $updatePayrollComponentDetails->where('payroll_component_id', $payrollComponent->id)->first();

                if ($updatePayrollComponentDetail) {
                    // $startEffectiveDate = Carbon::parse($updatePayrollComponentDetail->updatePayrollComponent->effective_date);

                    // // end_date / endEffectiveDate can be null
                    // $endEffectiveDate = $updatePayrollComponentDetail->updatePayrollComponent->end_date ? Carbon::parse($updatePayrollComponentDetail->updatePayrollComponent->end_date) : null;

                    // calculate prorate
                    // $amount = self::prorate($amount, $updatePayrollComponentDetail->new_amount, $totalWorkingDays, $cutOffStartDate, $cutOffEndDate, $startEffectiveDate, $endEffectiveDate, true);

                    $amount = self::calculatePayrollComponentPeriodType($payrollComponent, $updatePayrollComponentDetail->new_amount, $totalWorkingDays, $runPayrollUser, $updatePayrollComponentDetail);
                } else {
                    $amount = self::calculatePayrollComponentPeriodType($payrollComponent, $amount, $totalWorkingDays, $runPayrollUser);
                }
                self::createComponent($runPayrollUser, $payrollComponent, $amount);
            });
            // END

            /**
             * third, calculate alpa
             */
            if ($user->payrollInfo?->is_ignore_alpa == false && !$isFirstTimePayroll && !$joinDate->between($cutOffStartDate, $cutOffEndDate)) {
                $alpaComponent = PayrollComponent::tenanted()
                    ->where('company_id', $runPayroll->company_id)
                    ->whenBranch($runPayroll->branch_id)
                    ->where('category', PayrollComponentCategory::ALPA)->first();

                if ($alpaComponent) {
                    $alpaUpdateComponent = $updatePayrollComponentDetails->where('payroll_component_id', $alpaComponent->id)->first();
                    if ($alpaUpdateComponent) {
                        $amount = $alpaUpdateComponent->new_amount;
                    } else {
                        // get total alpa di range tgl cuttoff
                        // potongan = (totalAlpa/totalHariKerja)*(basicSalary+SUM(allowance))
                        $totalWorkingDays = ScheduleService::getTotalWorkingDaysInPeriod($user, $cutOffStartDate, $cutOffEndDate);
                        $totalAlpa = AttendanceService::getTotalAlpa($user, $cutOffStartDate, $cutOffEndDate);
                        $totalAllowance = $runPayrollUser->components()->whereHas('payrollComponent', fn($q) => $q->where('type', PayrollComponentType::ALLOWANCE)->whereNotIn('category', [PayrollComponentCategory::BASIC_SALARY, PayrollComponentCategory::REIMBURSEMENT, PayrollComponentCategory::OVERTIME]))->sum('amount');
                        $amount = round(max(($totalAlpa / $totalWorkingDays) * ($userBasicSalary + $totalAllowance), 0));
                    }

                    $amount = self::calculatePayrollComponentPeriodType($alpaComponent, $amount, $totalWorkingDays, $runPayrollUser);
                    self::createComponent($runPayrollUser, $alpaComponent, $amount);
                }
            }
            // END

            /**
             * calculate LOAN
             */
            $loanComponent = PayrollComponent::tenanted()
                ->where('company_id', $runPayroll->company_id)
                ->whenBranch($runPayroll->branch_id)
                ->where('category', PayrollComponentCategory::LOAN)->first();

            if ($loanComponent) {
                $whereHas = fn($q) => $q->whereNull('run_payroll_user_id')->where('payment_period_year', $startDate->format('Y'))->where('payment_period_month', $startDate->format('m'));
                $loans = Loan::where('user_id', $user->id)->whereLoan()->whereHas('details', $whereHas)->get(['id']);
                if ($loans->count()) {
                    $loans->load(['details' => $whereHas]);
                    $amount = $loans->sum(fn($loan) => $loan->details->sum('total'));

                    self::createComponent($runPayrollUser, $loanComponent, $amount, [
                        "loans" => $loans->toArray()
                    ]);
                }
            }

            /**
             * calculate INSURANCE
             */
            $insuranceComponent = PayrollComponent::tenanted()
                ->where('company_id', $runPayroll->company_id)
                ->whenBranch($runPayroll->branch_id)
                ->where('category', PayrollComponentCategory::INSURANCE)->first();

            if ($insuranceComponent) {
                $whereHas = fn($q) => $q->whereNull('run_payroll_user_id')->where('payment_period_year', $startDate->format('Y'))->where('payment_period_month', $startDate->format('m'));
                $insurances = Loan::where('user_id', $user->id)->whereInsurance()->whereHas('details', $whereHas)->get(['id']);
                if ($insurances->count()) {
                    $insurances->load(['details' => $whereHas]);
                    $amount = $insurances->sum(fn($loan) => $loan->details->sum('total'));

                    // $insurances->each(fn($loan) => $loan->details->each->update(['run_payroll_user_id' => $runPayrollUser->id]));

                    self::createComponent($runPayrollUser, $insuranceComponent, $amount, [
                        "insurances" => $insurances->toArray()
                    ]);
                }
            }

            /**
             * fourth, calculate bpjs
             */
            if ($company->countryTable?->id == 1 && $user->userBpjs) {
                $bpjsPayrollComponents = PayrollComponent::tenanted()
                    ->whereCompany($runPayroll->company_id)
                    ->whenBranch($runPayroll->branch_id)
                    ->whereBpjs()->get();

                $isEligibleToCalculateBpjsKesehatan = false;
                if (
                    !empty($user->userBpjs->bpjs_kesehatan_no)
                    && !empty($user->userBpjs->bpjs_kesehatan_date)
                    && (
                        date('Y', strtotime($user->userBpjs->bpjs_kesehatan_date)) < date('Y', strtotime($runPayroll->payroll_start_date))
                        || (date('Y', strtotime($user->userBpjs->bpjs_kesehatan_date)) == date('Y', strtotime($runPayroll->cut_off_end_date)) && date('m', strtotime($user->userBpjs->bpjs_kesehatan_date)) <= date('m', strtotime($runPayroll->payroll_start_date)))
                    )
                ) {
                    $isEligibleToCalculateBpjsKesehatan = true;
                }

                $isEligibleToCalculateBpjsKetenagakerjaan = false;
                if (
                    !empty($user->userBpjs->bpjs_ketenagakerjaan_no)
                    && !empty($user->userBpjs->bpjs_ketenagakerjaan_date)
                    && (
                        date('Y', strtotime($user->userBpjs->bpjs_ketenagakerjaan_date)) < date('Y', strtotime($runPayroll->payroll_start_date))
                        || (date('Y', strtotime($user->userBpjs->bpjs_ketenagakerjaan_date)) == date('Y', strtotime($runPayroll->cut_off_end_date)) && date('m', strtotime($user->userBpjs->bpjs_ketenagakerjaan_date)) <= date('m', strtotime($runPayroll->payroll_start_date)))
                    )
                ) {
                    $isEligibleToCalculateBpjsKetenagakerjaan = true;
                }

                // calculate bpjs
                // init bpjs variable
                $current_upahBpjsKesehatan = $user->userBpjs->upah_bpjs_kesehatan;
                if ($current_upahBpjsKesehatan > $max_upahBpjsKesehatan) $current_upahBpjsKesehatan = $max_upahBpjsKesehatan;

                $current_upahBpjsKetenagakerjaan = $user->userBpjs->upah_bpjs_ketenagakerjaan;
                if ($current_upahBpjsKetenagakerjaan > $max_jp) $current_upahBpjsKetenagakerjaan = $max_jp;

                // bpjs kesehatan
                $company_percentageBpjsKesehatan = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_BPJS_KESEHATAN_PERCENTAGE)?->value;

                $employee_percentageBpjsKesehatan = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::EMPLOYEE_BPJS_KESEHATAN_PERCENTAGE)?->value;
                if (!$isTaxable) {
                    $company_percentageBpjsKesehatan += $employee_percentageBpjsKesehatan;
                    $employee_percentageBpjsKesehatan = 0;
                }

                $company_totalBpjsKesehatan = $current_upahBpjsKesehatan * ($company_percentageBpjsKesehatan / 100);

                $employee_totalBpjsKesehatan = $current_upahBpjsKesehatan * ($employee_percentageBpjsKesehatan / 100);
                if ($user->userBpjs->bpjs_kesehatan_cost->is(PaidBy::COMPANY)) {
                    $company_totalBpjsKesehatan += $employee_totalBpjsKesehatan;
                    $employee_totalBpjsKesehatan = 0;
                }

                // jkk
                $company_percentageJkk = $company->jkk_tier->getValue() ?? 0;
                $company_totalJkk = $user->userBpjs->upah_bpjs_ketenagakerjaan * ($company_percentageJkk / 100);

                // jkm
                $company_percentageJkm = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_JKM_PERCENTAGE)?->value;
                $company_totalJkm = $user->userBpjs->upah_bpjs_ketenagakerjaan * ($company_percentageJkm / 100);

                // jht
                $company_percentageJht = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_JHT_PERCENTAGE)?->value;
                $employee_percentageJht = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::EMPLOYEE_JHT_PERCENTAGE)?->value;
                if (!$isTaxable) {
                    $company_percentageJht += $employee_percentageJht;
                    $employee_percentageJht = 0;
                }

                $company_totalJht = $user->userBpjs->upah_bpjs_ketenagakerjaan * ($company_percentageJht / 100);
                $employee_totalJht = $user->userBpjs->upah_bpjs_ketenagakerjaan * ($employee_percentageJht / 100);
                if ($user->userBpjs->jht_cost->is(PaidBy::COMPANY)) {
                    $company_totalJht += $employee_totalJht;
                    $employee_totalJht = 0;
                }

                // jp
                $company_totalJp = 0;
                $employee_totalJp = 0;
                if (!$user->userBpjs->jaminan_pensiun_cost->is(JaminanPensiunCost::NOT_PAID)) {
                    $company_percentageJp = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_JP_PERCENTAGE)?->value;

                    $employee_percentageJp = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::EMPLOYEE_JP_PERCENTAGE)?->value;
                    if (!$isTaxable) {
                        $company_percentageJp += $employee_percentageJp;
                        $employee_percentageJp = 0;
                    }

                    $company_totalJp = $current_upahBpjsKetenagakerjaan * ($company_percentageJp / 100);

                    $employee_totalJp = $current_upahBpjsKetenagakerjaan * ($employee_percentageJp / 100);

                    if ($user->userBpjs->jaminan_pensiun_cost->is(JaminanPensiunCost::COMPANY)) {
                        $company_totalJp += $employee_totalJp;
                        $employee_totalJp = 0;
                    }
                }

                foreach ($bpjsPayrollComponents as $bpjsPayrollComponent) {
                    $amount = 0;
                    if ($isEligibleToCalculateBpjsKesehatan) {
                        if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_BPJS_KESEHATAN)) $amount = $company_totalBpjsKesehatan;

                        if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::EMPLOYEE_BPJS_KESEHATAN)) $amount = $employee_totalBpjsKesehatan;
                    }

                    if ($isEligibleToCalculateBpjsKetenagakerjaan) {
                        if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JKK)) $amount = $company_totalJkk;

                        if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JKM)) $amount = $company_totalJkm;

                        if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JHT)) $amount = $company_totalJht;
                        if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::EMPLOYEE_JHT)) $amount = $employee_totalJht;

                        if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JP)) $amount = $company_totalJp;
                        if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::EMPLOYEE_JP)) $amount = $employee_totalJp;
                    }

                    $amount = self::calculatePayrollComponentPeriodType($bpjsPayrollComponent, $amount, $totalWorkingDays, $runPayrollUser);

                    self::createComponent($runPayrollUser, $bpjsPayrollComponent, $amount);
                }
            }
            // END

            /**
             * five, calculate overtime
             */
            $overtimePayrollComponent = PayrollComponent::tenanted()
                ->whereCompany($runPayroll->company_id)
                ->whenBranch($runPayroll->branch_id)
                ->where('category', PayrollComponentCategory::OVERTIME)->first();

            $isUserOvertimeEligible = $user->payrollInfo->overtime_setting->is(OvertimeSetting::ELIGIBLE);

            if ($isUserOvertimeEligible && $overtimePayrollComponent) {
                $amount = OvertimeService::calculate($user, $cutOffStartDate, $cutOffEndDate, $userBasicSalary);

                self::createComponent($runPayrollUser, $overtimePayrollComponent, $amount);
            }
            // END

            /**
             * six, calculate task overtime
             */
            if (config('app.name') == 'SUNSHINE') {
                $taskOvertimePayrollComponent = PayrollComponent::tenanted()
                    ->whereCompany($runPayroll->company_id)
                    ->whenBranch($runPayroll->branch_id)
                    ->where('category', PayrollComponentCategory::TASK_OVERTIME)->first();

                if ($taskOvertimePayrollComponent) {
                    $amount = OvertimeService::calculateTaskOvertime($user, $cutOffStartDate, $cutOffEndDate);

                    self::createComponent($runPayrollUser, $taskOvertimePayrollComponent, $amount);
                }
            }
            // END

            /**
             * seven, calculate BPJS FAMILY
             */
            if ($user->userBpjs && !$user->userBpjs->bpjs_kesehatan_family_no->is(\App\Enums\BpjsKesehatanFamilyNo::ZERO)) {
                $bpjsKesehatanFamilyComponent = PayrollComponent::tenanted()
                    ->whereCompany($runPayroll->company_id)
                    ->whenBranch($runPayroll->branch_id)
                    ->where('category', PayrollComponentCategory::BPJS_KESEHATAN_FAMILY)->first();

                if ($bpjsKesehatanFamilyComponent) {
                    $current_upahBpjsKesehatan = $user->userBpjs->upah_bpjs_kesehatan;
                    if ($current_upahBpjsKesehatan > $max_upahBpjsKesehatan) $current_upahBpjsKesehatan = $max_upahBpjsKesehatan;

                    // one percent from current_upahBpjsKesehatan, dikali bpjs_kesehatan_family_no
                    $amount = ($current_upahBpjsKesehatan * 0.01) * $user->userBpjs->bpjs_kesehatan_family_no->value;

                    self::createComponent($runPayrollUser, $bpjsKesehatanFamilyComponent, $amount);
                }
            }
            // END

            // update total amount for each user
            self::refreshRunPayrollUser($runPayrollUser);
        }

        return response()->json([
            'success' => true,
            'data' => null,
        ]);
    }

    /**
     * create run payroll details
     *
     * @param  RunPayroll   $runPayroll
     * @param  string|int   $userId
     * @return RunPayrollUser
     */
    public static function assignUser(RunPayroll $runPayroll, string|int $userId): RunPayrollUser
    {
        return $runPayroll->users()->create(['user_id' => $userId]);
    }

    /**
     * create run payroll user components
     *
     * @param  RunPayrollUser   $runPayrollUser
     * @param  int              $payrollComponentId
     * @param  int|float        $amomunt
     * @param  bool             $isEditable
     * @return RunPayrollUserComponent
     */
    public static function createComponent(RunPayrollUser $runPayrollUser, PayrollComponent $payrollComponent, int|float $amount = 0, array|null $context = null, ?bool $isEditable = true): RunPayrollUserComponent
    {
        return $runPayrollUser->components()->create([
            'payroll_component_id' => $payrollComponent->id,
            'amount' => $amount,
            'is_editable' => $isEditable,
            'payroll_component' => $payrollComponent,
            'context' => $context,
        ]);
    }

    /**
     * Calculates the amount of a payroll component based on its period type.
     *
     * @param PayrollComponent $payrollComponent The payroll component to calculate.
     * @param int|float $amount The initial amount of the component. Default is 0.
     * @param int $cutoffDiffDay The number of days between the cutoff start and end dates. Default is 0.
     * @param RunPayrollUser|null $runPayrollUser The run payroll user associated with the component. Default is null.
     * @return int|float The calculated amount of the component.
     */
    public static function calculatePayrollComponentPeriodType(PayrollComponent $payrollComponent, int|float $amount = 0, int $cutoffDiffDay = 0, ?RunPayrollUser $runPayrollUser = null, ?UpdatePayrollComponentDetail $updatePayrollComponentDetail = null): int|float
    {
        if ($payrollComponent->category->is(PayrollComponentCategory::ALPA)) {
            return $amount;
        }

        switch ($payrollComponent->period_type) {
            case PayrollComponentPeriodType::DAILY:
                // rate_amount * cutoff diff days
                // if (!$payrollComponent->formulas) $amount = $amount * $cutoffDiffDay;
                $amount = $amount * $cutoffDiffDay;
                break;
            case PayrollComponentPeriodType::MONTHLY:
                $amount = $amount;

                break;
            case PayrollComponentPeriodType::ONE_TIME:
                $checkOneTime = $runPayrollUser->user->oneTimePayrollComponents()
                    ->where('payroll_component_id', $payrollComponent->id)
                    ->whereHas(
                        'runPayroll',
                        fn($q) => $q->release()
                            ->when($updatePayrollComponentDetail, function ($q) use ($updatePayrollComponentDetail) {
                                $startDate = $updatePayrollComponentDetail->updatePayrollComponent->effective_date;
                                $endDate = $updatePayrollComponentDetail->updatePayrollComponent->end_date ?? null;
                                return $q->when(
                                    !$endDate,
                                    function ($q) use ($startDate) {
                                        return $q->whereDate('payroll_start_date', '>', $startDate);
                                    },
                                    function ($q) use ($startDate, $endDate) {
                                        return $q->whereDate('payroll_start_date', '>=', $startDate)
                                            ->whereDate('payroll_start_date', '<=', $endDate);
                                    }
                                );
                            })
                    )
                    ->exists();

                if ($checkOneTime) {
                    $amount = 0;
                } else {
                    $runPayrollUser->user->oneTimePayrollComponents()->create([
                        'payroll_component_id' => $payrollComponent->id,
                        'run_payroll_id' => $runPayrollUser->run_payroll_id,
                    ]);
                    $amount = $amount;
                }

                break;
            default:
                //

                break;
        }

        return $amount;
    }

    public static function refreshRunPayrollUser(RunPayrollUser|int $runPayrollUser)
    {
        if (!$runPayrollUser instanceof RunPayrollUser) {
            $runPayrollUser = RunPayrollUser::findOrFail($runPayrollUser);
        }

        $basicSalary = $runPayrollUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('category', PayrollComponentCategory::BASIC_SALARY);
            // $q->where('is_calculateable', true);
        })->sum('amount');

        $allowanceTaxable = $runPayrollUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('type', PayrollComponentType::ALLOWANCE);
            $q->whereNotIn('category', [PayrollComponentCategory::BASIC_SALARY]);
            $q->where('is_taxable', true);
        })->sum('amount');

        $allowanceNonTaxable = $runPayrollUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('type', PayrollComponentType::ALLOWANCE);
            $q->whereNotIn('category', [PayrollComponentCategory::BASIC_SALARY]);
            $q->where('is_taxable', false);
        })->sum('amount');

        $additionalEarning = 0; // belum kepake

        $benefit = $runPayrollUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('type', PayrollComponentType::BENEFIT);
            $q->whereNotIn('category', [PayrollComponentCategory::COMPANY_JHT, PayrollComponentCategory::COMPANY_JP]);
            // $q->where('is_calculateable', true);
        })->sum('amount');

        $deduction = $runPayrollUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('type', PayrollComponentType::DEDUCTION);
            // $q->where('is_calculateable', true);
        })->sum('amount');

        $grossSalary = $basicSalary + $allowanceTaxable + $additionalEarning + $benefit;

        $tax = 0;
        $userPayrollInfo = $runPayrollUser->user->payrollInfo;
        if ($userPayrollInfo->tax_salary->is(TaxSalary::TAXABLE)) {
            $taxPercentage = self::calculateTax($runPayrollUser->user->payrollInfo->ptkp_status, $grossSalary);
            if ($userPayrollInfo->tax_method->is(TaxMethod::GROSS_UP)) {
                $grossUp1 = floatval(100 - $taxPercentage);
                $grossSalary2 = ($grossSalary / $grossUp1) * 100;

                $taxPercentage = self::calculateTax($runPayrollUser->user->payrollInfo->ptkp_status, $grossSalary2);

                $tax = $grossSalary2 * ($taxPercentage / 100);
            } else {
                $tax = $grossSalary * ($taxPercentage / 100);
            }
        }

        $runPayrollUser->update([
            'basic_salary' => $basicSalary,
            'gross_salary' => $grossSalary,
            'allowance' => $allowanceTaxable + $allowanceNonTaxable,
            'additional_earning' => $additionalEarning,
            'deduction' => $deduction,
            'benefit' => $benefit,
            'tax' => floor($tax),
            'payroll_info' => $userPayrollInfo,
        ]);
    }

    public static function calculateTax(PtkpStatus $ptkpStatus, float $grossSalary): float
    {
        $taxPercentage = 0;

        if (in_array($ptkpStatus, [PtkpStatus::TK_0, PtkpStatus::TK_1, PtkpStatus::K_0])) {
            if ($grossSalary <= 5400000) {
                $taxPercentage = 0;
            } else if ($grossSalary > 5400000 && $grossSalary <= 5650000) {
                $taxPercentage = 0.25;
            } else if ($grossSalary > 5650000 && $grossSalary <= 5950000) {
                $taxPercentage = 0.5;
            } else if ($grossSalary > 5950000 && $grossSalary <= 6300000) {
                $taxPercentage = 0.75;
            } else if ($grossSalary > 6300000 && $grossSalary <= 6750000) {
                $taxPercentage = 1;
            } else if ($grossSalary > 6750000 && $grossSalary <= 7500000) {
                $taxPercentage = 1.25;
            } else if ($grossSalary > 7500000 && $grossSalary <= 8550000) {
                $taxPercentage = 1.5;
            } else if ($grossSalary > 8550000 && $grossSalary <= 9650000) {
                $taxPercentage = 1.75;
            } else if ($grossSalary > 9650000 && $grossSalary <= 10050000) {
                $taxPercentage = 2;
            } else if ($grossSalary > 10050000 && $grossSalary <= 10350000) {
                $taxPercentage = 2.25;
            } else if ($grossSalary > 10350000 && $grossSalary <= 10700000) {
                $taxPercentage = 2.5;
            } else if ($grossSalary > 10700000 && $grossSalary <= 11050000) {
                $taxPercentage = 3;
            } else if ($grossSalary > 11050000 && $grossSalary <= 11600000) {
                $taxPercentage = 3.5;
            } else if ($grossSalary > 11600000 && $grossSalary <= 12500000) {
                $taxPercentage = 4;
            } else if ($grossSalary > 12500000 && $grossSalary <= 13750000) {
                $taxPercentage = 5;
            } else if ($grossSalary > 13750000 && $grossSalary <= 15100000) {
                $taxPercentage = 6;
            } else if ($grossSalary > 15100000 && $grossSalary <= 16950000) {
                $taxPercentage = 7;
            } else if ($grossSalary > 16950000 && $grossSalary <= 19750000) {
                $taxPercentage = 8;
            } else if ($grossSalary > 19750000 && $grossSalary <= 24150000) {
                $taxPercentage = 9;
            } else if ($grossSalary > 24150000 && $grossSalary <= 26450000) {
                $taxPercentage = 10;
            } else if ($grossSalary > 26450000 && $grossSalary <= 28000000) {
                $taxPercentage = 11;
            } else if ($grossSalary > 28000000 && $grossSalary <= 30050000) {
                $taxPercentage = 12;
            } else if ($grossSalary > 30050000 && $grossSalary <= 32400000) {
                $taxPercentage = 13;
            } else if ($grossSalary > 32400000 && $grossSalary <= 35400000) {
                $taxPercentage = 14;
            } else if ($grossSalary > 35400000 && $grossSalary <= 39100000) {
                $taxPercentage = 15;
            } else if ($grossSalary > 39100000 && $grossSalary <= 43850000) {
                $taxPercentage = 16;
            } else if ($grossSalary > 43850000 && $grossSalary <= 47800000) {
                $taxPercentage = 17;
            } else if ($grossSalary > 47800000 && $grossSalary <= 51400000) {
                $taxPercentage = 18;
            } else if ($grossSalary > 51400000 && $grossSalary <= 56300000) {
                $taxPercentage = 19;
            } else if ($grossSalary > 56300000 && $grossSalary <= 62200000) {
                $taxPercentage = 20;
            } else if ($grossSalary > 62200000 && $grossSalary <= 68600000) {
                $taxPercentage = 21;
            } else if ($grossSalary > 68600000 && $grossSalary <= 77500000) {
                $taxPercentage = 22;
            } else if ($grossSalary > 77500000 && $grossSalary <= 89000000) {
                $taxPercentage = 23;
            } else if ($grossSalary > 89000000 && $grossSalary <= 103000000) {
                $taxPercentage = 24;
            } else if ($grossSalary > 103000000 && $grossSalary <= 125000000) {
                $taxPercentage = 25;
            } else if ($grossSalary > 125000000 && $grossSalary <= 157000000) {
                $taxPercentage = 26;
            } else if ($grossSalary > 157000000 && $grossSalary <= 206000000) {
                $taxPercentage = 27;
            } else if ($grossSalary > 206000000 && $grossSalary <= 337000000) {
                $taxPercentage = 28;
            } else if ($grossSalary > 337000000 && $grossSalary <= 454000000) {
                $taxPercentage = 29;
            } else if ($grossSalary > 454000000 && $grossSalary <= 550000000) {
                $taxPercentage = 30;
            } else if ($grossSalary > 550000000 && $grossSalary <= 695000000) {
                $taxPercentage = 31;
            } else if ($grossSalary > 695000000 && $grossSalary <= 910000000) {
                $taxPercentage = 32;
            } else if ($grossSalary > 910000000 && $grossSalary <= 1400000000) {
                $taxPercentage = 33;
            } else if ($grossSalary > 1400000000) {
                $taxPercentage = 34;
            } else {
                $taxPercentage = 0;
            }
        }

        if (in_array($ptkpStatus, [PtkpStatus::TK_2, PtkpStatus::TK_3, PtkpStatus::K_1, PtkpStatus::K_2])) {
            if ($grossSalary <= 6200000) {
                $taxPercentage = 0;
            } else if ($grossSalary > 6200000 && $grossSalary <= 6500000) {
                $taxPercentage = 0.25;
            } else if ($grossSalary > 6500000 && $grossSalary <= 6850000) {
                $taxPercentage = 0.5;
            } else if ($grossSalary > 6850000 && $grossSalary <= 7300000) {
                $taxPercentage = 0.75;
            } else if ($grossSalary > 7300000 && $grossSalary <= 9200000) {
                $taxPercentage = 1;
            } else if ($grossSalary > 9200000 && $grossSalary <= 10750000) {
                $taxPercentage = 1.5;
            } else if ($grossSalary > 10750000 && $grossSalary <= 11250000) {
                $taxPercentage = 2;
            } else if ($grossSalary > 11250000 && $grossSalary <= 11600000) {
                $taxPercentage = 2.5;
            } else if ($grossSalary > 11600000 && $grossSalary <= 12600000) {
                $taxPercentage = 3;
            } else if ($grossSalary > 12600000 && $grossSalary <= 13600000) {
                $taxPercentage = 4;
            } else if ($grossSalary > 13600000 && $grossSalary <= 14950000) {
                $taxPercentage = 5;
            } else if ($grossSalary > 14950000 && $grossSalary <= 16400000) {
                $taxPercentage = 6;
            } else if ($grossSalary > 16400000 && $grossSalary <= 18450000) {
                $taxPercentage = 7;
            } else if ($grossSalary > 18450000 && $grossSalary <= 21850000) {
                $taxPercentage = 8;
            } else if ($grossSalary > 21850000 && $grossSalary <= 26000000) {
                $taxPercentage = 9;
            } else if ($grossSalary > 26000000 && $grossSalary <= 27700000) {
                $taxPercentage = 10;
            } else if ($grossSalary > 27700000 && $grossSalary <= 29350000) {
                $taxPercentage = 11;
            } else if ($grossSalary > 29350000 && $grossSalary <= 31450000) {
                $taxPercentage = 12;
            } else if ($grossSalary > 31450000 && $grossSalary <= 33950000) {
                $taxPercentage = 13;
            } else if ($grossSalary > 33950000 && $grossSalary <= 37100000) {
                $taxPercentage = 14;
            } else if ($grossSalary > 37100000 && $grossSalary <= 41100000) {
                $taxPercentage = 15;
            } else if ($grossSalary > 41100000 && $grossSalary <= 45800000) {
                $taxPercentage = 16;
            } else if ($grossSalary > 45800000 && $grossSalary <= 49500000) {
                $taxPercentage = 17;
            } else if ($grossSalary > 49500000 && $grossSalary <= 53800000) {
                $taxPercentage = 18;
            } else if ($grossSalary > 53800000 && $grossSalary <= 58500000) {
                $taxPercentage = 19;
            } else if ($grossSalary > 58500000 && $grossSalary <= 64000000) {
                $taxPercentage = 20;
            } else if ($grossSalary > 64000000 && $grossSalary <= 71000000) {
                $taxPercentage = 21;
            } else if ($grossSalary > 71000000 && $grossSalary <= 80000000) {
                $taxPercentage = 22;
            } else if ($grossSalary > 80000000 && $grossSalary <= 93000000) {
                $taxPercentage = 23;
            } else if ($grossSalary > 93000000 && $grossSalary <= 109000000) {
                $taxPercentage = 24;
            } else if ($grossSalary > 109000000 && $grossSalary <= 129000000) {
                $taxPercentage = 25;
            } else if ($grossSalary > 129000000 && $grossSalary <= 163000000) {
                $taxPercentage = 26;
            } else if ($grossSalary > 163000000 && $grossSalary <= 211000000) {
                $taxPercentage = 27;
            } else if ($grossSalary > 211000000 && $grossSalary <= 374000000) {
                $taxPercentage = 28;
            } else if ($grossSalary > 374000000 && $grossSalary <= 459000000) {
                $taxPercentage = 29;
            } else if ($grossSalary > 459000000 && $grossSalary <= 555000000) {
                $taxPercentage = 30;
            } else if ($grossSalary > 555000000 && $grossSalary <= 704000000) {
                $taxPercentage = 31;
            } else if ($grossSalary > 704000000 && $grossSalary <= 957000000) {
                $taxPercentage = 32;
            } else if ($grossSalary > 957000000 && $grossSalary <= 1405000000) {
                $taxPercentage = 33;
            } else if ($grossSalary > 1405000000) {
                $taxPercentage = 34;
            } else {
                $taxPercentage = 0;
            }
        }

        if (in_array($ptkpStatus, [PtkpStatus::K_3])) {
            if ($grossSalary <= 6600000) {
                $taxPercentage = 0;
            } else if ($grossSalary > 6600000 && $grossSalary <= 6950000) {
                $taxPercentage = 0.25;
            } else if ($grossSalary > 6950000 && $grossSalary <= 7350000) {
                $taxPercentage = 0.5;
            } else if ($grossSalary > 7350000 && $grossSalary <= 7800000) {
                $taxPercentage = 0.75;
            } else if ($grossSalary > 7800000 && $grossSalary <= 8850000) {
                $taxPercentage = 1;
            } else if ($grossSalary > 8850000 && $grossSalary <= 9800000) {
                $taxPercentage = 1.25;
            } else if ($grossSalary > 9800000 && $grossSalary <= 10950000) {
                $taxPercentage = 1.5;
            } else if ($grossSalary > 10950000 && $grossSalary <= 11200000) {
                $taxPercentage = 1.75;
            } else if ($grossSalary > 11200000 && $grossSalary <= 12050000) {
                $taxPercentage = 2;
            } else if ($grossSalary > 12050000 && $grossSalary <= 12950000) {
                $taxPercentage = 3;
            } else if ($grossSalary > 12950000 && $grossSalary <= 14150000) {
                $taxPercentage = 4;
            } else if ($grossSalary > 14150000 && $grossSalary <= 15550000) {
                $taxPercentage = 5;
            } else if ($grossSalary > 15550000 && $grossSalary <= 17050000) {
                $taxPercentage = 6;
            } else if ($grossSalary > 17050000 && $grossSalary <= 19500000) {
                $taxPercentage = 7;
            } else if ($grossSalary > 19500000 && $grossSalary <= 22700000) {
                $taxPercentage = 8;
            } else if ($grossSalary > 22700000 && $grossSalary <= 26600000) {
                $taxPercentage = 9;
            } else if ($grossSalary > 26600000 && $grossSalary <= 28100000) {
                $taxPercentage = 10;
            } else if ($grossSalary > 28100000 && $grossSalary <= 30100000) {
                $taxPercentage = 11;
            } else if ($grossSalary > 30100000 && $grossSalary <= 32600000) {
                $taxPercentage = 12;
            } else if ($grossSalary > 32600000 && $grossSalary <= 35400000) {
                $taxPercentage = 13;
            } else if ($grossSalary > 35400000 && $grossSalary <= 38900000) {
                $taxPercentage = 14;
            } else if ($grossSalary > 38900000 && $grossSalary <= 43000000) {
                $taxPercentage = 15;
            } else if ($grossSalary > 43000000 && $grossSalary <= 47400000) {
                $taxPercentage = 16;
            } else if ($grossSalary > 47400000 && $grossSalary <= 51200000) {
                $taxPercentage = 17;
            } else if ($grossSalary > 51200000 && $grossSalary <= 55800000) {
                $taxPercentage = 18;
            } else if ($grossSalary > 55800000 && $grossSalary <= 60400000) {
                $taxPercentage = 19;
            } else if ($grossSalary > 60400000 && $grossSalary <= 66700000) {
                $taxPercentage = 20;
            } else if ($grossSalary > 66700000 && $grossSalary <= 74500000) {
                $taxPercentage = 21;
            } else if ($grossSalary > 74500000 && $grossSalary <= 83200000) {
                $taxPercentage = 22;
            } else if ($grossSalary > 83200000 && $grossSalary <= 95600000) {
                $taxPercentage = 23;
            } else if ($grossSalary > 95600000 && $grossSalary <= 110000000) {
                $taxPercentage = 24;
            } else if ($grossSalary > 110000000 && $grossSalary <= 134000000) {
                $taxPercentage = 25;
            } else if ($grossSalary > 134000000 && $grossSalary <= 169000000) {
                $taxPercentage = 26;
            } else if ($grossSalary > 169000000 && $grossSalary <= 221000000) {
                $taxPercentage = 27;
            } else if ($grossSalary > 221000000 && $grossSalary <= 390000000) {
                $taxPercentage = 28;
            } else if ($grossSalary > 390000000 && $grossSalary <= 463000000) {
                $taxPercentage = 29;
            } else if ($grossSalary > 463000000 && $grossSalary <= 561000000) {
                $taxPercentage = 30;
            } else if ($grossSalary > 561000000 && $grossSalary <= 709000000) {
                $taxPercentage = 31;
            } else if ($grossSalary > 709000000 && $grossSalary <= 965000000) {
                $taxPercentage = 32;
            } else if ($grossSalary > 965000000 && $grossSalary <= 1419000000) {
                $taxPercentage = 33;
            } else if ($grossSalary > 1419000000) {
                $taxPercentage = 34;
            } else {
                $taxPercentage = 0;
            }
        }

        return $taxPercentage;
    }

    public static function isFirstTimePayroll(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;
        return RunPayrollUser::query()->where('user_id', $userId)
            ->whereHas('runPayroll', fn($q) => $q->release())
            ->limit(1)
            ->doesntExist();
    }
}
