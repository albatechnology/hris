@extends('layouts.base')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>User Detail</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Detail</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3">
                <div class="card card-orange card-outline">
                    <div class="card-body box-profile">
                        <!-- <div class="text-center">
                            <img class="profile-user-img img-fluid img-circle" src="../../dist/img/user4-128x128.jpg" alt="User profile picture">
                        </div> -->

                        <h3 class="profile-username text-center">{{ $model->name }}</h3>

                        <p class="text-muted text-center">{{ $model->type->getLabel() }}</p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Phone</b>
                                <p class="float-right">{{ $model->phone }}</p>
                            </li>
                            <li class="list-group-item">
                                <b>Email</b>
                                <p class="float-right">{{ $model->email }}</p>
                            </li>
                            <li class="list-group-item">
                                <b>Last Update</b>
                                <p class="float-right">{{ $model->updated_at }}</p>
                            </li>
                            @if($model->type->is(\App\Enums\UserType::CUSTOMER))
                            <li class="list-group-item">
                                <b>Referral Code</b>
                                <p class="float-right">{{ $model->referral_code }}</p>
                            </li>
                            @endif
                        </ul>

                        <a href="{{ route('users.edit', $model->id) }}" class="btn btn-warning btn-block"><i class="fas fa-edit"></i> <b>Edit</b></a>
                    </div>
                </div>

                @if($model->type->is(\App\Enums\UserType::CUSTOMER))
                <div class="card card-orange">
                    <div class="card-header">
                        <h3 class="card-title text-white">Points & Balances</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <strong><i class="fas fa-coins mr-1"></i> Coin</strong>
                                <p class="text-muted">{{ $model->coin }}</p>
                            </div>
                            <div class="col-6">
                                <strong><i class="fas fa-ticket-alt mr-1"></i> Ticket</strong>
                                <p class="text-muted">{{ $model->ticket }}</p>
                            </div>
                        </div>
                        <h4><b>Balances</b></h4>
                        <hr>

                        <strong>E-Deposito</strong>
                        <p class="text-muted">{{ 'Rp' . number_format((float)$model->e_deposito_balance, 0, ',', '.') }}</p>
                        <hr>
                        <strong>Deposito</strong>
                        <p class="text-muted">{{ 'Rp' . number_format((float)$model->deposito_balance, 0, ',', '.') }}</p>
                        <hr>
                        <strong>Investa Plus</strong>
                        <p class="text-muted">{{ 'Rp' . number_format((float)$model->investa_plus_balance, 0, ',', '.') }}</p>
                    </div>
                </div>
                @endif
            </div>

            <div class="col-md-9">
                <div class="card">
                    <div class="card-header p-2">
                        <ul class="nav nav-pills">
                            <li class="nav-item"><a class="nav-link active" href="#tasks" data-toggle="tab">Tasks</a></li>
                            <li class="nav-item"><a class="nav-link" href="#rewards" data-toggle="tab">Rewards</a></li>
                            <li class="nav-item"><a class="nav-link" href="#pointLogs" data-toggle="tab">Point Logs</a></li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane active" id="tasks">
                                <div class="post">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="50">#</th>
                                                <th>CATEGORY</th>
                                                <th>ACTIVITY</th>
                                                <th>DESCRIPTION</th>
                                                <th>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($taskCategories as $i => $taskCategory)
                                            <tr>
                                                <td rowspan="{{ $taskCategory->tasks->count() }}" class="align-middle text-center">{{ ++$i }}</td>
                                                <td rowspan="{{ $taskCategory->tasks->count() }}" class="align-middle">{{ $taskCategory->name }}</td>
                                                <td>{{ $taskCategory->tasks[0]->name }}</td>
                                                <td>{!! $taskCategory->tasks[0]->description !!}</td>
                                                <td class="text-center">
                                                    @if($taskCategory->tasks[0]->type->is(\App\Enums\TaskType::CHECKBOX))
                                                    <div class="icheck-success d-inline">
                                                        <input class="task-checkbox" value="{{ $taskCategory->tasks[0]->id }}" type="checkbox" id="checkbox-{{ $taskCategory->tasks[0]->id }}" {{ in_array($taskCategory->tasks[0]->id, $model->tasks?->pluck('id')->toArray() ?? []) ? 'checked' : null }}>
                                                        <label for="checkbox-{{ $taskCategory->tasks[0]->id }}"></label>
                                                    </div>
                                                    @elseif($taskCategory->tasks[0]->type->is(\App\Enums\TaskType::BUTTON))
                                                    <a href="{{ $taskCategory->tasks[0]->button_action }}" target="_blank" class="btn btn-sm btn-info">{{ $taskCategory->tasks[0]->button_label }}</a>
                                                    @endif
                                                </td>
                                            </tr>
                                            @for($i = 1; $i < $taskCategory->tasks->count(); $i++)
                                                <tr>
                                                    <td>{{ $taskCategory->tasks[$i]->name }}</td>
                                                    <td>{!! $taskCategory->tasks[0]->description !!}</td>
                                                    <td class="text-center">
                                                        @if($taskCategory->tasks[$i]->type->is(\App\Enums\TaskType::CHECKBOX))
                                                        <div class="icheck-success d-inline">
                                                            <input class="task-checkbox" value="{{ $taskCategory->tasks[$i]->id }}" type="checkbox" id="checkbox-{{ $taskCategory->tasks[$i]->id }}" {{ in_array($taskCategory->tasks[$i]->id, $model->tasks?->pluck('id')->toArray() ?? []) ? 'checked' : null }}>
                                                            <label for="checkbox-{{ $taskCategory->tasks[$i]->id }}"></label>
                                                        </div>
                                                        @elseif($taskCategory->tasks[$i]->type->is(\App\Enums\TaskType::BUTTON))
                                                        <a href="{{ $taskCategory->tasks[$i]->button_action }}" target="_blank" class="btn btn-sm btn-info">{{ $taskCategory->tasks[$i]->button_label }}</a>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endfor
                                                @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane" id="rewards">
                                <div class="post">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="50">#</th>
                                                <th>NAME</th>
                                                <th>QUANTITY</th>
                                                <th>TYPE</th>
                                                <th>VALUE</th>
                                                <th>REDEEM AT</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($model->userRewards as $i => $userReward)
                                            <tr>
                                                <td>{{ $i + 1 }}</td>
                                                <td>{{ $userReward->reward->name }}</td>
                                                <td>{{ $userReward->quantity }}</td>
                                                <td>{{ $userReward->reward->point_type }}</td>
                                                <td>{{ $userReward->reward->point_cost }}</td>
                                                <td>{{ $userReward->created_at }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane" id="pointLogs">
                                <div class="post">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="50">#</th>
                                                <th>TIMESTAMP</th>
                                                <th>DESCRIPTION</th>
                                                <th>TYPE</th>
                                                <th>VALUE</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($model->pointLogs()->orderBy('id', 'desc')->get() as $i => $pointLog)
                                            <tr>
                                                <td>{{ $i + 1 }}</td>
                                                <td>{{ $pointLog->timestamp ? date('F j, Y g:i A', strtotime($pointLog->timestamp)) : date('F j, Y g:i A', strtotime($pointLog->created_at)) }}</td>
                                                <td>{{ $pointLog->description }}</td>
                                                <td>{{ $pointLog->point_type->getDescription() }}</td>
                                                @if($pointLog->point_value > 0)
                                                <td class="text-center text-success }}"><b>+{{ $pointLog->point_value }}</b></td>
                                                @else
                                                <td class="text-center text-danher }}"><b>-{{ $pointLog->point_value }}</b></td>
                                                @endif
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</section>
@endsection

@push('scripts')
<script type="application/javascript">
    $('.task-checkbox').on('change', function() {
        const taskIds = $('.task-checkbox:checked').map(function() {
            return this.value;
        }).get();

        $.ajax({
            url: ("{{ route('users.tasks', ':id') }}").replace(':id', '{{ $model->id }}'),
            type: "PUT",
            cache: false,
            data: {
                task_ids: taskIds,
            },
            success: function(data, textStatus, jqXHR) {
                $(document).Toasts('create', {
                    title: 'Success',
                    class: 'bg-success',
                    body: "User tasks is saved",
                    autohide: true,
                    delay: 3000,
                });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $(document).Toasts('create', {
                    title: 'Error',
                    class: 'bg-danger',
                    body: "An error occurred while saving user tasks",
                });
            }
        });
    });
</script>
@endpush
