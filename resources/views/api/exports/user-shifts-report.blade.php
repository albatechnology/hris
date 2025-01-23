<table>
    <thead>
        <tr>
            <th style="font-weight: bold">Employee ID</th>
            <th style="font-weight: bold">Employee Name</th>
            @foreach ($data['dates'] as $date)
                <th style="font-weight: bold">{{ date('Y-m-d', strtotime($date)) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($data['users'] as $user)
            <tr>
                <td>{{ $user['user']->nik }}</td>
                <td>{{ $user['user']->full_name }}</td>
                @foreach ($user['shifts'] as $shift)
                    <td>{{ $shift['shift'] }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
