@extends('layouts.base')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Overtimes</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item active">Overtimes</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                @if (auth()->user()->type->hasPermission('overtime_access', 'create'))
                <div style="margin-bottom: 10px;" class="d-flex justify-content-between">
                    <div>
                        <a class="btn btn-primary" href="{{ route('overtimes.create') }}">
                            Add Overtime
                        </a>
                    </div>
                </div>
                @endif
                <div class="card">
                    <div class="card-body">
                        <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-overtimes">
                            <thead>
                                <tr>
                                    <th width="10">#</th>
                                    <th width="20">ID</th>
                                    <th>COMPANY</th>
                                    <th>NAME</th>
                                    <th>IS ROUNDING</th>
                                    <th>COMPENSATION RATE PER DAY</th>
                                    <th>RATE TYPE</th>
                                    <th>RATE AMOUNT</th>
                                    <th>ACTIONS</th>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>
                                        <input class="search" type="text" strict="true" placeholder="search">
                                    </td>
                                    <td>
                                        <select class="search">
                                            <option value>-- Please Select --</option>
                                            @foreach (\App\Models\Company::all() as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input class="search" type="text" strict="true" placeholder="search">
                                    </td>
                                    <td>
                                        <input class="search" type="text" strict="true" placeholder="search">
                                    </td>
                                    <td>
                                        <input class="search" type="text" strict="true" placeholder="search">
                                    </td>
                                    <td>
                                        <select class="search">
                                            <option value>-- Please Select --</option>
                                            @foreach (\App\Enums\RateType::all() as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
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
            url: "{{ route('overtimes.mass.destroy') }}",
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

                if (confirm('This action cannot be undone. Are you sure you want to delete the selected overtimes?')) {
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
            ajax: "{{ route('overtimes.index') }}",
            columns: [{
                    data: 'checkbox',
                    name: 'checkbox',
                },
                {
                    data: 'id',
                    name: 'id',
                },
                {
                    data: 'company.name',
                    name: 'company_id',
                },
                {
                    data: 'name',
                    name: 'name',
                },
                {
                    data: 'is_rounding',
                    name: 'is_rounding',
                },
                {
                    data: 'compensation_rate_per_day',
                    name: 'compensation_rate_per_day',
                },
                {
                    data: 'rate_type',
                    name: 'rate_type',
                },
                {
                    data: 'rate_amount',
                    name: 'rate_amount',
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

        let table = $('.datatable-overtimes').DataTable(dtOverrideGlobals);
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