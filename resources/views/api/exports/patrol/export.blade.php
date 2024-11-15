<div>
    <table>
        <tr>
            <th>Nama Patroli</th>
            <th>{{ $patrol->name }}</th>
        </tr>
    </table>

    @foreach ($patrol->patrolLocations as $patrolLocation)
        <table>
            <tr>
                <th>Nama Lokasi</th>
                <th>{{ $patrolLocation->clientLocation->name }}</th>
            </tr>
            <tr>
                <th>Latitude</th>
                <th>{{ $patrolLocation->clientLocation->lat }}</th>
            </tr>
            <tr>
                <th>Longitude</th>
                <th>{{ $patrolLocation->clientLocation->lng }}</th>
            </tr>
            <tr>
                <th>Alamat</th>
                <th>{{ $patrolLocation->clientLocation->address }}</th>
            </tr>
        </table>
        @foreach ($patrolLocation->tasks as $task)
            <table>
                <tr>
                    <th>Nama Task</th>
                    <th>{{ $task->name }}</th>
                </tr>
                <tr>
                    <th>Deskripsi</th>
                    <th>{{ $task->description }}</th>
                </tr>
            </table>
            <table>
                <thead>
                    <tr>
                        <th>Nama User</th>
                        <th>Schedule</th>
                        <th>Shift</th>
                        <th>Jam</th>
                        <th>Deskripsi</th>
                    </tr>
                </thead>
                @foreach ($task->userPatrolTasks as $userPatrolTask)
                    <tbody>
                        <tr>
                            <th>{{ $userPatrolTask->user->full_name }}</th>
                            <th>{{ $userPatrolTask->schedule->name }}</th>
                            <th>{{ $userPatrolTask->shift->name }}</th>
                            <th>{{ date('d-M-Y H:i', strtotime($userPatrolTask->created_at)) }}</th>
                            <th>{{ $task->description }}</th>
                        </tr>
                    </tbody>
                @endforeach
            </table>
        @endforeach
        <table>
            <tr>
                <td></td>
            </tr>
        </table>
    @endforeach
</div>
