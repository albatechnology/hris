<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ env('APP_NAME') }} | @yield('title', 'Log in')</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="{{ asset('admin-lte/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin-lte/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin-lte/dist/css/adminlte.min.css') }}">
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            {{ env('APP_NAME') }}
        </div>
        @yield('content')
    </div>
    <script src="{{ asset('admin-lte/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('admin-lte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('admin-lte/dist/js/adminlte.min.js') }}"></script>
    <script type="application/javascript">
        $(document).ready(function(){
            $('.form-loading').on('submit', function() {
                $(this).find(':submit').attr('disabled', true).text('Loading...')
            });
        });
    </script>
</body>
</html>
