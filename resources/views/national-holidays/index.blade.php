@extends('layouts.base')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>National Holidays</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item active">National Holidays</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @if (auth()->user()->type->hasPermission('national_holiday_access', 'create'))
                        <div style="margin-bottom: 10px;" class="d-flex justify-content-between">
                            <div>
                                <a class="btn btn-primary" href="{{ route('national-holidays.create') }}">
                                    Add National Holiday
                                </a>
                            </div>
                            <div>
                                <button type="button" class="btn btn-primary" data-toggle="modal"
                                    data-target="#modal-default"><i class="fa fa-file-import"></i> Import</button>
                                <div class="modal fade" id="modal-default">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title">Import Data</h4>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <form action="{{ route('national-holidays.import') }}" method="post" enctype="multipart/form-data" class="form-loading">
                                                @csrf
                                                <div class="modal-body">
                                                    <a href="{{ route('exports.sample', 'national_holiday') }}"
                                                        class="btn btn-info mb-2"><i class="fa fa-download"></i> Download
                                                        Sample</a>
                                                        <div class="text-right">allowed: csv,xls,xlxs</div>
                                                    <div class="custom-file">
                                                        <input name="file" type="file" class="custom-file-input @error('file') is-invalid @enderror" id="file" required>
                                                        <label class="custom-file-label" for="file">Choose
                                                            file</label>
                                                        @error('file')
                                                            <span class="error invalid-feedback">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <div class="modal-footer justify-content-between">
                                                    <button type="button" class="btn btn-default"
                                                        data-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary"><i
                                                            class="fa fa-file-import"></i> Import</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="card">
                        <div class="card-body">
                            <table
                                class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-national-holidays">
                                <thead>
                                    <tr>
                                        <th width="10">#</th>
                                        <th width="20">ID</th>
                                        <th>NAME</th>
                                        <th>DATE</th>
                                        <th>ACTIONS</th>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td>
                                            <input class="search" type="text" strict="true" placeholder="search">
                                        </td>
                                        <td>
                                            <input class="search" type="text" strict="true" placeholder="search">
                                        </td>
                                        <td>
                                            <input class="search" type="text" strict="true" placeholder="search">
                                        </td>
                                        <td></td>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script type="application/javascript">
    $(function() {
        let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)

        let deleteButtonTrans = 'Delete Selected';
        let deleteButton = {
            text: deleteButtonTrans,
            url: "{{ route('national-holidays.mass.destroy') }}",
            className: 'btn-danger',
            action: function(e, dt, node, config) {
                var ids = $.map(dt.rows({
                    selected: true,
                }).data(), function(entry) {
                    return entry.id;
                });

                if (ids.length === 0) {
                    alert('No rows selected.');

                    return;
                }

                if (confirm('This action cannot be undone. Are you sure you want to delete the selected national-holidays?')) {
                    $.ajax({
                            method: 'POST',
                            url: config.url,
                            data: {
                                ids: ids,
                                _method: 'DELETE'
                            },
                        })
                        .done(function(response) {
                            $(document).Toasts('create', {
                                title: 'Success',
                                class: 'bg-success',
                                body: response.data,
                                autohide: true,
                                delay: 3000,
                            }).on('hidden.bs.toast', function() {
                                location.reload();
                            });
                        });
                }
            }
        }
        dtButtons.push(deleteButton);

        let dtOverrideGlobals = {
            buttons: dtButtons,
            processing: true,
            serverSide: true,
            retrieve: true,
            aaSorting: [],
            ajax: "{{ route('national-holidays.index') }}",
            columns: [{
                    data: 'checkbox',
                    name: 'checkbox',
                },
                {
                    data: 'id',
                    name: 'id',
                },
                {
                    data: 'name',
                    name: 'name',
                },
                {
                    data: 'date',
                    name: 'date',
                },
                {
                    data: 'actions',
                    name: 'actions',
                },

            ],
            orderCellsTop: true,
            order: [
                [1, 'desc']
            ],
            pageLength: 25,
        };

        let table = $('.datatable-national-holidays').DataTable(dtOverrideGlobals);
        $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e) {
            $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
        });

        let visibleColumnsIndexes = null;
        $('.datatable thead').on('input', '.search', function() {
            let strict = $(this).attr('strict') || false;
            let value = strict && this.value ? "^" + this.value + "$" : this.value;

            let index = $(this).parent().index()
            if (visibleColumnsIndexes !== null) {
                index = visibleColumnsIndexes[index];
            }

            table.column(index).search(value, strict).draw();
        });

        table.on('column-visibility.dt', function(e, settings, column, state) {
            visibleColumnsIndexes = []
            table.columns(":visible").every(function(colIdx) {
                visibleColumnsIndexes.push(colIdx);
            });
        });
    });
</script>

    <script type="application/javascript">
    $(function() {
        bsCustomFileInput.init();
    });
</script>
@endpush
