@extends('layouts.base')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Timeoff</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('timeoffs.index') }}">Timeoffs</a></li>
                    <li class="breadcrumb-item active">Detail</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-orange card-outline">
                    <div class="card-body box-profile">
                        <!-- <div class="text-center">
                            <img class="profile-user-img img-fluid img-circle" src="../../dist/img/user4-128x128.jpg" alt="User profile picture">
                        </div> -->

                        <h3 class="profile-username text-center">{{ $model->user->name }}</h3>
                        <p class="text-muted text-center">{{ $model->timeoffPolicy->name }}</p>
                        <ul class="list-group list-group-unbordered mb-3">

                            <li class="list-group-item">
                                <b>Request Type</b>
                                <p class="float-right">{{ $model->request_type->getLabel() }}</p>
                            </li>
                            <li class="list-group-item">
                                <b>Start At</b>
                                <p class="float-right">{{ $model->start_at }}</p>
                            </li>
                            <li class="list-group-item">
                                <b>End At</b>
                                <p class="float-right">{{ $model->end_at }}</p>
                            </li>
                            <li class="list-group-item">
                                <b>Reason</b>
                                <p class="float-right">{{ $model->reason }}</p>
                            </li>
                            <li class="list-group-item">
                                <b>Delegate To</b>
                                <p class="float-right">{{ $model->delegate_to }}</p>
                            </li>
                            <li class="list-group-item">
                                <b>Is Advanced Leave</b>
                                <p class="float-right">{{ $model->is_advanced_leave }}</p>
                            </li>
                            <li class="list-group-item">
                                <b>Last Update</b>
                                <p class="float-right">{{ $model->updated_at }}</p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
</section>
@endsection