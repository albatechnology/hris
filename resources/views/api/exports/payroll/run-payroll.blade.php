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
            <th style="text-align: center; font-weight: bold" rowspan="2">Basic Salary</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Gross Salary</th>
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
        @foreach ($runPayroll->users as $runPayrollUser)
            <tr>
                <td>{{ $runPayrollUser->user->nik }}</td>
                <td>{{ $runPayrollUser->user->full_name }}</td>
                <td>{{ $runPayroll->company?->name }}</td>
                <td>{{ $runPayrollUser->user->branch?->name }}</td>
                <td>{{ $runPayrollUser->user->join_date ? date('d-M-Y', strtotime($runPayrollUser->user->join_date)) : '' }}</td>
                <td>{{ $runPayrollUser->user->resign_date ? date('d-M-Y', strtotime($runPayrollUser->user->resign_date)) : '' }}</td>
                <td>{{ $runPayrollUser->user->positions?->implode(', ') }}</td>
                <td>{{ $runPayrollUser->basic_salary }}</td>
                <td>{{ $runPayrollUser->gross_salary }}</td>

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
