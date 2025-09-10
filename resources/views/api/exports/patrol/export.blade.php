<div>
    <table>
        <tr>
            <th style="font-weight: bold; background: #b4c7dc;">Nama Patroli</th>
            <th style="background: #b4c7dc;">{{ $patrol->name }}</th>
        </tr>
        <tr>
            <th style="font-weight: bold; background: #b4c7dc;">Map Lokasi</th>
            <th style="background: #b4c7dc;"><a
                    href="https://www.google.com/maps/search/{{ $patrol->lat . ',' . $patrol->lng }}">Lihat Lokasi</a>
            </th>
        </tr>
        <tr>
            <th style="font-weight: bold; background: #b4c7dc;">Tanggal</th>
            <th style="background: #b4c7dc;">{{ $startDate }} - {{ $endDate }}</th>
        </tr>
    </table>
    <table>
        <thead>
            <tr>
                <th style="font-weight: bold; background: #b4c7dc;">Lokasi Patroli</th>
                <th style="font-weight: bold; background: #b4c7dc;">Map Lokasi</th>
                <th style="font-weight: bold; background: #b4c7dc;">Alamat</th>
                <th style="font-weight: bold; background: #b4c7dc;">Task</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($patrol->patrolLocations as $patrolLocation)
                <tr>
                    <td style="background: #b4c7dc;">{{ $patrolLocation->branchLocation->name }}</td>
                    <td style="background: #b4c7dc;"><a
                            href="https://www.google.com/maps/search/{{ $patrolLocation->branchLocation->lat . ',' . $patrolLocation->branchLocation->lng }}">Lihat
                            Lokasi</a>
                    </td>
                    <td style="background: #b4c7dc; height: 100px;">{{ $patrolLocation->branchLocation->address }}</td>
                    <td style="background: #b4c7dc; height: {{ max($patrolLocation->tasks->count() * 23, 100) }}px;">
                        <ol>
                            @foreach ($patrolLocation->tasks as $task)
                                <li>{{ $task->name }}</li>
                            @endforeach
                        </ol>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th style="font-weight: bold; background: #b4c7dc;">User</th>
                <th style="font-weight: bold; background: #b4c7dc;">Patroli Batch</th>
                <th style="font-weight: bold; background: #b4c7dc;">Task</th>
                <th style="font-weight: bold; background: #b4c7dc;">Lokasi</th>
                <th style="font-weight: bold; background: #b4c7dc;">Laporan Pekerjaan</th>
                <th style="font-weight: bold; background: #b4c7dc;">Waktu Pengerjaan</th>
                <th style="font-weight: bold; background: #b4c7dc;">Map Lokasi</th>
                <th style="font-weight: bold; background: #b4c7dc;">Bukti Foto</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($patrol->users as $userPatrol)
                <tr>
                    <td style="font-weight: bold; background: #ffdbb6;">{{ $userPatrol->user->name }}</td>
                    <td style="background: #ffdbb6;"></td>
                    <td style="background: #ffdbb6;"></td>
                    <td style="background: #ffdbb6;"></td>
                    <td style="background: #ffdbb6;"></td>
                    <td style="background: #ffdbb6;"></td>
                    <td style="background: #ffdbb6;"></td>
                    <td style="background: #ffdbb6;"></td>
                </tr>
                @foreach ($userPatrol->user->patrolBatches as $patrolBatch)
                    <tr>
                        <td></td>
                        <td style="font-weight: bold; background: #ffb66c;">{{ $patrolBatch->datetime }}</td>
                        <td style="background: #ffb66c;"></td>
                        <td style="background: #ffb66c;"></td>
                        <td style="background: #ffb66c;"></td>
                        <td style="background: #ffb66c;"></td>
                        <td style="background: #ffb66c;"></td>
                        <td style="background: #ffb66c;"></td>
                    </tr>
                    @foreach ($patrolBatch->userPatrolTasks as $userPatrolTask)
                        <tr>
                            <td></td>
                            <td></td>
                            <td>{{ $userPatrolTask->patrolTask?->name }}</td>
                            <td>{{ $userPatrolTask->patrolTask?->patrolLocation?->branchLocation?->name }}</td>
                            <td style="height: 100px">{{ $userPatrolTask->description }}</td>
                            <td style="width: 130px;">{{ $userPatrolTask->datetime }}</td>
                            <td><a
                                    href="https://www.google.com/maps/search/{{ $userPatrolTask->lat . ',' . $userPatrolTask->lng }}">Lihat
                                    Lokasi</a>
                            </td>
                            @foreach ($userPatrolTask->media as $media)
                                <td>
                                    {{-- <img src="{{ $media->original_url }}" alt="image" style="height: 100px; width: auto;" /> --}}
                                    @if($media?->getUrl('thumb'))
                                    <img src="{{ $media->getUrl('thumb') }}" alt="image"/>
                                    @endif
                                </td>
                            @endforeach
                            <td>
                                <h1 style="margin-bottom: 100px">'</h1>
                                <h1 style="margin-bottom: 100px">&nbsp;</h1>
                                <h1 style="margin-bottom: 100px">&nbsp;</h1>
                                <h1 style="margin-bottom: 100px">&nbsp;</h1>
                                <h1 style="margin-bottom: 100px">&nbsp;</h1>
                                <h1 style="margin-bottom: 100px">&nbsp;</h1>
                                <h1 style="margin-bottom: 100px">&nbsp;</h1>
                                <h1 style="margin-bottom: 100px">&nbsp;</h1>
                                <h1 style="margin-bottom: 100px">&nbsp;</h1>
                                <h1 style="margin-bottom: 100px">&nbsp;</h1>
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <td></td>
                    </tr>
                @endforeach
                <tr>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{-- @foreach ($patrol->users as $userPatrol)
        <table>
            <tbody>
                <tr>
                    <td>{{ $userPatrol->user->name }}</td>
                </tr>
            </tbody>
        </table>
        @foreach ($userPatrol->user->patrolBatches as $patrolBatch)
            <table>
                <tbody>
                    <tr>
                        <td>
                            <table>
                                <tr>
                                    <td>{{ $patrolBatch->datetime }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    @endforeach --}}
</div>
