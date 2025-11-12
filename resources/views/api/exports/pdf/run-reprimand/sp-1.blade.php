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
        <div class="bold">FIRST WARNING LETTER</div>
        <div>No: {{ $number ?? '[no]' }}/HR-SP2/IV/{{ now()->year }}</div>
    </div>

    <div class="mt-5">
        <p>
            To:<br>
            <span class="highlight">{{ $employee_name ?? '[Employee Name]' }}</span><br>
            <span class="highlight">{{ $job_title ?? '[Job Title]' }}</span><br>
            <span class="highlight">{{ $department ?? '[Department]' }}</span>
        </p>
    </div>

    <p>Subject: <strong>Third and Final Warning - Continued Tardiness</strong></p>
    <p>Dear <span class="highlight">{{ $employee_salutation ?? '[Mr./Ms. Employee Name]' }}</span>,</p>

    <p>
        This letter serves as the Third and Final Warning regarding your repeated and unresolved tardiness, which has occurred consistently over the past five consecutive months, from [Month 1] through [Month 5] of [Year].
    </p>

    <p>
      Despite having been issued for all warning letter we have sent to you, there has been no meaningful improvement in your punctuality. Your continuous failure to report to work on time without valid or documented reasons constitutes a serious breach of the company's policies on attendance and discipline, as outlined in company's internal policies.
    </p>

     <p>
       Punctuality is a basic expectation and professional responsibility. Your ongoing tardiness negatively affects team dynamics, disrupts operational flow, and sets a poor example for others.
    </p>

    <p>
       This <strong>Third Warning Letter</strong> is the final stage in our disciplinary process. Please be advised that <strong>any further violation may result in immediate termination of your employment</strong>, in accordance with company policy and prevailing labor regulations.
    </p>

    <p>
        We strongly urge you to take this warning seriously and make immediate, consistent changes to your attendance behavior. Should you have any underlying issues affecting your punctuality, we encourage you to discuss them with your direct supervisor or HR as soon as possible.
    </p>

    <div class="signature">
        <p>Sincerely,</p>
        <p><strong>HR SUN Education Group</strong></p>
    </div>
</body>
</html>
