<table>
    <thead>
        <tr>
            <th>NIK</th>
            <th>Name</th>
            <th>Date</th>
            <th>Shift</th>
            <th>Schedule Check In</th>
            <th>Schedule Check Out</th>
            <th>Attendance Code</th>
            <th>Time Off Code</th>
            <th>Check In</th>
            <th>Check Out</th>
            <th>Late In</th>
            <th>Early Out</th>
            <th>Schedule Working Hour</th>
            <th>Actual Working Hour</th>
            <th>Real Working Hour</th>
            <th>Overtime Duration Before</th>
            <th>Overtime Duration After</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $data)
            @forelse ($data['attendances'] as $attendance)
                <tr>
                    <td>{{ $data['user']['nik'] }}</td>
                    <td>{{ $data['user']['name'] }}</td>
                    <td>{{ $attendance['date']->format('Y-m-d') }}</td>
                    <td>{{ $attendance['shift']['name'] }}</td>
                    <td>{{ $attendance['shift']['clock_in'] }}</td>
                    <td>{{ $attendance['shift']['clock_out'] }}</td>
                    <td>{{ $attendance['shift']['clock_out'] }}</td>
                    <td>
                        @if (isset($attendance['attendance']['timeoff']) && !is_null($attendance['attendance']['timeoff']))
                            @if (isset($attendance['attendance']['timeoff']['timeoff_policy']) &&
                                    !is_null($attendance['attendance']['timeoff']['timeoff_policy']))
                                {{ $attendance['attendance']['timeoff']['timeoff_policy']['code'] }}
                            @else
                                {{ $attendance['attendance']['timeoff']['timeoff_policy']['name'] }}
                            @endif
                        @endif
                    </td>
                    <td>
                        @if (isset($attendance['attendance']['clock_in']) && !is_null($attendance['attendance']['clock_in']))
                            {{ date('H:i', strtotime($attendance['attendance']['clock_in'])) }}
                        @endif
                    </td>
                    <td>
                        @if (isset($attendance['attendance']['clock_out']) && !is_null($attendance['attendance']['clock_out']))
                            {{ date('H:i', strtotime($attendance['attendance']['clock_out'])) }}
                        @endif
                    </td>
                </tr>
            @empty
            @endforelse
        @endforeach
    </tbody>
</table>
