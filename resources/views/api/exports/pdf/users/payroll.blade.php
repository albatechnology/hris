<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Payroll</title>
    <style>
        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .w-100 {
            width: 100%
        }

        .w-50 {
            width: 50%
        }

        .vertical-align-top {
            vertical-align: top
        }

        footer {
            font-size: 13px;
            position: absolute;
            bottom: 0
        }
    </style>
</head>

<body>
    <h4 class="text-center" style="color: red">*CONFIDENTIAL</h4>
    <table class="w-100">
        <tr>
            <td class="w-50">
                <h3 style="text-align: left;">{{ $runPayrollUser->runPayroll->company->name }}</h3>
            </td>
            <td class="w-50">
                <h3 style="text-align: right;">PAYSLIP</h3>
            </td>
        </tr>
    </table>
    <table class="w-100">
        <tr>
            <td class="w-100 vertical-align-top">
                <table>
                    <tr>
                        <th class="text-left">Payroll cut off</th>
                        <td>: {{ $cutoffDate }}</td>
                    </tr>
                    <tr>
                        <th class="text-left">ID/Name</th>
                        <td>: {{ $user->nik . ' / ' . $user->full_name }}</td>
                    </tr>
                    <tr>
                        <th class="text-left">Job Position</th>
                        <td>: {{ $user->positions->pluck('position.name')->implode(', ') }}</td>
                    </tr>
                    <tr>
                        <th class="text-left">Organization</th>
                        <td>: {{ $runPayrollUser->runPayroll->company->name }}</td>
                    </tr>
                </table>
            </td>
            <td class="w-100 vertical-align-top">
                <table>
                    <tr>
                        <th class="text-left">Grade / Level</th>
                        <td>: {{ $user->detail->job_level }}</td>
                    </tr>
                    <tr>
                        <th class="text-left">PTKP</th>
                        <td>: {{ $user->payrollInfo->ptkp_status }}</td>
                    </tr>
                    <tr>
                        <th class="text-left">NPWP</th>
                        <td>: {{ $user->payrollInfo->npwp }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <br>
    <table class="w-100" border="1" style="border-spacing: 0">
        <tr>
            <th style="background: #e6d7d7">Earnings</th>
            <th style="background: #e6d7d7">Deductions</th>
        </tr>
        <tr>
            <td class="vertical-align-top">
                <table style="margin-bottom: 10px" border="0" class="w-100">
                    @foreach ($earnings as $earning)
                        <tr>
                            <td>{{ $earning->payrollComponent->name }}</td>
                            <td class="text-right">{{ number_format($earning->amount) }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
            <td class="vertical-align-top">
                <table style="margin-bottom: 10px" border="0" class="w-100">
                    @foreach ($deductions as $deduction)
                        <tr>
                            <td>{{ $deduction->payrollComponent->name }}</td>
                            <td class="text-right">{{ number_format($deduction->amount) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td>PPH 21</td>
                        <td class="text-right">{{ number_format($runPayrollUser->tax) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table border="0" class="w-100">
                    <tr>
                        <th class="text-left">Total Earnings</th>
                        <th class="text-right">{{ number_format($runPayrollUser->total_earning) }}</th>
                    </tr>
                </table>
            </td>
            <td>
                <table border="0" class="w-100">
                    <tr>
                        <th class="text-left">Total Deductions</th>
                        <th class="text-right">{{ number_format($runPayrollUser->total_deduction) }}
                        </th>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <table class="w-100">
        <tr>
            <td class="w-50"></td>
            <td>
                <table border="0" class="w-100">
                    <tr>
                        <th class="text-left">
                            <h3>Take Home Pay</h3>
                        </th>
                        <th class="text-right">{{ number_format($runPayrollUser->thp) }}</th>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <table class="w-50">
        <tr>
            <th class="text-left">Benefits*</th>
        </tr>
        <tr>
            <th colspan="2">
                <hr>
            </th>
        </tr>
        @foreach ($benefits as $benefit)
            <tr>
                <td>{{ $benefit->payrollComponent->name }}</td>
                <td class="text-right">{{ number_format($benefit->amount) }}</td>
            </tr>
        @endforeach
        <tr>
            <th class="text-left">Total benefits</th>
            <th class="text-right">{{ number_format($runPayrollUser->total_benefit) }}</th>
        </tr>
    </table>
    <footer>
        <p>*These are the benefits you'll get from the company, but not included in your take-home pay (THP).</p>
        <p>THIS IS COMPUTER GENERATED PRINTOUT AND NO SIGNATURE IS REQUIRED</p>
        <p>PLEASE NOTE THAT THE CONTENTS OF THIS STATEMENT SHOULD BE TREATED WITH ABSOLUTE CONFIDENTIALITY EXCEPT TO THE
            EXTENT YOU ARE REQUIRED TO MAKE DISCLOSURE FOR ANY TAX, LEGAL, OR REGULATORY PURPOSES. ANY BREACH OF THIS
            CONFIDENTIALITY OBLIGATION WILL BE DEALT WITH SERIOUSLY, WHICH MAY INVOLVE DISCPLINARY ACTION BEING TAKEN.
        </p>
        <p>HARAP DIPERHATIKAN, ISI PERNYATAAN INI ADALAH RAHASIA KECUALI ANDA DIMINTA UNTUK MENGUNGKAPKANNYA UNTUK
            PAJAK, HUKUM, ATAU KEPENTINGAN PEMERINTAH. SETIAP PELANGGARAN ATAS KEWAJIBAN MENJAGA KERAHASIAAN INI AKAN
            DIKENAKAN SANKSI, YANG MUNGKIN BERUPA TINDAKAN KEDISIPLINAN.</p>
        <table class="w-100">
            <tr>
                <th class="text-left">This payslip is generated by {{ config('app.name') }}</th>
                <th class="text-right">{{ env('APP_URL') }}</th>
            </tr>
        </table>
    </footer>
</body>

</html>
