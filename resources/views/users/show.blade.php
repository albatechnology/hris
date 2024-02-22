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
            <div class="col-12">
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
                        </ul>

                        <a href="{{ route('users.edit', $model->id) }}" class="btn btn-warning btn-block"><i class="fas fa-edit"></i> <b>Edit</b></a>
                    </div>
                </div>
            </div>
        </div>
</section>
@endsection
