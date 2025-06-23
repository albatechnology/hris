@extends('layouts.base')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Payments</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item active">Payments</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @if (auth()->user()->type->hasPermission('payment_access', 'create'))
                        <div style="margin-bottom: 10px;" class="d-flex justify-content-between">
                            <div>
                                <a class="btn btn-primary" href="{{ route('payments.create') }}">
                                    Add Payment
                                </a>
                            </div>
                        </div>
                    @endif
                    <div class="card">
                        <div class="card-body">
                            <table
                                class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-payments">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th width="20">ID</th>
                                        <th>GROUP</th>
                                        <th>USER</th>
                                        <th>PAYMENT AT</th>
                                        <th>TOTAL PRICE</th>
                                        <th>ACTIONS</th>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td>
                                            <input class="search" type="text" placeholder="search">
                                        </td>
                                        <td>
                                            <input class="search" type="text" placeholder="search">
                                        </td>
                                        <td>
                                            <input class="search" type="text" placeholder="search">
                                        </td>
                                        <td>
                                            <input class="search" type="text" placeholder="search">
                                        </td>
                                        <td>
                                            <input class="search" type="text" placeholder="search">
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

        let dtOverrideGlobals = {
            buttons: dtButtons,
            processing: true,
            serverSide: true,
            retrieve: true,
            aaSorting: [],
            ajax: "{{ route('payments.index') }}",
            columns: [{
                    data: 'checkbox',
                    name: 'checkbox',
                },
                {
                    data: 'id',
                    name: 'id',
                },
                {
                    data: 'subscription.group.name',
                    name: 'subscription.group.id',
                },
                {
                    data: 'subscription.user.name',
                    name: 'subscription.user.id',
                },
                {
                    data: 'payment_at',
                    name: 'payment_at',
                },
                {
                    data: 'total_price',
                    name: 'total_price',
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

        let table = $('.datatable-payments').DataTable(dtOverrideGlobals);
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
