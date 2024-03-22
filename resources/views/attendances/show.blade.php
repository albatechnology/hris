@extends('layouts.base')

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Profile</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('attendances.index') }}">Attendances</a></li>
                    <li class="breadcrumb-item active">Detail</li>
                </ol>
            </div>
        </div>
    </div><!-- /.container-fluid -->
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3">

                <!-- Profile Image -->
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <h3 class="profile-username text-center">{{ $model->user->name }}</h3>
                        <p class="text-muted text-center">{{ $model->code }}</p>
                        <p class="text-muted text-center">{{ $model->date }}</p>
                        <ul class="list-group list-group-unbordered mb-3">

                            <li class="list-group-item">
                                <b>Schedule</b>
                                <p class="float-right">{{ $model->schedule?->name ?? '-' }}</p>
                            </li>
                            <li class="list-group-item">
                                <b>Shift</b>
                                <p class="float-right">{{ $model->shift?->name ?? '-' }}</p>
                            </li>
                            <li class="list-group-item">
                                <b>Time Off</b>
                                <p class="float-right">{{ $model->timeoff?->name ?? '-' }}</p>
                            </li>
                        </ul>
                    </div>
                    <!-- /.card-body -->
                </div>
            </div>
            <!-- /.col -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header p-2">
                        <ul class="nav nav-pills">
                            <li class="nav-item"><a class="nav-link active" href="#details" data-toggle="tab">Detail
                                </a></li>

                        </ul>
                    </div><!-- /.card-header -->
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="active tab-pane" id="details">
                                <div class="row box-profile">
                                    <div class="col-6">
                                        <h3>Clock In</h3>
                                        <table class="h5 mt-2">
                                            <tr>
                                                <td width="300">Time</td>
                                                <td>: {{ $model->clockIn?->time ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td width="300">Lat</td>
                                                <td>: {{ $model->clockIn?->lat ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td width="300">Lng</td>
                                                <td>: {{ $model->clockIn?->lng ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td width="300">Note</td>
                                                <td>: {{ $model->clockIn?->note ?? '-' }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-6">
                                        <h3>Clock Out</h3>
                                        <table class="h5 mt-2">
                                            <tr>
                                                <td width="300">Time</td>
                                                <td>: {{ $model->clockOut?->time ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td width="300">Lat</td>
                                                <td>: {{ $model->clockOut?->lat ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td width="300">Lng</td>
                                                <td>: {{ $model->clockOut?->lng ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td width="300">Note</td>
                                                <td>: {{ $model->clockOut?->note ?? '-' }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection