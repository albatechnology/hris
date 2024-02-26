@extends('layouts.base')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Companies</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item active">Companies</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                @if (auth()->user()->type->hasPermission('company_access', 'create'))
                <div style="margin-bottom: 10px;" class="d-flex justify-content-between">
                    <div>
                        <a class="btn btn-primary" href="{{ route('companies.create') }}">
                            Add Company
                        </a>
                    </div>
                </div>
                @endif
                <div class="card">
                    <div class="card-body">
                        <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-companies">
                            <thead>
                                <tr>
                                    <th width="10">#</th>
                                    <th width="20">ID</th>
                                    <th>GROUP</th>
                                    <th>NAME</th>
                                    <th>ADDRESS</th>
                                    <th>COUNTRY</th>
                                    <th>PROVINCE</th>
                                    <th>CITY</th>
                                    <th>ZIP CODE</th>
                                    <th>LAT</th>
                                    <th>LNG</th>
                                    <th>CURRENCY CODE</th>
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
                                        <input class="search" type="text" strict="true" placeholder="search">
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
                                        <input class="search" type="text" strict="true" placeholder="search">
                                    </td>
                                    <td>
                                        <select class="search">
                                            <option value>-- Please Select --</option>
                                            @foreach (\App\Enums\CurrencyCode::all() as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
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
            url: "{{ route('companies.mass.destroy') }}",
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

                if (confirm('This action cannot be undone. Are you sure you want to delete the selected companies?')) {
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
            ajax: "{{ route('companies.index') }}",
            columns: [{
                    data: 'checkbox',
                    name: 'checkbox',
                },
                {
                    data: 'id',
                    name: 'id',
                },
                {
                    data: 'group.name',
                    name: 'group_id',
                },
                {
                    data: 'name',
                    name: 'name',
                },
                {
                    data: 'address',
                    name: 'address',
                },
                {
                    data: 'country',
                    name: 'country',
                },
                {
                    data: 'province',
                    name: 'province',
                },
                {
                    data: 'city',
                    name: 'city',
                },
                {
                    data: 'zip_code',
                    name: 'zip_code',
                },
                {
                    data: 'lat',
                    name: 'lat',
                },
                {
                    data: 'lng',
                    name: 'lng',
                },
                {
                    data: 'currency_code',
                    name: 'currency_code',
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

        let table = $('.datatable-companies').DataTable(dtOverrideGlobals);
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