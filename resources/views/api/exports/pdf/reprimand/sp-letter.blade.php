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
        We are writing to formally issue this <strong>First Warning Letter</strong> due to your continuous tardiness
        over the past three consecutive months, specifically during [Month 1], [Month 2], dan [Month 3] of [Year].
    </p>

    {{ $mail_body }}

    <div class="signature">
        <p>Sincerely,</p>
        <p><strong>HR SUN Education Group</strong></p>
    </div>
</body>

</html>
