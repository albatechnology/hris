<table>
    <thead>
        <tr>
            <th style="font-weight: bold">NIK</th>
            <th style="font-weight: bold">Name</th>
            <th style="font-weight: bold">Date</th>
            <th style="font-weight: bold">Shift</th>
            <th style="font-weight: bold">Schedule Check In</th>
            <th style="font-weight: bold">Schedule Check Out</th>
            <th style="font-weight: bold">Attendance Code</th>
            <th style="font-weight: bold">Time Off Code</th>
            <th style="font-weight: bold">Check In</th>
            <th style="font-weight: bold">Check Out</th>
            <th style="font-weight: bold">Late In</th>
            <th style="font-weight: bold">Early Out</th>
            <th style="font-weight: bold">Schedule Working Hour</th>
            {{-- <th style="font-weight: bold">Actual Working Hour</th> --}}
            <th style="font-weight: bold">Real Working Hour</th>
            <th style="font-weight: bold">Overtime Duration Before</th>
            <th style="font-weight: bold">Overtime Duration After</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $data)
            @forelse ($data['attendances'] as $attendance)
                <tr>
                    <td>{{ $data['user']['nik'] }}</td>
                    <td>{{ $data['user']['name'] }}</td>
                    <td>{{ $attendance['date'] }}</td>
                    <td>{{ $attendance['shift']['name'] }}</td>
                    <td>{{ $attendance['shift']['clock_in'] }}</td>
                    <td>{{ $attendance['shift']['clock_out'] }}</td>
                    <td>
                        @if (isset($attendance['attendance']['timeoff']) && !is_null($attendance['attendance']['timeoff']))
                            @if (isset($attendance['attendance']['timeoff']['timeoffPolicy']) &&
                                    !is_null($attendance['attendance']['timeoff']['timeoffPolicy']))
                                {{ $attendance['attendance']['timeoff']['timeoffPolicy']['code'] }}
                            @else
                                {{ $attendance['attendance']['timeoff']['timeoffPolicy']['name'] }}
                            @endif
                        @else
                            @if (isset($attendance['attendance']['shift']) && !is_null($attendance['attendance']['shift']))
                                H
                            @else
                                A
                            @endif
                        @endif
                    </td>
                    <td>
                        @if (isset($attendance['attendance']['timeoff']) && !is_null($attendance['attendance']['timeoff']))
                            @if (isset($attendance['attendance']['timeoff']['timeoffPolicy']) &&
                                    !is_null($attendance['attendance']['timeoff']['timeoffPolicy']))
                                {{ $attendance['attendance']['timeoff']['timeoffPolicy']['code'] }}
                            @else
                                {{ $attendance['attendance']['timeoff']['timeoffPolicy']['name'] }}
                            @endif
                        @endif
                    </td>
                    <td>
                        @if (isset($attendance['attendance']['clock_in']) && !is_null($attendance['attendance']['clock_in']))
                            {{ date('H:i', strtotime($attendance['attendance']['clock_in']['time'])) }}
                        @endif
                    </td>
                    <td>
                        @if (isset($attendance['attendance']['clock_out']) && !is_null($attendance['attendance']['clock_out']))
                            {{ date('H:i', strtotime($attendance['attendance']['clock_out']['time'])) }}
                        @endif
                    </td>
                    <td>
                        @if (isset($attendance['attendance']['late_in']) && !is_null($attendance['attendance']['late_in']))
                            {{ $attendance['attendance']['late_in'] }}
                        @endif
                    </td>
                    <td>
                        @if (isset($attendance['attendance']['early_out']) && !is_null($attendance['attendance']['early_out']))
                            {{ $attendance['attendance']['early_out'] }}
                        @endif
                    </td>
                    <td>
                        @if (isset($attendance['attendance']['shift']) && isset($attendance['attendance']['shift']['schedule_working_hour']))
                            {{ $attendance['attendance']['shift']['schedule_working_hour'] }}
                        @else
                            {{ $attendance['shift']['schedule_working_hour'] }}
                        @endif
                    </td>
                    <td>
                        @if (isset($attendance['attendance']['real_working_hour']) && !is_null($attendance['attendance']['real_working_hour']))
                            {{ $attendance['attendance']['real_working_hour'] }}
                        @endif
                    </td>
                    <td>
                        @if (isset($attendance['attendance']['overtime_duration_before_shift']) &&
                                !is_null($attendance['attendance']['overtime_duration_before_shift']))
                            {{ $attendance['attendance']['overtime_duration_before_shift'] }}
                        @endif
                    </td>
                    <td>
                        @if (isset($attendance['attendance']['overtime_duration_after_shift']) &&
                                !is_null($attendance['attendance']['overtime_duration_after_shift']))
                            {{ $attendance['attendance']['overtime_duration_after_shift'] }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td>{{ $data['user']['nik'] }}</td>
                    <td>{{ $data['user']['name'] }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>A</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endforelse
            <tr>
                <th style="background: #ffcbb1; font-weight: bold" colspan="10">TOTAL FOR EMPLOYEE :
                    {{ $data['user']['nik'] . ' - ' . $data['user']['name'] }}</th>
                <th style="background: #ffcbb1; font-weight: bold">{{ $data['summary']['late_in'] }}</th>
                <th style="background: #ffcbb1; font-weight: bold">{{ $data['summary']['early_out'] }}</th>
                <th style="background: #ffcbb1; font-weight: bold">{{ $data['summary']['schedule_working_hour'] }}</th>
                <th style="background: #ffcbb1; font-weight: bold">{{ $data['summary']['real_working_hour'] }}</th>
                <th style="background: #ffcbb1; font-weight: bold">
                    {{ $data['summary']['overtime_duration_before_shift'] }}</th>
                <th style="background: #ffcbb1; font-weight: bold">
                    {{ $data['summary']['overtime_duration_after_shift'] }}</th>
            </tr>
        @endforeach
    </tbody>
</table>
