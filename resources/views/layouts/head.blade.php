<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
<title>{{ env('APP_NAME') }} | CMS</title>

<!-- Google Font: Source Sans Pro -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<!-- Font Awesome -->
<link rel="stylesheet" href="{{ asset('admin-lte/plugins/fontawesome-free/css/all.min.css') }}">
<!-- Ionicons -->
<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
<!-- Tempusdominus Bootstrap 4 -->
<link rel="stylesheet" href="{{ asset('admin-lte/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
<!-- iCheck -->
<link rel="stylesheet" href="{{ asset('admin-lte/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
<!-- JQVMap -->
<link rel="stylesheet" href="{{ asset('admin-lte/plugins/jqvmap/jqvmap.min.css') }}">
<!-- Theme style -->
<link rel="stylesheet" href="{{ asset('admin-lte/dist/css/adminlte.min.css') }}">
<!-- overlayScrollbars -->
<link rel="stylesheet" href="{{ asset('admin-lte/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
<!-- Daterange picker -->
<link rel="stylesheet" href="{{ asset('admin-lte/plugins/daterangepicker/daterangepicker.css') }}">
<!-- summernote -->
<link rel="stylesheet" href="{{ asset('admin-lte/plugins/summernote/summernote-bs4.min.css') }}">
<!-- DataTables -->
<link rel="stylesheet" href="{{ asset('admin-lte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin-lte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin-lte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
<link href="https://cdn.datatables.net/select/1.3.0/css/select.dataTables.min.css" rel="stylesheet" />
<link href="https://cdn.datatables.net/buttons/1.2.4/css/buttons.dataTables.min.css" rel="stylesheet" />
<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css">

<!-- Custom CSS -->
<link rel="stylesheet" href="{{ asset('css/custom.css') }}">
{{-- @cspMetaTag(App\Support\MyCSP::class) --}}
