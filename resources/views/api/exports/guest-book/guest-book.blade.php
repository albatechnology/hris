<html xmlns:v="urn:schemas-microsoft-com:vml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
    <table>
        <thead>
            <tr>
                <th style="font-weight: bold; background: #4472C4;">ID</th>
                <th style="font-weight: bold; background: #4472C4;">Branch</th>
                <th style="font-weight: bold; background: #4472C4;">Name</th>
                <th style="font-weight: bold; background: #4472C4;">Address</th>
                <th style="font-weight: bold; background: #4472C4;">Room</th>
                <th style="font-weight: bold; background: #4472C4;">Location Destination</th>
                <th style="font-weight: bold; background: #4472C4;">Person Destination</th>
                <th style="font-weight: bold; background: #4472C4;">Vehicle Number</th>
                <th style="font-weight: bold; background: #4472C4;">Description</th>
                <th style="font-weight: bold; background: #4472C4;">Check In By</th>
                <th style="font-weight: bold; background: #4472C4;">Check In At</th>
                <th style="font-weight: bold; background: #4472C4;">Check Out By</th>
                <th style="font-weight: bold; background: #4472C4;">Check Out At</th>
                <th style="font-weight: bold; background: #4472C4;">Images</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($guestBooks as $guestBook)
                <tr>
                    <td style="background: #F2F2F2;">{{ $guestBook->id }}</td>
                    <td style="background: #F2F2F2;">{{ $guestBook->name }}</td>
                    <td style="background: #F2F2F2;">{{ $guestBook->address }}</td>
                    <td style="background: #F2F2F2;">{{ $guestBook->room }}</td>
                    <td style="background: #F2F2F2;">{{ $guestBook->location_destination }}</td>
                    <td style="background: #F2F2F2;">{{ $guestBook->person_destination }}</td>
                    <td style="background: #F2F2F2;">{{ $guestBook->vehicle_number }}</td>
                    <td style="background: #F2F2F2;">{{ $guestBook->description }}</td>
                    <td style="background: #F2F2F2;">{{ $guestBook->user?->name ?? '' }}</td>
                    <td style="background: #F2F2F2;">{{ $guestBook->created_at }}</td>
                    <td style="background: #F2F2F2;">{{ $guestBook->checkOutBy?->name ?? '' }}</td>
                    <td style="background: #F2F2F2;">{{ $guestBook->check_out_at }}</td>
                    @foreach ($guestBook->media as $media)
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
