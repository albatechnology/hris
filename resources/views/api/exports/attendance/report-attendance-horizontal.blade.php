<table>
    <thead>
        <tr>
            <th rowspan="2" style="font-weight: bold">NIK</th>
            <th rowspan="2" style="font-weight: bold">Name</th>
            @foreach ($dateRange as $date)
                <th colspan="2" style="font-weight: bold; text-align: center;">{{ $date->format('Y-m-d') }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach ($dateRange as $date)
                <th style="font-weight: bold">Clock In</th>
                <th style="font-weight: bold">Clock Out</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $data)
            <tr>
                <td>{{ $data['user']['nik'] }}</td>
                <td>{{ $data['user']['name'] }}</td>
                @if (count($data['attendances']))
                    @foreach ($dateRange as $date)
                        @php
                            $attendance = collect($data['attendances'])->where('date', $date->format('Y-m-d'))->first();
                        @endphp
                        @if ($attendance && $attendance['shift'] && $attendance['shift']['is_dayoff'] == 1)
                            <td style="text-align: center; background-color: yellow;">Dayoff</td>
                            <td style="text-align: center; background-color: yellow;">Dayoff</td>
                        @elseif ($attendance && $attendance['shift_type'] && $attendance['shift_type'] == 'national_holiday')
                            <td style="text-align: center; background-color: #00e1ff;">National Holiday</td>
                            <td style="text-align: center; background-color: #00e1ff;">National Holiday</td>
                        @elseif ($attendance && $attendance['attendance'] && $attendance['attendance']['timeoff'])
                            @if ($attendance['attendance']['clock_in'])
                                <td style="background-color: pink;">
                                    {{ date('H:i', strtotime($attendance['attendance']['clock_in']['time'])) }}
                                </td>
                            @else
                                <td style="background-color: pink;">Timeoff</td>
                            @endif
                            @if ($attendance['attendance']['clock_out'])
                                <td style="background-color: pink;">
                                    {{ date('H:i', strtotime($attendance['attendance']['clock_out']['time'])) }}
                                </td>
                            @else
                                <td style="background-color: pink;">Timeoff</td>
                            @endif
                        @elseif ($attendance && $attendance['attendance'])
                            @if ($attendance['attendance']['clock_in'])
                                <td>{{ date('H:i', strtotime($attendance['attendance']['clock_in']['time'])) }}</td>
                            @else
                                <td style="background-color: red;"></td>
                            @endif
                            @if ($attendance['attendance']['clock_out'])
                                <td>{{ date('H:i', strtotime($attendance['attendance']['clock_out']['time'])) }}
                                </td>
                            @else
                                <td style="background-color: red;"></td>
                            @endif
                        @else
                            <td colspan="2" style="background-color: red;"></td>
                        @endif
                    @endforeach
                @else
                    @foreach ($dateRange as $date)
                        <td colspan="2" style="background-color: red;"></td>
                    @endforeach
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
