<table>
    <thead>
        <tr>
            <th style="font-weight: bold; background: #b4c7dc;">ID</th>
            <th style="font-weight: bold; background: #b4c7dc;">user_patrol_batch_id</th>
            <th style="font-weight: bold; background: #b4c7dc;">patrol_task_id</th>
            <th style="font-weight: bold; background: #b4c7dc;">Deskripsi</th>
            <th style="font-weight: bold; background: #b4c7dc;">LatLng</th>
            <th style="font-weight: bold; background: #b4c7dc;">Bukti Foto</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($userPatrolBatch->userPatrolTasks as $userPatrolTask)
            <tr>
                <td>{{ $userPatrolTask->id }}</td>
                <td>{{ $userPatrolTask->user_patrol_batch_id }}</td>
                <td>{{ $userPatrolTask->patrol_task_id }}</td>
                <td>{{ $userPatrolTask->description }}</td>
                <td>{{ $userPatrolTask->lat . ',' . $userPatrolTask->lng }}</td>
                <td><img src="{{ $userPatrolTask->media[0]?->original_url }}" alt="test"></td>
            </tr>
        @endforeach
    </tbody>
</table>
