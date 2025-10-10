@extends('layouts.base')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Subscription Detail</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('subscriptions.index') }}">Subscriptions</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-orange">
                        <div class="card-header">
                            <h3 class="card-title">Data Subscription</h3>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <tr>
                                        <th>Subscriber Name</th>
                                        <td>{{ $model->user->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Subscriber Email</th>
                                        <td>{{ $model->user->email }}</td>
                                    </tr>
                                    <tr>
                                        <th>Subscriber Phone</th>
                                        <td>{{ $model->user->phone }}</td>
                                    </tr>
                                    <tr>
                                        <th>Subscriber Group Name</th>
                                        <td>{{ $model->group->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Active Until</th>
                                        <td>{{ $model->active_end_date }}</td>
                                    </tr>
                                    <tr>
                                        <th>Max Companies</th>
                                        <td>{{ $model->max_companies }}</td>
                                    </tr>
                                    <tr>
                                        <th>Max Users</th>
                                        <td>{{ $model->max_users }}</td>
                                    </tr>
                                    <tr>
                                        <th>Price</th>
                                        <td>{{ $model->price }}</td>
                                    </tr>
                                    <tr>
                                        <th>Discount</th>
                                        <td>{{ $model->discount }}</td>
                                    </tr>
                                    <tr>
                                        <th>Total Price</th>
                                        <td>{{ $model->total_price }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card card-orange">
                        <div class="card-header">
                            <h3 class="card-title">Payment Histories</h3>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>PAYMENT AT</th>
                                            <th>TOTAL PRICE</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($model->payments as $payment)
                                        <tr>
                                            <td>{{ $payment->id }}</td>
                                            <td>{{ $payment->payment_at }}</td>
                                            <td>{{ $payment->total_price }}</td>
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
    </section>
@endsection
