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
            <th style="text-align: center; font-weight: bold" rowspan="2">THR Pro Rate</th>
            {{-- <th style="text-align: center; font-weight: bold" colspan="{{ $allowances->count() }}">Allowance</th> --}}
            {{-- <th style="text-align: center; font-weight: bold" rowspan="2">Total Allowance</th> --}}
            <th style="text-align: center; font-weight: bold" colspan="{{ $deductions->count() }}">Deduction
                {{ date('M-Y', strtotime($runThr->thr_date)) }}</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Total Deduction</th>
            <th style="text-align: center; font-weight: bold" colspan="{{ $benefits->count() }}">Benefit
                {{ date('M-Y', strtotime($runThr->thr_date)) }}</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Total Benefit</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">PPH 21
                {{ date('M-Y', strtotime($runThr->thr_date)) }}</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Total
                {{ date('M-Y', strtotime($runThr->thr_date)) }}</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Total Beban
                {{ date('M-Y', strtotime($runThr->thr_date)) }}</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">Total PPH 21
                {{ date('M-Y', strtotime($runThr->thr_date)) }}</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">PPH 21 THR</th>
            <th style="text-align: center; font-weight: bold" rowspan="2">THP THR</th>
        </tr>
        <tr>
            {{-- @foreach ($allowances as $allowance)
                <th>{{ $allowance->name }}</th>
            @endforeach --}}

            @foreach ($deductions as $deduction)
                <th>{{ $deduction->name }}</th>
            @endforeach

            @foreach ($benefits as $benefit)
                <th>{{ $benefit->name }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        <tr>
            <th colspan="{{ $totalColumns }}" style="font-weight: bold; background-color: yellow">Active Users</th>
        </tr>
        @foreach ($activeUsers as $group)
            @php
                $totalBasicSalary = 0;
                $totalThrProrate = 0;
                // $totalAllowance = 0;
                $totalDeduction = 0;
                $totalTax = 0;
                $totalThp = 0;
                $totalBenefit = 0;
                $totalBebanMonth = 0;
                $totalTaxMonth = 0;
                $taxThr = 0;
                $thpThr = 0;

                // $cloneTotalAllowancesStorages = $totalAllowancesStorages;
                $cloneTotalDeductionsStorages = $totalDeductionsStorages;
                $cloneTotalBenefitsStorages = $totalBenefitsStorages;
            @endphp
            @foreach ($group as $runThrUser)
                @php
                    $totalThrProrate += $runThrUser->thr_prorate;
                    // $totalBasicSalary += $runThrUser->basic_salary;
                     $totalBasicSalary += $runThrUser->base_salary_original;
                    // $totalAllowance += $runThrUser->allowance;
                    $totalDeduction += $runThrUser->deduction;
                    $totalTax += $runThrUser->tax;
                    $totalThp += $runThrUser->total_month;
                    $totalBenefit += $runThrUser->benefit;
                    $totalBebanMonth += $runThrUser->total_beban_month;
                    $totalTaxMonth += $runThrUser->total_tax_month;
                    $taxThr += $runThrUser->tax_thr;
                    $thpThr += $runThrUser->thp_thr;
                @endphp
                <tr>
                    <td>{{ $runThrUser->user?->nik ?? '' }}</td>
                    <td>{{ $runThrUser->user?->name ?? '' }}</td>
                    <td>{{ $runThr->company?->name }}</td>
                    <td>{{ $runThrUser->user?->branch?->name }}</td>
                    <td>{{ $runThrUser->user?->join_date ? date('d-M-Y', strtotime($runThrUser->user->join_date)) : '' }}
                    </td>
                    <td>{{ $runThrUser->user?->resign_date ? date('d-M-Y', strtotime($runThrUser->user->resign_date)) : '' }}
                    </td>
                    <td>{{ $runThrUser->user?->positions
                        ?->map(function ($position) {
                            return ($position->department?->name ?? "") . ' / ' . ($position->position?->name ?? "");
                        })
                        ?->implode(', ') }}
                    </td>
                    <td>{{ $runThrUser->user?->payrollInfo?->bank_account_holder ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->bank_account_no ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->secondary_bank_account_holder ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->secondary_bank_account_no ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->bank?->name ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->bank?->account_holder ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->bank?->account_no ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->bank?->code ?? '' }}</td>
                    <td>{{ $runThrUser->basic_salary }}</td>
                    <td>{{ $runThrUser->thr_prorate }}</td>

                    {{-- @foreach ($allowances as $allowance)
                        @php
                            $amount =
                                $runThrUser->components?->firstWhere('payroll_component_id', $allowance->id)
                                    ?->amount ?? 0;
                            $cloneTotalAllowancesStorages[$allowance->id] += $amount;
                        @endphp
                        <th>{{ $amount }}</th>
                    @endforeach
                    <td>{{ $runThrUser->allowance }}</td> --}}

                    @foreach ($deductions as $deduction)
                        @php
                            $amount =
                                $runThrUser->components?->firstWhere('payroll_component_id', $deduction->id)?->amount ??
                                0;
                            $cloneTotalDeductionsStorages[$deduction->id] += $amount;
                        @endphp
                        <th>{{ $amount }}</th>
                    @endforeach
                    <td>{{ $runThrUser->deduction }}</td>

                    @foreach ($benefits as $benefit)
                        @php
                            $amount =
                                $runThrUser->components?->firstWhere('payroll_component_id', $benefit->id)?->amount ??
                                0;
                            $cloneTotalBenefitsStorages[$benefit->id] += $amount;
                        @endphp
                        <th>{{ $amount }}</th>
                    @endforeach
                    <td>{{ $runThrUser->benefit }}</td>

                    <td>{{ $runThrUser->tax }}</td>
                    <td>{{ $runThrUser->total_month }}</td>
                    <td>{{ $runThrUser->total_beban_month }}</td>
                    <td>{{ $runThrUser->total_tax_month }}</td>
                    <td>{{ $runThrUser->tax_thr }}</td>
                    <td>{{ $runThrUser->thp_thr }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="15" style="font-weight: bold; background: #ffcbb1;">TOTAL</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalBasicSalary }}</td>

                {{-- @foreach ($cloneTotalAllowancesStorages as $value)
                    <th style="font-weight: bold; background: #ffcbb1;">{{ $value }}</th>
                @endforeach
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalAllowance }}</td> --}}

                @foreach ($cloneTotalDeductionsStorages as $value)
                    <th style="font-weight: bold; background: #ffcbb1;">{{ $value }}</th>
                @endforeach
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalDeduction }}</td>

                @foreach ($cloneTotalBenefitsStorages as $value)
                    <th style="font-weight: bold; background: #ffcbb1;">{{ $value }}</th>
                @endforeach
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalBenefit }}</td>

                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalTax }}</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalThp }}</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalBebanMonth }}</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalTaxMonth }}</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $taxThr }}</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $thpThr }}</td>
            </tr>
        @endforeach

        <tr>
            <th colspan="{{ $totalColumns }}"></th>
        <tr>
            <th colspan="{{ $totalColumns }}"></th>
        </tr>
        <tr>
            <th colspan="{{ $totalColumns }}" style="font-weight: bold; background-color: yellow">Resign Users</th>
        </tr>
        @foreach ($resignUsers as $group)
            @php
                $totalBasicSalary = 0;
                $totalThrProrate = 0;
                // $totalAllowance = 0;
                $totalDeduction = 0;
                $totalTax = 0;
                $totalThp = 0;
                $totalBenefit = 0;
                $totalBebanMonth = 0;
                $totalTaxMonth = 0;
                $taxThr = 0;
                $thpThr = 0;

                // $cloneTotalAllowancesStorages = $totalAllowancesStorages;
                $cloneTotalDeductionsStorages = $totalDeductionsStorages;
                $cloneTotalBenefitsStorages = $totalBenefitsStorages;
            @endphp
            @foreach ($group as $runThrUser)
                @php
                    // $totalBasicSalary += $runThrUser->thr_prorate;
                    $totalBasicSalary += $runThrUser->basic_salary;
                    // $totalAllowance += $runThrUser->allowance;
                    $totalDeduction += $runThrUser->deduction;
                    $totalTax += $runThrUser->tax;
                    $totalThp += $runThrUser->total_month;
                    $totalBenefit += $runThrUser->benefit;
                    $totalBebanMonth += $runThrUser->total_beban_month;
                    $totalTaxMonth += $runThrUser->total_tax_month;
                    $taxThr += $runThrUser->tax_thr;
                    $thpThr += $runThrUser->thp_thr;
                @endphp
                <tr>
                    <td>{{ $runThrUser->user?->nik ?? '' }}</td>
                    <td>{{ $runThrUser->user?->name ?? '' }}</td>
                    <td>{{ $runThr->company?->name }}</td>
                    <td>{{ $runThrUser->user?->branch?->name }}</td>
                    <td>{{ $runThrUser->user?->join_date ? date('d-M-Y', strtotime($runThrUser->user->join_date)) : '' }}
                    </td>
                    <td>{{ $runThrUser->user?->resign_date ? date('d-M-Y', strtotime($runThrUser->user->resign_date)) : '' }}
                    </td>
                    <td>{{ $runThrUser->user?->positions
                        ?->map(function ($position) {
                            return $position->department->name . ' / ' . $position->position->name;
                        })
                        ?->implode(', ') }}
                    </td>
                    <td>{{ $runThrUser->user?->payrollInfo?->bank_account_holder ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->bank_account_no ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->secondary_bank_account_holder ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->secondary_bank_account_no ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->bank?->name ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->bank?->account_holder ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->bank?->account_no ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->bank?->code ?? '' }}</td>
                     <td>{{ $runThrUser->thr_prorate }}</td>
                    {{-- <td>{{ $runThrUser->thr_prorate }}</td> --}}

                    {{-- @foreach ($allowances as $allowance)
                        @php
                            $amount =
                                $runThrUser->components?->firstWhere('payroll_component_id', $allowance->id)
                                    ?->amount ?? 0;
                            $cloneTotalAllowancesStorages[$allowance->id] += $amount;
                        @endphp
                        <th>{{ $amount }}</th>
                    @endforeach
                    <td>{{ $runThrUser->allowance }}</td> --}}

                    @foreach ($deductions as $deduction)
                        @php
                            $amount =
                                $runThrUser->components?->firstWhere('payroll_component_id', $deduction->id)?->amount ??
                                0;
                            $cloneTotalDeductionsStorages[$deduction->id] += $amount;
                        @endphp
                        <th>{{ $amount }}</th>
                    @endforeach
                    <td>{{ $runThrUser->deduction }}</td>

                    @foreach ($benefits as $benefit)
                        @php
                            $amount =
                                $runThrUser->components?->firstWhere('payroll_component_id', $benefit->id)?->amount ??
                                0;
                            $cloneTotalBenefitsStorages[$benefit->id] += $amount;
                        @endphp
                        <th>{{ $amount }}</th>
                    @endforeach
                    <td>{{ $runThrUser->benefit }}</td>

                    <td>{{ $runThrUser->tax }}</td>
                    <td>{{ $runThrUser->total_month }}</td>
                    <td>{{ $runThrUser->total_beban_month }}</td>
                    <td>{{ $runThrUser->total_tax_month }}</td>
                    <td>{{ $runThrUser->tax_thr }}</td>
                    <td>{{ $runThrUser->thp_thr }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="15" style="font-weight: bold; background: #ffcbb1;">TOTAL</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalBasicSalary }}</td>

                {{-- @foreach ($cloneTotalAllowancesStorages as $value)
                    <th style="font-weight: bold; background: #ffcbb1;">{{ $value }}</th>
                @endforeach
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalAllowance }}</td> --}}

                @foreach ($cloneTotalDeductionsStorages as $value)
                    <th style="font-weight: bold; background: #ffcbb1;">{{ $value }}</th>
                @endforeach
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalDeduction }}</td>

                @foreach ($cloneTotalBenefitsStorages as $value)
                    <th style="font-weight: bold; background: #ffcbb1;">{{ $value }}</th>
                @endforeach
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalBenefit }}</td>

                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalTax }}</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalThp }}</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalBebanMonth }}</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalTaxMonth }}</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $taxThr }}</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $thpThr }}</td>
            </tr>
        @endforeach

        <tr>
            <th colspan="{{ $totalColumns }}"></th>
        <tr>
            <th colspan="{{ $totalColumns }}"></th>
        </tr>
        <tr>
            <th colspan="{{ $totalColumns }}" style="font-weight: bold; background-color: yellow">New Users</th>
        </tr>
        @foreach ($newUsers as $group)
            @php
                $totalBasicSalary = 0;
                $totalThrProrate = 0;
                // $totalAllowance = 0;
                $totalDeduction = 0;
                $totalTax = 0;
                $totalThp = 0;
                $totalBenefit = 0;
                $totalBebanMonth = 0;
                $totalTaxMonth = 0;
                $taxThr = 0;
                $thpThr = 0;

                // $cloneTotalAllowancesStorages = $totalAllowancesStorages;
                $cloneTotalDeductionsStorages = $totalDeductionsStorages;
                $cloneTotalBenefitsStorages = $totalBenefitsStorages;
            @endphp
            @foreach ($group as $runThrUser)
                @php
                    $totalThrProrate += $runThrUser->thr_prorate;
                    $totalBasicSalary += $runThrUser->basic_salary;
                    // $totalAllowance += $runThrUser->allowance;
                    $totalDeduction += $runThrUser->deduction;
                    $totalTax += $runThrUser->tax;
                    $totalThp += $runThrUser->total_month;
                    $totalBenefit += $runThrUser->benefit;
                    $totalBebanMonth += $runThrUser->total_beban_month;
                    $totalTaxMonth += $runThrUser->total_tax_month;
                    $taxThr += $runThrUser->tax_thr;
                    $thpThr += $runThrUser->thp_thr;
                @endphp
                <tr>
                    <td>{{ $runThrUser->user?->nik ?? '' }}</td>
                    <td>{{ $runThrUser->user?->name ?? '' }}</td>
                    <td>{{ $runThr->company?->name }}</td>
                    <td>{{ $runThrUser->user?->branch?->name }}</td>
                    <td>{{ $runThrUser->user?->join_date ? date('d-M-Y', strtotime($runThrUser->user->join_date)) : '' }}
                    </td>
                    <td>{{ $runThrUser->user?->resign_date ? date('d-M-Y', strtotime($runThrUser->user->resign_date)) : '' }}
                    </td>
                    <td>{{ $runThrUser->user?->positions
                        ?->map(function ($position) {
                            return $position->department->name . ' / ' . $position->position->name;
                        })
                        ?->implode(', ') }}
                    </td>
                    <td>{{ $runThrUser->user?->payrollInfo?->bank_account_holder ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->bank_account_no ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->secondary_bank_account_holder ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->secondary_bank_account_no ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->bank?->name ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->bank?->account_holder ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->bank?->account_no ?? '' }}</td>
                    <td>{{ $runThrUser->user?->payrollInfo?->bank?->code ?? '' }}</td>
                    <td>{{ $runThrUser->thr_prorate }}</td>
                     <td>{{ $runThrUser->basic_salary }}</td>

                    {{-- @foreach ($allowances as $allowance)
                        @php
                            $amount =
                                $runThrUser->components?->firstWhere('payroll_component_id', $allowance->id)
                                    ?->amount ?? 0;
                            $cloneTotalAllowancesStorages[$allowance->id] += $amount;
                        @endphp
                        <th>{{ $amount }}</th>
                    @endforeach
                    <td>{{ $runThrUser->allowance }}</td> --}}

                    @foreach ($deductions as $deduction)
                        @php
                            $amount =
                                $runThrUser->components?->firstWhere('payroll_component_id', $deduction->id)?->amount ??
                                0;
                            $cloneTotalDeductionsStorages[$deduction->id] += $amount;
                        @endphp
                        <th>{{ $amount }}</th>
                    @endforeach
                    <td>{{ $runThrUser->deduction }}</td>

                    @foreach ($benefits as $benefit)
                        @php
                            $amount =
                                $runThrUser->components?->firstWhere('payroll_component_id', $benefit->id)?->amount ??
                                0;
                            $cloneTotalBenefitsStorages[$benefit->id] += $amount;
                        @endphp
                        <th>{{ $amount }}</th>
                    @endforeach
                    <td>{{ $runThrUser->benefit }}</td>

                    <td>{{ $runThrUser->tax }}</td>
                    <td>{{ $runThrUser->total_month }}</td>
                    <td>{{ $runThrUser->total_beban_month }}</td>
                    <td>{{ $runThrUser->total_tax_month }}</td>
                    <td>{{ $runThrUser->tax_thr }}</td>
                    <td>{{ $runThrUser->thp_thr }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="15" style="font-weight: bold; background: #ffcbb1;">TOTAL</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalBasicSalary }}</td>

                {{-- @foreach ($cloneTotalAllowancesStorages as $value)
                    <th style="font-weight: bold; background: #ffcbb1;">{{ $value }}</th>
                @endforeach
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalAllowance }}</td> --}}

                @foreach ($cloneTotalDeductionsStorages as $value)
                    <th style="font-weight: bold; background: #ffcbb1;">{{ $value }}</th>
                @endforeach
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalDeduction }}</td>

                @foreach ($cloneTotalBenefitsStorages as $value)
                    <th style="font-weight: bold; background: #ffcbb1;">{{ $value }}</th>
                @endforeach
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalBenefit }}</td>

                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalTax }}</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalThp }}</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalBebanMonth }}</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $totalTaxMonth }}</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $taxThr }}</td>
                <td style="font-weight: bold; background: #ffcbb1;">{{ $thpThr }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
