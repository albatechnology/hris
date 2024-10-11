<table>
    <tr>
        @for ($i = 0; $i < $schedule->shifts->count(); $i++)
            <th>shift_id</th>
        @endfor
    </tr>
    <tr>
        @foreach ($schedule->shifts as $shift)
            <td>{{ $shift->id }}</td>
        @endforeach
    </tr>
    <tr>
        <td></td>
    </tr>
    <tr>
        <td></td>
    </tr>
    <tr>
        <td><strong>Schedule</strong></td>
        <td colspan="5">{{ $schedule->name }}</td>
    </tr>
    <tr>
        <td><strong>Effective Date</strong></td>
        <td>{{ date('d M Y', strtotime($schedule->effective_date)) }}</td>
    </tr>
</table>
<table>
    <thead>
        <tr>
            <th><strong>Shift ID</strong></th>
            <th><strong>Shift Name</strong></th>
            <th><strong>Clock In</strong></th>
            <th><strong>Clock Out</strong></th>
        </tr>
    </thead>
    <tbody>
        @foreach ($shifts as $shift)
            <tr>
                <th>{{ $shift->id }}</th>
                <th>{{ $shift->name }}</th>
                <th>{{ $shift->clock_in }}</th>
                <th>{{ $shift->clock_out }}</th>
            </tr>
        @endforeach
    </tbody>
</table>
<table>
    <tr>
        <th><strong>Cara Pengisian Shift :</strong></th>
    </tr>
    <tr>
        <td colspan="10">Isi baris ke 2 dengan ID Shift berdasarkan data shift di atas.</td>
    </tr>
    <tr>
        <td colspan="10">Shift bersifat recurring/berulang. Sesuaikan perulangan shift yang diperlukan</td>
    </tr>
</table>
