@extends('layouts.base')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Dashboard</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <!-- <li class="breadcrumb-item active">Dashboard v2</li> -->
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-sm-12 col-md-4">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-users"></i></span>

                        <div class="info-box-content">
                            <span class="info-box-text">DASHBOARD</span>
                        </div>
                    </div>
                </div>
            </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('admin-lte/plugins/jquery-mousewheel/jquery.mousewheel.js') }}"></script>
    <script src="{{ asset('admin-lte/plugins/raphael/raphael.min.js') }}"></script>
    <script src="{{ asset('admin-lte/plugins/jquery-mapael/jquery.mapael.min.js') }}"></script>
    <script src="{{ asset('admin-lte/plugins/jquery-mapael/maps/usa_states.min.js') }}"></script>
    <script src="{{ asset('admin-lte/plugins/chart.js/Chart.min.js') }}"></script>
    <script src="{{ asset('admin-lte/dist/js/pages/dashboard2.js') }}"></script>
@endpush
