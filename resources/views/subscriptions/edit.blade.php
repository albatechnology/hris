@extends('layouts.base')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Subscription</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('subscriptions.index') }}">Subscriptions</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <form action="{{ route('subscriptions.update', $model->id) }}" method="post" enctype="multipart/form-data"
                class="form-loading">
                @csrf
                @method('put')

                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-orange">
                            <div class="card-header">
                                <h3 class="card-title">Edit Subscription</h3>
                            </div>

                            <div class="card-body">
                                <x-forms.input-text name='name' label='Name' placeholder='Name' minlength='3'
                                    required='Required, at least 3 characters' :$model value='{{ $model->user->name }}' readonly />
                                <x-forms.input-email name='email' label='Email' placeholder='Email' required='Required'
                                    :$model value='{{ $model->user->email }}' readonly />
                                <x-forms.input-text name='phone' label='Phone' placeholder='Phone' minlength='10'
                                    required='Required, at least 10 characters' :$model value='{{ $model->user->phone }}' readonly />
                                <x-forms.input-text name='company_name' label='Group Name' placeholder='Group Name'
                                    minlength='3' required='Required, at least 3 characters' :$model value='{{ $model->group->name }}' readonly />
                                <x-forms.input-date name='active_end_date' label='Active Until' placeholder='Active Until'
                                    :$model />
                                <x-forms.input-number name='max_companies' label='Max Companies' placeholder='Max Companies'
                                    min="1" :$model />
                                <x-forms.input-number name='max_users' label='Max Users' placeholder='Max Users'
                                    min="1" :$model />
                                <x-forms.input-number name='price' label='Price' placeholder='Price' min="1"
                                    :$model />
                                <x-forms.input-number name='discount' label='Discount' placeholder='Discount' min="1"
                                    :$model />
                            </div>
                        </div>
                    </div>
                </div>


                <div class="pb-3">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </section>
@endsection
