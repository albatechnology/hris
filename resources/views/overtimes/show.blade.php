@extends('layouts.base')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Overtime</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('overtimes.index') }}">Overtimes</a></li>
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

                        <h3 class="profile-username text-center">{{ $model->company->name }}</h3>
                        <p class="text-muted text-center">{{ $model->name }}</p>
                        <ul class="list-group list-group-unbordered mb-3">

                            <li class="list-group-item">
                                <b>Is rounding</b>
                                <p class="float-right">{{ $model->is_rounding }}</p>
                            </li>
                            <li class="list-group-item">
                                <b>Compensation rate per day</b>
                                <p class="float-right">{{ $model->compensation_rate_per_day }}</p>
                            </li>
                            <li class="list-group-item">
                                <b>Rate type</b>
                                <p class="float-right">{{ $model->rate_type->getLabel() }}</p>
                            </li>
                            <li class="list-group-item">
                                <b>Rate amount</b>
                                <p class="float-right">{{ $model->rate_amount }}</p>
                            </li>
                        </ul>

                    </div>
                </div>
            </div>
        </div>
</section>
@endsection