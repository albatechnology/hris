@extends('layouts.base')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Create Attendance</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('attendances.index') }}">Attendances</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <form action="{{ route('attendances.store') }}" method="post" enctype="multipart/form-data" class="form-loading">
            @csrf

            @include('attendances.form')

            <div class="pb-3">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</section>
@endsection