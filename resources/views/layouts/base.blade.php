<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('layouts.head')
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <!-- Preloader -->
    <div class="preloader flex-column justify-content-center align-items-center">
        <img class="animation__shake" src="{{ asset('img/logo-circle.png') }}" alt="img" height="60" width="60">
    </div>
    <!-- /.preloader -->

    @include('layouts._partials._modal')

    <div class="wrapper">
        @include('layouts._partials._topbar')
        @include('layouts._partials._leftbar')

        <div class="content-wrapper">
            @yield('content')
        </div>

        @include('layouts._partials._footer')

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->
    </div>

    @include('layouts.script')
    @stack('scripts')
</body>


</html>