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

        .highlight {
            background-color: #fff6a5;
        }

        .signature {
            margin-top: 60px;
        }
    </style>
</head>

<body>
    <div class="text-center">
        <div class="bold">FIRST WARNING LETTER</div>
        <div>No: {{ $number ?? '[no]' }}/HR-SP2/IV/{{ now()->year }}</div>
    </div>

    <div class="mt-5">
        <p>
            To:<br>
            <span class="highlight">{{ $user_name }}</span><br>
            @if ($position)
                <span class="highlight">{{ $position }}</span><br>
            @endif
            @if ($department)
                <span class="highlight">{{ $department }}</span><br>
            @endif
        </p>
    </div>

    <p>Subject: <strong>First Warning Letter - Repeated Tardiness</strong></p>
    <p>Dear <span class="highlight">{{ $user_title . $user_name }}</span>,</p>

    <p>
        This letter serves as a <strong>Second Warning</strong> regarding your ongoing pattern of tardiness, which has now extended over
        <strong>four consecutive months</strong> — specifically [Month 1], [Month 2], [Month 3], and [Month 4] of [Year].
    </p>

    <p>
        Despite a prior written warning, there has been no significant improvement in your punctuality. Continued delays
        in your arrival time without valid reasons constitute a violation of the company's rules and expectations
        regarding employee discipline and attendance.
    </p>

    <p>
        We would like to remind you that punctuality is critical to maintaining productivity and collaboration within
        the team. Repeated lateness not only impacts your own performance but also affects your colleagues and overall
        departmental operations
    </p>

    <p>
        This <strong>Second Warning Letter</strong> is intended to emphasize the seriousness of this matter. If there is no immediate and
        sustained improvement, the company may proceed with a <strong>Third (Final) Warning</strong> and consider <strong>termination of
        employment</strong>, in accordance with company policies and labor regulations.
    </p>

    <p>
        We urge you to treat this matter with the utmost seriousness and take immediate corrective action.
    </p>

    <div class="signature">
        <p>Sincerely,</p>
        <p><strong>HR SUN Education Group</strong></p>
    </div>
</body>

</html>
