<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>REPRIMAND</title>
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

        .download-button {
            margin-top: 20px;
            margin-bottom: 20px;
            border-radius: 20px;
            padding: 10px;
            color: #ffffff;
            background-color: #F16522;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="text-center">
        <div class="bold">REPRIMAND</div>
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

    <p>Dear <span>{{ $user_title . $user_name }}</span>,</p>

    {{-- arahkan ke file pdf nya --}}
    @if ($download_url)
        <br><br>
        <a href="{{ $download_url }}" class="download-button">DOWNLOAD FILE</a>
        <br><br>
    @endif

    <p>Thank you for your attention and cooperation.</p>

    <div class="signature">
        <p>Sincerely,</p>
        <p><strong>HR SUN Education Group</strong></p>
    </div>
</body>

</html>
