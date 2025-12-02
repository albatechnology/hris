<html xmlns:v="urn:schemas-microsoft-com:vml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
    <table>
        <thead>
            <tr>
                <th style="font-weight: bold; background: #4472C4;">User</th>
                <th style="font-weight: bold; background: #4472C4;">Title</th>
                <th style="font-weight: bold; background: #4472C4;">Start At</th>
                <th style="font-weight: bold; background: #4472C4;">End At</th>
                <th style="font-weight: bold; background: #4472C4;">Created At</th>
                <th style="font-weight: bold; background: #4472C4;">Description</th>
                <th style="font-weight: bold; background: #4472C4;">Images</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dailyActivities as $dailyActivity)
                <tr>
                    <td style="background: #F2F2F2;">{{ $dailyActivity->user->name }}</td>
                    <td style="background: #F2F2F2;">{{ $dailyActivity->title }}</td>
                    <td style="background: #F2F2F2;">{{ $dailyActivity->start_at }}</td>
                    <td style="background: #F2F2F2;">{{ $dailyActivity->end_at }}</td>
                    <td style="background: #F2F2F2;">{{ $dailyActivity->created_at }}</td>
                    <td style="background: #F2F2F2;">{{ $dailyActivity->description }}</td>
                    @foreach ($dailyActivity->media as $media)
                        <td>
                            @if ($media)
                                <v:shape id="img-tes" type="#_x0000_t75" style='width:80pt;height:80pt'>
                                    <v:imagedata src="{{ $media->getUrl() }}" o:title="" />
                                </v:shape>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
