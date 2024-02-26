@extends('layouts.base')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Edit Branch</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('branches.index') }}">Branches</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <form action="{{ route('branches.update', $model->id) }}" method="post" enctype="multipart/form-data" class="form-loading">
            @csrf
            @method('put')

            @include('branches.form')

            <div class="pb-3">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</section>
@endsection