{{-- <html xmlns:v="urn:schemas-microsoft-com:vml">


<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
      <xml>
        <o:shapelayout v:ext="edit">
            <o:idmap v:ext="edit" data="1"/>
        </o:shapelayout>
    </xml>
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
                <th style="font-weight: bold; background: #4472C4;">ID</th>
                <th style="font-weight: bold; background: #4472C4;">Branch</th>
                <th style="font-weight: bold; background: #4472C4;">User</th>
                <th style="font-weight: bold; background: #4472C4;">Type</th>
                <th style="font-weight: bold; background: #4472C4;">Description</th>
                <th style="font-weight: bold; background: #4472C4;">Created At</th>
                <th style="font-weight: bold; background: #4472C4;">Gambar</th>
            </tr>

            @foreach ($incidents as $incident)
                <tr>
                    <td>{{ $incident->id }}</td>
                    <td>{{ $incident->branch_id }}</td>
                    <td>{{ $incident->user?->name }}</td>
                    <td>{{ $incident->incidentType?->name }}</td>
                    <td>{{ $incident->description }}</td>
                    <td>{{ $incident->created_at }}</td>
                    <td>
                        @foreach ($incident->media as $key => $media)
                            <v:shape id="img-{{ $incident->id }}-{{ $key }}" type="#_x0000_t75"
                                style='width:80pt;height:80pt'>
                                <v:imagedata src="{{ $media->getUrl() }}" o:title="" />
                            </v:shape>
                        @endforeach
                    </td>

                </tr>
            @endforeach

        </table>

    </div>
</body>

</html> --}}
<html xmlns:v="urn:schemas-microsoft-com:vml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <xml>
        <o:shapelayout v:ext="edit">
            <o:idmap v:ext="edit" data="1" />
        </o:shapelayout>
    </xml>
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
            padding: 14px 16px;
            text-align: left;
            vertical-align: middle;
            font-size: 13px;
        }

        /* Header Styling */
        th {
            font-weight: bold;
            background: #4472C4 !important;
            color: white;
            text-align: center;
            font-size: 14px;
            padding: 16px;
        }

        /* Data Rows */
        td {
            background: white;
            color: #333;
            line-height: 1.6;
        }

        /* Zebra Striping for Better Readability */
        tr:nth-child(even) td {
            background: #F9F9F9;
        }

        tr:hover td {
            background: #F0F4F8;
        }

        /* Column Widths - Fixed & Diperbesar */
        colgroup col:nth-child(1) {
            width: 100pt;
            /* ID */
        }

        colgroup col:nth-child(2) {
            width: 150pt;
            /* Branch */
        }

        colgroup col:nth-child(3) {
            width: 200pt;
            /* User */
        }

        colgroup col:nth-child(4) {
            width: 180pt;
            /* Type */
        }

        colgroup col:nth-child(5) {
            width: 350pt;
            /* Description */
        }

        colgroup col:nth-child(6) {
            width: 180pt;
            /* Created At */
        }

        colgroup col:nth-child(7) {
            width: 300pt;
            /* Gambar - diperbesar signifikan */
        }

        /* Text Wrapping untuk Description */
        td:nth-child(5) {
            word-wrap: break-word;
            white-space: normal;
            max-width: 350pt;
        }

        /* Date/Time Formatting */
        td:nth-child(6) {
            white-space: nowrap;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }

        /* Image Container */
        v\:shape {
            display: inline-block;
            border: 2px solid #e0e0e0;
            border-radius: 4px;
            padding: 2px;
            background: white;
            margin: 2px;
        }

        /* Center ID and Branch columns */
        td:nth-child(1),
        td:nth-child(2) {
            text-align: center;
        }
    </style>
</head>

<body>
    <div>
        <table>
            <colgroup>
                <col style="width: 100pt;"> <!-- ID -->
                <col style="width: 150pt;"> <!-- Branch -->
                <col style="width: 200pt;"> <!-- User -->
                <col style="width: 180pt;"> <!-- Type -->
                <col style="width: 350pt;"> <!-- Description -->
                <col style="width: 180pt;"> <!-- Created At -->
                <col style="width: 300pt;"> <!-- Gambar -->
            </colgroup>
            <thead>
                <tr>
                    <th style="font-weight: bold; background: #4472C4;">ID</th>
                    <th style="font-weight: bold; background: #4472C4;">Branch</th>
                    <th style="font-weight: bold; background: #4472C4;">User</th>
                    <th style="font-weight: bold; background: #4472C4;">Type</th>
                    <th style="font-weight: bold; background: #4472C4;">Description</th>
                    <th style="font-weight: bold; background: #4472C4;">Created At</th>
                    <th style="font-weight: bold; background: #4472C4;">Gambar</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($incidents as $incident)
                    <tr>
                        <td>{{ $incident->id }}</td>
                        <td>{{ $incident->branch_id }}</td>
                        <td>{{ $incident->user?->name }}</td>
                        <td>{{ $incident->incidentType?->name }}</td>
                        <td>{{ $incident->description }}</td>
                        <td>{{ $incident->created_at }}</td>
                        <td>
                            @foreach ($incident->media as $key => $media)
                                <v:shape id="img-{{ $incident->id }}-{{ $key }}" type="#_x0000_t75"
                                    style='width:80pt;height:80pt'>
                                    <v:imagedata src="{{ $media->getUrl() }}" o:title="" />
                                </v:shape>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
