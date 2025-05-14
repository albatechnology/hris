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
        @php
            $bankId = null;
            $totalBasicSalary = 0;
            $totalAllowance = 0;
            $totalDeduction = 0;
            $totalTax = 0;
            $totalThp = 0;
            $totalBenefit = 0;
        @endphp
        @foreach ($runPayroll->users->sortBy('user.payrollInfo.bank.account_holder') as $runPayrollUser)
            @php
                $bankId = $runPayrollUser->user?->payrollInfo?->bank?->id ?? null;
                $totalBasicSalary += $runPayrollUser->basic_salary;
                $totalAllowance += $runPayrollUser->allowance;
                $totalDeduction += $runPayrollUser->deduction;
                $totalTax += $runPayrollUser->tax;
                $totalThp += $runPayrollUser->thp;
                $totalBenefit += $runPayrollUser->benefit;
            @endphp
            <tr>
                <td>{{ $runPayrollUser->user?->nik ?? '' }}</td>
                <td>{{ $runPayrollUser->user?->name ?? '' }}</td>
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
                    <th>{{ $runPayrollUser->components->firstWhere('payroll_component_id', $allowance->id)?->amount }}
                    </th>
                @endforeach
                <td>{{ $runPayrollUser->allowance }}</td>

                @foreach ($deductions as $deduction)
                    <th>{{ $runPayrollUser->components->firstWhere('payroll_component_id', $deduction->id)?->amount }}
                    </th>
                @endforeach
                <td>{{ $runPayrollUser->deduction }}</td>

                <td>{{ $runPayrollUser->tax }}</td>
                <td>{{ $runPayrollUser->thp }}</td>

                @foreach ($benefits as $benefit)
                    <th>{{ $runPayrollUser->components->firstWhere('payroll_component_id', $benefit->id)?->amount }}
                    </th>
                @endforeach
                <td>{{ $runPayrollUser->benefit }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
