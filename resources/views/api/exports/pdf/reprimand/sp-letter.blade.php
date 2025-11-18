<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Warning Letter</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12pt;
            color: #000;
            line-height: 1.5;
            margin: 40px;
        }

        .text-center {
            text-align: center;
        }

        .mt-2 {
            margin-top: 8px;
        }

        .mt-3 {
            margin-top: 12px;
        }

        .mt-4 {
            margin-top: 16px;
        }

        .mt-5 {
            margin-top: 24px;
        }

        .bold {
            font-weight: bold;
        }

        ul {
            margin-top: 8px;
            margin-bottom: 8px;
        }

        li {
            margin-bottom: 4px;
        }

        .signature {
            margin-top: 60px;
        }
    </style>
</head>

<body>
    <div class="text-center">
        <div class="bold">{{ $letter_title }}</div>
        <div>No: {{ $letter_number }}</div>
        {{-- <div>No: {{ $number ?? '[no]' }}/HR-SP2/IV/{{ now()->year }}</div> --}}
    </div>

    <div class="mt-5">
        <p>
            To:<br>
            <span>{{ $user_name }}</span><br>
            @if ($position)
                <span>{{ $position }}</span><br>
            @endif
            @if ($department)
                <span>{{ $department }}</span><br>
            @endif
        </p>
    </div>

    <p>Subject: <strong>{{ $letter_subject }}</strong></p>
    <p>Dear <span>{{ $user_title . $user_name }}</span>,</p>

    @if ($reprimand->month_type->is(\App\Enums\ReprimandMonthType::MONTH_3_VIOLATION_2))
        <p>
            This letter serves as a <strong>Second Warning</strong> regarding your ongoing pattern of tardiness, which
            has now extended over
            <strong>four consecutive months</strong> — specifically {{ $formatted_months }} of {{ date('Y') }}.
        </p>

        <p>
            Despite a prior written warning, there has been no significant improvement in your punctuality. Continued
            delays
            in your arrival time without valid reasons constitute a violation of the company's rules and expectations
            regarding employee discipline and attendance.
        </p>

        <p>
            We would like to remind you that punctuality is critical to maintaining productivity and collaboration
            within
            the team. Repeated lateness not only impacts your own performance but also affects your colleagues and
            overall
            departmental operations
        </p>

        <p>
            This <strong>Second Warning Letter</strong> is intended to emphasize the seriousness of this matter. If
            there is no immediate and
            sustained improvement, the company may proceed with a <strong>Third (Final) Warning</strong> and consider
            <strong>termination of
                employment</strong>, in accordance with company policies and labor regulations.
        </p>

        <p>
            We urge you to treat this matter with the utmost seriousness and take immediate corrective action.
        </p>
    @elseif($reprimand->month_type->is(\App\Enums\ReprimandMonthType::MONTH_3_VIOLATION_3))
        <p>
            This letter serves as the Third and Final Warning regarding your repeated and unresolved tardiness, which
            has
            occurred consistently over the past five consecutive months, from {{ $formatted_months }} of
            {{ date('Y') }}.
        </p>

        <p>
            Despite having been issued for all warning letter we have sent to you, there has been no meaningful
            improvement
            in your punctuality. Your continuous failure to report to work on time without valid or documented reasons
            constitutes a serious breach of the company's policies on attendance and discipline, as outlined in
            company's
            internal policies.
        </p>

        <p>
            Punctuality is a basic expectation and professional responsibility. Your ongoing tardiness negatively
            affects
            team dynamics, disrupts operational flow, and sets a poor example for others.
        </p>

        <p>
            This <strong>Third Warning Letter</strong> is the final stage in our disciplinary process. Please be advised
            that <strong>any further violation may result in immediate termination of your employment</strong>, in
            accordance with company policy and prevailing labor regulations.
        </p>

        <p>
            We strongly urge you to take this warning seriously and make immediate, consistent changes to your
            attendance
            behavior. Should you have any underlying issues affecting your punctuality, we encourage you to discuss them
            with your direct supervisor or HR as soon as possible.
        </p>
    @else
        <p>
            We are writing to formally issue this <strong>First Warning Letter</strong> due to your continuous tardiness
            over the past three consecutive months, specifically during {{ $formatted_months }} of {{ date('Y') }}.
        </p>

        <p>
            Despite having been issued for all warning letter we have sent to you, there has been no meaningful
            improvement
            in your punctuality. Your continuous failure to report to work on time without valid or documented reasons
            constitutes a serious breach of the company's policies on attendance and discipline, as outlined in
            company's
            internal policies.
        </p>

        <p>
            Punctuality is a basic expectation and professional responsibility. Your ongoing tardiness negatively
            affects
            team dynamics, disrupts operational flow, and sets a poor example for others.
        </p>

        <p>
            This <strong>Third Warning Letter</strong> is the final stage in our disciplinary process. Please be advised
            that <strong>any further violation may result in immediate termination of your employment</strong>, in
            accordance with company policy and prevailing labor regulations.
        </p>

        <p>
            We strongly urge you to take this warning seriously and make immediate, consistent changes to your
            attendance
            behavior. Should you have any underlying issues affecting your punctuality, we encourage you to discuss them
            with your direct supervisor or HR as soon as possible.
        </p>
    @endif

    <div class="signature">
        <p>Sincerely,</p>
        <p><strong>HR SUN Education Group</strong></p>
    </div>
</body>

</html>
