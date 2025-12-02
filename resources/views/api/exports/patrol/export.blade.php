<html xmlns:v="urn:schemas-microsoft-com:vml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            background-color: #f5f5f5;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            border: 1px solid #d0d0d0;
            padding: 12px 15px;
            text-align: left;
            vertical-align: middle;
        }

        /* Header Styling - Info Patroli */
        table:first-of-type th:first-child {
            font-weight: bold;
            background: #4472C4 !important;
            color: white;
            width: 200px;
        }

        table:first-of-type th:last-child {
            background: #D9E1F2 !important;
            color: #1F4E78;
            font-weight: normal;
        }

        /* Header Table - Lokasi Patroli */
        table:nth-of-type(2) thead th {
            font-weight: bold;
            background: #4472C4 !important;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 14px;
        }

        table:nth-of-type(2) tbody td {
            background: #F2F2F2 !important;
            padding: 12px;
        }

        table:nth-of-type(2) tbody td:first-child {
            font-weight: 600;
            color: #1F4E78;
        }

        table:nth-of-type(2) tbody td ol {
            margin: 0;
            padding-left: 20px;
        }

        table:nth-of-type(2) tbody td ol li {
            padding: 4px 0;
            color: #333;
        }

        /* Main Table - Detail Patroli */
        table:nth-of-type(3) thead th {
            font-weight: bold;
            background: #4472C4 !important;
            color: white;
            padding: 15px 10px;
            text-align: center;
            font-size: 13px;
            border: 1px solid #2E5C9A;
        }

        /* User Row - Orange Header */
        table:nth-of-type(3) tbody tr td[style*="ffdbb6"] {
            background: #F4B183 !important;
            font-weight: bold;
            color: #802600;
            padding: 12px 15px;
            border: 1px solid #E09963;
        }

        /* Batch Row - Darker Orange */
        table:nth-of-type(3) tbody tr td[style*="ffb66c"] {
            background: #F8CBAD !important;
            font-weight: 600;
            color: #802600;
            padding: 10px 15px;
            border: 1px solid #E6A872;
        }

        /* Task Detail Rows */
        table:nth-of-type(3) tbody tr:not(:has(td[style*="ffdbb6"])):not(:has(td[style*="ffb66c"])) td {
            background: white;
            padding: 10px;
            font-size: 12px;
            color: #333;
        }

        table:nth-of-type(3) tbody tr:not(:has(td[style*="ffdbb6"])):not(:has(td[style*="ffb66c"])):hover td {
            background: #F9F9F9;
        }

        /* Empty cells for spacing */
        table:nth-of-type(3) tbody tr td:empty {
            background: transparent !important;
            border: none;
        }

        /* Links */
        a {
            color: #4472C4;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        a:hover {
            color: #2E5C9A;
            text-decoration: underline;
        }

        /* Image container */
        v\:shape {
            display: inline-block;
            border: 2px solid #e0e0e0;
            border-radius: 4px;
            padding: 2px;
            background: white;
        }

        /* Column widths */
        colgroup col:nth-child(1) {
            width: 120pt;
        }

        colgroup col:nth-child(2) {
            width: 140pt;
        }

        colgroup col:nth-child(3) {
            width: 150pt;
        }

        colgroup col:nth-child(4) {
            width: 120pt;
        }

        colgroup col:nth-child(5) {
            width: 200pt;
        }

        colgroup col:nth-child(6) {
            width: 130pt;
        }

        colgroup col:nth-child(7) {
            width: 100pt;
        }

        colgroup col:nth-child(8) {
            width: 100pt;
        }

        /* Responsive text sizing */
        td[style*="height: 100px"] {
            max-width: 300px;
            word-wrap: break-word;
            line-height: 1.5;
        }

        /* Better spacing for datetime cells */
        td[style*="width: 130px"] {
            white-space: nowrap;
            font-family: 'Courier New', monospace;
            font-size: 11px;
        }

        /* Hidden overflow spacer */
        td h1 {
            margin: 0;
            padding: 0;
            font-size: 0;
            line-height: 0;
            visibility: hidden;
        }
    </style>
</head>

<body>
    <div>
        <table>
            <tr>
                <th style="font-weight: bold; background: #4472C4;">Nama Patroli</th>
                <th style="background: #D9E1F2;">{{ $patrol->name }}</th>
            </tr>
            <tr>
                <th style="font-weight: bold; background: #4472C4;">Map Lokasi</th>
                <th style="background: #D9E1F2;"><a
                        href="https://www.google.com/maps/search/{{ $patrol->lat . ',' . $patrol->lng }}">Lihat
                        Lokasi</a>
                </th>
            </tr>
            <tr>
                <th style="font-weight: bold; background: #4472C4;">Tanggal</th>
                <th style="background: #D9E1F2;">{{ $startDate }} - {{ $endDate }}</th>
            </tr>
        </table>
        <table>
            <thead>
                <tr>
                    <th style="font-weight: bold; background: #4472C4;">Lokasi Patroli</th>
                    <th style="font-weight: bold; background: #4472C4;">Map Lokasi</th>
                    <th style="font-weight: bold; background: #4472C4;">Alamat</th>
                    <th style="font-weight: bold; background: #4472C4;">Task</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($patrol->patrolLocations as $patrolLocation)
                    <tr>
                        <td style="background: #F2F2F2;">{{ $patrolLocation->branchLocation->name }}</td>
                        <td style="background: #F2F2F2;"><a
                                href="https://www.google.com/maps/search/{{ $patrolLocation->branchLocation->lat . ',' . $patrolLocation->branchLocation->lng }}">Lihat
                                Lokasi</a>
                        </td>
                        <td style="background: #F2F2F2; height: 100px;">{{ $patrolLocation->branchLocation->address }}
                        </td>
                        <td
                            style="background: #F2F2F2; height: {{ max($patrolLocation->tasks->count() * 23, 100) }}px;">
                            <ol>
                                @foreach ($patrolLocation->tasks as $task)
                                    <li>{{ $task->name }}</li>
                                @endforeach
                            </ol>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table>
            <colgroup>
                <col style="width: 120pt;"> <!-- Kolom 1 -->
                <col style="width: 140pt;"> <!-- Kolom 2 -->
                <col style="width: 150pt;"> <!-- Kolom 3 -->
                <col style="width: 120pt;"> <!-- Kolom 4 -->
                <col style="width: 200pt;"> <!-- Kolom 5 -->
                <col style="width: 130pt;"> <!-- Kolom 6 -->
                <col style="width: 100pt;"> <!-- Kolom 7 -->
                <col style="width: 100pt;"> <!-- Kolom 8 -->
            </colgroup>
            <thead>
                <tr>
                    <th style="font-weight: bold; background: #4472C4;">User</th>
                    <th style="font-weight: bold; background: #4472C4;">Patroli Batch</th>
                    <th style="font-weight: bold; background: #4472C4;">Task</th>
                    <th style="font-weight: bold; background: #4472C4;">Lokasi</th>
                    <th style="font-weight: bold; background: #4472C4;">Laporan Pekerjaan</th>
                    <th style="font-weight: bold; background: #4472C4;">Waktu Pengerjaan</th>
                    <th style="font-weight: bold; background: #4472C4;">Map Lokasi</th>
                    <th style="font-weight: bold; background: #4472C4;">Bukti Foto</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($patrol->users as $userPatrol)
                    <tr>
                        <td style="font-weight: bold; background: #F4B183;">{{ $userPatrol->user->name }}</td>
                        <td style="background: #F4B183;"></td>
                        <td style="background: #F4B183;"></td>
                        <td style="background: #F4B183;"></td>
                        <td style="background: #F4B183;"></td>
                        <td style="background: #F4B183;"></td>
                        <td style="background: #F4B183;"></td>
                        <td style="background: #F4B183;"></td>
                    </tr>
                    @foreach ($userPatrol->user->patrolBatches as $patrolBatch)
                        <tr>
                            <td></td>
                            <td style="font-weight: bold; background: #F8CBAD;">{{ $patrolBatch->datetime }}</td>
                            <td style="background: #F8CBAD;"></td>
                            <td style="background: #F8CBAD;"></td>
                            <td style="background: #F8CBAD;"></td>
                            <td style="background: #F8CBAD;"></td>
                            <td style="background: #F8CBAD;"></td>
                            <td style="background: #F8CBAD;"></td>
                        </tr>
                        @foreach ($patrolBatch->userPatrolTasks as $userPatrolTask)
                            <tr>
                                <td></td>
                                <td></td>
                                <td>{{ $userPatrolTask->patrolTask?->name }}</td>
                                <td>{{ $userPatrolTask->patrolTask?->patrolLocation?->branchLocation?->name }}</td>
                                <td style="height: 100px">{{ $userPatrolTask->description }}</td>
                                <td style="width: 130px;">{{ $userPatrolTask->datetime }}</td>
                                <td>
                                    <a
                                        href="https://www.google.com/maps/search/{{ $userPatrolTask->lat . ',' . $userPatrolTask->lng }}">Lihat
                                        Lokasi</a>
                                </td>
                                @foreach ($userPatrolTask->media as $media)
                                    <td>
                                        @if ($media)
                                            <v:shape id="img-tes" type="#_x0000_t75" style='width:80pt;height:80pt'>
                                                <v:imagedata src="{{ $media->getUrl() }}" o:title="" />
                                            </v:shape>
                                        @else
                                            <a href="{{ $media->original_url }}" target="_blank">Lihat Gambar</a>
                                        @endif
                                    </td>
                                @endforeach
                                <td>
                                    <h1 style="margin-bottom: 100px">'</h1>
                                    <h1 style="margin-bottom: 100px">&nbsp;</h1>
                                    <h1 style="margin-bottom: 100px">&nbsp;</h1>
                                    <h1 style="margin-bottom: 100px">&nbsp;</h1>
                                    <h1 style="margin-bottom: 100px">&nbsp;</h1>
                                    <h1 style="margin-bottom: 100px">&nbsp;</h1>
                                    <h1 style="margin-bottom: 100px">&nbsp;</h1>
                                    <h1 style="margin-bottom: 100px">&nbsp;</h1>
                                    <h1 style="margin-bottom: 100px">&nbsp;</h1>
                                    <h1 style="margin-bottom: 100px">&nbsp;</h1>
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td></td>
                        </tr>
                    @endforeach
                    <tr>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
