<table>
    <thead>
        <tr>
            <th style="font-weight: bold; background: #b4c7dc;">ID</th>
            <th style="font-weight: bold; background: #b4c7dc;">user_patrol_batch_id</th>
            <th style="font-weight: bold; background: #b4c7dc;">patrol_task_id</th>
            <th style="font-weight: bold; background: #b4c7dc;">Deskripsi</th>
            <th style="font-weight: bold; background: #b4c7dc;">LatLng</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($userPatrolBatch->userPatrolTasks as $userPatrolTask)
            <tr>
                <td>
                    <div>
                        {{ $userPatrolTask->id }}
                    </div>
                    <div style="line-height: 200px !important">&nbsp;</div>
                </td>
                <td>
                    <div>
                        {{ $userPatrolTask->user_patrol_batch_id }}
                    </div>
                    <div style="line-height: 200px !important">&nbsp;</div>
                </td>
                <td>
                    <div>
                        {{ $userPatrolTask->patrol_task_id }}
                    </div>
                    <div style="line-height: 200px !important">&nbsp;</div>
                </td>
                <td>
                    <div>
                        {{ $userPatrolTask->description }}
                    </div>
                    <div style="line-height: 200px !important">&nbsp;</div>
                </td>
                <td>
                    <div>
                        {{ $userPatrolTask->lat . ',' . $userPatrolTask->lng }}
                    </div>
                    <div style="line-height: 200px !important">&nbsp;</div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
