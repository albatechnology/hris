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

        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 12px; }
        .mt-4 { margin-top: 16px; }
        .mt-5 { margin-top: 24px; }

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
        <div class="bold">WARNING LETTER</div>
        <div>No: {{ $number ?? '[no]' }}/HR-WL1/IV/{{ now()->year }}</div>
    </div>

    <div class="mt-5">
        <p>
            To:<br>
            <span class="highlight">{{ $employee_name ?? '[Employee Name]' }}</span><br>
            <span class="highlight">{{ $job_title ?? '[Job Title]' }}</span><br>
            <span class="highlight">{{ $department ?? '[Department]' }}</span>
        </p>
    </div>

    <p>Dear <span class="highlight">{{ $employee_salutation ?? '[Mr./Ms. Employee Name]' }}</span>,</p>

    <p>
        Through this letter, we would like to formally address your repeated tardiness on the following dates:
    </p>

    <ul>
        @foreach ($dates ?? ['[e.g., April 1st, 2025]', '[e.g., April 4th, 2025]', '[e.g., April 8th, 2025]'] as $date)
            <li>{{ $date }}</li>
        @endforeach
    </ul>

    <p>
        We understand that occasional delays may happen due to unforeseen circumstances.
        However, arriving late on multiple occasions without prior notice or valid reason
        reflects a lack of discipline and can disrupt team productivity.
    </p>

    <p>
        In accordance with the company's internal policies, all employees are expected to arrive on
        time as scheduled. We kindly remind you to improve your punctuality and ensure that this does
        not become a recurring issue.
    </p>

    <p>
        Should this behavior continue, further disciplinary action may be taken in accordance
        with the company’s regulations.
    </p>

    <p>Thank you for your attention and cooperation.</p>

    <div class="signature">
        <p>Sincerely,</p>
        <p><strong>HR SUN Education Group</strong></p>
    </div>
</body>
</html>
