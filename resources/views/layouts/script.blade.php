<!-- jQuery -->
<script src="{{ asset('admin-lte/plugins/jquery/jquery.min.js') }}"></script>
<!-- jQuery UI 1.11.4 -->
<script src="{{ asset('admin-lte/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script type="application/javascript">
    $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="{{ asset('admin-lte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- jQuery Knob Chart -->
<script src="{{ asset('admin-lte/plugins/jquery-knob/jquery.knob.min.js') }}"></script>
<!-- daterangepicker -->
<script src="{{ asset('admin-lte/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('admin-lte/plugins/daterangepicker/daterangepicker.js') }}"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="{{ asset('admin-lte/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
<!-- Summernote -->
<script src="{{ asset('admin-lte/plugins/summernote/summernote-bs4.min.js') }}"></script>
<!-- overlayScrollbars -->
<script src="{{ asset('admin-lte/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('admin-lte/dist/js/adminlte.js') }}"></script>
<!-- AdminLTE for demo purposes -->
<!-- <script src="{{ asset('admin-lte/dist/js/demo.js') }}"></script> -->
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<!-- Toast -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js"></script>
<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Summernote -->
<script src="{{ asset('admin-lte/plugins/summernote/summernote-bs4.min.js') }}"></script>
<!-- Bootstrap Switch -->
<script src="{{ asset('admin-lte/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
<!-- bs-custom-file-input -->
<script src="{{ asset('admin-lte/plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>

<!-- DataTables  & Plugins -->
<script src="{{ asset('admin-lte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admin-lte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('admin-lte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('admin-lte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script src="{{ asset('admin-lte/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('admin-lte/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
<script src="{{ asset('admin-lte/plugins/jszip/jszip.min.js') }}"></script>
<script src="{{ asset('admin-lte/plugins/pdfmake/pdfmake.min.js') }}"></script>
<script src="{{ asset('admin-lte/plugins/pdfmake/vfs_fonts.js') }}"></script>
<script src="{{ asset('admin-lte/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ asset('admin-lte/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
<script src="{{ asset('admin-lte/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
<script src="https://cdn.datatables.net/select/1.3.0/js/dataTables.select.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.4/js/buttons.flash.min.js"></script>
<script type="application/javascript">
    $(function() {
        $('.form-loading').on('submit', function() {
            $(this).find(':submit').attr('disabled', true).text('Loading...')
        });

        let copyButtonTrans = 'Copy'
        let csvButtonTrans = 'CSV'
        let excelButtonTrans = 'Excel'
        let pdfButtonTrans = 'PDF'
        let printButtonTrans = 'Print'
        let colvisButtonTrans = 'Columns'
        let selectAllButtonTrans = 'Select All'
        let selectNoneButtonTrans = 'Deselect All'

        // let languages = {
        //     'en': 'https://cdn.datatables.net/plug-ins/1.10.19/i18n/English.json',
        //     // 'id': 'https://cdn.datatables.net/plug-ins/1.10.19/i18n/Indonesian.json'
        // };

        $.extend(true, $.fn.dataTable.Buttons.defaults.dom.button, {
            className: 'btn'
        })
        $.extend(true, $.fn.dataTable.defaults, {
            // language: {
            //     url: languages['{{ app()->getLocale() }}']
            // },
            columnDefs: [{
                orderable: false,
                className: 'select-checkbox',
                targets: 0
            }, {
                orderable: false,
                searchable: false,
                targets: -1
            }],
            select: {
                style: 'multi+shift',
                selector: 'td:first-child'
            },
            lengthMenu: [
                [10, 25, 50, 100, 500, 1000, 2500, -1],
                [10, 25, 50, 100, 500, 1000, 2500, "All"]
            ],
            order: [],
            scrollX: true,
            pageLength: 100,
            dom: 'lBfrtip<"actions">',
            buttons: [{
                    extend: 'selectAll',
                    className: 'btn-primary',
                    text: selectAllButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    },
                    action: function(e, dt) {
                        e.preventDefault()
                        dt.rows().deselect();
                        dt.rows({
                            search: 'applied'
                        }).select();
                    }
                },
                {
                    extend: 'selectNone',
                    className: 'btn-primary',
                    text: selectNoneButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'copy',
                    className: 'btn-default',
                    text: copyButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'csv',
                    className: 'btn-default',
                    text: csvButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'excel',
                    className: 'btn-default',
                    text: excelButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'pdf',
                    className: 'btn-default',
                    text: pdfButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'print',
                    className: 'btn-default',
                    text: printButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'colvis',
                    className: 'btn-default',
                    text: colvisButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                }
            ]
        });

        $.fn.dataTable.ext.classes.sPageButton = '';
    });
</script>


<!-- Alert -->
@if (session('success'))
<script type="application/javascript">
    $(document).Toasts('create', {
        title: 'Success',
        class: 'bg-success',
        body: "{{ session('success') }}",
        autohide: true,
        delay: 3000,
    });
</script>
@endif

@if (session('error'))
<script type="application/javascript">
    $(document).Toasts('create', {
        title: 'Error',
        class: 'bg-danger',
        body: "{{ session('error') }}",
    });
</script>
@endif

<script type="application/javascript">
    // Functions
    function moneyFormat(value = 0, format = 'IDR') {
        switch (format) {
            case 'IDR':
                $result = "Rp" + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                break;
            default:
                $result = "Rp" + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                break;
        }

        return $result;

    }

    // Setup AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $(document).ajaxStart(function() {
        $('.ct-loading-overlay').show();
    });
    $(document).ajaxStop(function() {
        $('.ct-loading-overlay').hide();
    });

    // Select2
    $('.select2').each(function() {
        var options = {};
        options.theme = "bootstrap4";

        if ($(this).data('placeholder')) {
            options.placeholder = $(this).data('placeholder');
        }

        if ($(this).data('hide-search')) {
            options.minimumResultsForSearch = -1;
        }

        if ($(this).data('allow-clear')) {
            options.allowClear = true;
        }

        $(this).select2(options);
    });

    // Summernote
    $('.summernote').summernote();

    // Bootstrap Switch
    $(".bootstrap-switch").each(function() {
        $(this).bootstrapSwitch();
    })

    // Close alert
    $('.feather-x').on('click', function() {
        $(this).closest('.feather-x-section').remove();
    });

    // Delete modal
    let deleteModal = $('#ct-delete-modal');
    $('.ct-show-delete-modal').on('click', function() {
        deleteModal.find('form').attr('action', $(this).attr('action'));
        deleteModal.modal('show');
    });
    $('.ct-hide-delete-modal').on('click', function() {
        deleteModal.find('form').attr('action', null);
        deleteModal.modal('hide');
    });

    // Input image
    $(".ct-input-img-btn").on('click', function() {
        $(this).closest('.ct-input-img-section').find(".ct-input-img-file").click();
    });
    $(".ct-input-img-file").on('change', function() {
        $this = $(this);
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $this.closest('.ct-input-img-section').find('img').attr('src', e.target.result);
                $this.closest('.ct-input-img-section').find('.ct-input-img-remove').show();
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
    $(".ct-input-img-remove").on('click', function() {
        $(this).closest('.ct-input-img-section').find('img').attr('src', "{{ asset('dist/images/profile-5.jpg') }}");
        $(this).hide();
    });

    // Input text to number only
    $('.ct-input-text-to-number').bind('keyup paste', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
</script>
