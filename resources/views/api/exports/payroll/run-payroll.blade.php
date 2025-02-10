<table>
    <thead>
        <tr>
            <th style="text-align: center; font-weight: bold" rowspan="2">Employee ID</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Full Name</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Organization</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Branch</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Join Date</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Resign Date</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Job Position</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">OCBC Account Holder</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">OCBC Account</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">BCA Account Holder</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">BCA Account</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Source Bank Name</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Source Bank Account Holder</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Source Bank Account</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Source Bank Code</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Basic Salary</th>
            <th style="text-align: center; font-weight: bold" colspan="{{ $allowances->count() }}">Allowance</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Total Allowance</th>
            <th style="text-align: center; font-weight: bold" colspan="{{ $deductions->count() }}">Deduction</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Total Deduction</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">PPH 21 Payment</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Take Home Pay</th>
            <th style="text-align: center; font-weight: bold" colspan="{{ $benefits->count() }}">Benefit</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Total Benefit</th>
        </tr>
        <tr>
            @foreach ($allowances as $allowance)
                <th>{{ $allowance->name }}</th>
            @endforeach

            @foreach ($deductions as $deduction)
                <th>{{ $deduction->name }}</th>
            @endforeach

            @foreach ($benefits as $benefit)
                <th>{{ $benefit->name }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($runPayrollUsersGroups as $group)
            @php
                $totalBasicSalary = 0;
                $totalAllowance = 0;
                $totalDeduction = 0;
                $totalTax = 0;
                $totalThp = 0;
                $totalBenefit = 0;
                $cloneTotalAllowancesStorages = $totalAllowancesStorages;
                $cloneTotalDeductionsStorages = $totalDeductionsStorages;
                $cloneTotalBenefitsStorages = $totalBenefitsStorages;
            @endphp
            @foreach ($group as $runPayrollUser)
                @php
                    $totalBasicSalary += $runPayrollUser->basic_salary;
                    $totalAllowance += $runPayrollUser->allowance;
                    $totalDeduction += $runPayrollUser->deduction;
                    $totalTax += $runPayrollUser->tax;
                    $totalThp += $runPayrollUser->thp;
                    $totalBenefit += $runPayrollUser->benefit;
                @endphp
                <tr>
                    <td>{{ $runPayrollUser->user?->nik ?? '' }}</td>
                    <td>{{ $runPayrollUser->user?->full_name ?? '' }}</td>
                    <td>{{ $runPayroll->company?->name }}</td>
                    <td>{{ $runPayrollUser->user?->branch?->name }}</td>
                    <td>{{ $runPayrollUser->user?->join_date ? date('d-M-Y', strtotime($runPayrollUser->user->join_date)) : '' }}
                    </td>
                    <td>{{ $runPayrollUser->user?->resign_date ? date('d-M-Y', strtotime($runPayrollUser->user->resign_date)) : '' }}
                    </td>
                    <td>{{ $runPayrollUser->user?->positions
                        ?->map(function ($position) {
                            return $position->department->name . ' / ' . $position->position->name;
                        })
                        ?->implode(', ') }}
                    </td>
                    <td>{{ $runPayrollUser->user?->payrollInfo?->bank_account_holder ?? '' }}</td>
                    <td>{{ $runPayrollUser->user?->payrollInfo?->bank_account_no ?? '' }}</td>
                    <td>{{ $runPayrollUser->user?->payrollInfo?->secondary_bank_account_holder ?? '' }}</td>
                    <td>{{ $runPayrollUser->user?->payrollInfo?->secondary_bank_account_no ?? '' }}</td>
                    <td>{{ $runPayrollUser->user?->payrollInfo?->bank?->name ?? '' }}</td>
                    <td>{{ $runPayrollUser->user?->payrollInfo?->bank?->account_holder ?? '' }}</td>
                    <td>{{ $runPayrollUser->user?->payrollInfo?->bank?->account_no ?? '' }}</td>
                    <td>{{ $runPayrollUser->user?->payrollInfo?->bank?->code ?? '' }}</td>
                    <td>{{ $runPayrollUser->basic_salary }}</td>

                    @foreach ($allowances as $allowance)
                        @php
                            $amount = $runPayrollUser->components?->firstWhere('payroll_component_id', $allowance->id)?->amount ?? 0;
                            $cloneTotalAllowancesStorages[$allowance->id] += $amount;
                        @endphp
                        <th>{{ $amount }}</th>
                    @endforeach
                    <td>{{ $runPayrollUser->allowance }}</td>

                    @foreach ($deductions as $deduction)
                        @php
                            $amount = $runPayrollUser->components?->firstWhere('payroll_component_id', $deduction->id)?->amount ?? 0;
                            $cloneTotalDeductionsStorages[$deduction->id] += $amount;
                        @endphp
                        <th>{{ $amount }}</th>
                    @endforeach
                    <td>{{ $runPayrollUser->deduction }}</td>

                    <td>{{ $runPayrollUser->tax }}</td>
                    <td>{{ $runPayrollUser->thp }}</td>

                    @foreach ($benefits as $benefit)
                        @php
                            $amount = $runPayrollUser->components?->firstWhere('payroll_component_id', $benefit->id)?->amount ?? 0;
                            $cloneTotalBenefitsStorages[$benefit->id] += $amount;
                        @endphp
                        <th>{{ $amount }}</th>
                    @endforeach
                    <td>{{ $runPayrollUser->benefit }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="15" style="font-weight: bold; background: #ffcbb1;">TOTAL</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalBasicSalary }}</td>

                @foreach ($cloneTotalAllowancesStorages as $value)
                    <th style="font-weight: bold; background: #ffcbb1;">{{ $value }}</th>
                @endforeach
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalAllowance }}</td>

                @foreach ($cloneTotalDeductionsStorages as $value)
                    <th style="font-weight: bold; background: #ffcbb1;">{{ $value }}</th>
                @endforeach
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalDeduction }}</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalTax }}</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalThp }}</td>

                @foreach ($cloneTotalBenefitsStorages as $value)
                    <th style="font-weight: bold; background: #ffcbb1;">{{ $value }}</th>
                @endforeach
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalBenefit }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
