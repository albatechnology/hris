@extends('layouts.base')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Subscriptions</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item active">Subscriptions</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @if (auth()->user()->type->hasPermission('subscription_access', 'create'))
                        <div style="margin-bottom: 10px;" class="d-flex justify-content-between">
                            <div>
                                <a class="btn btn-primary" href="{{ route('subscriptions.create') }}">
                                    Add Subscription
                                </a>
                            </div>
                        </div>
                    @endif
                    <div class="card">
                        <div class="card-body">
                            <table
                                class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-subscriptions">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th width="20">ID</th>
                                        <th>GROUP</th>
                                        <th>USER</th>
                                        <th>ACTIVE UNTIL</th>
                                        <th>MAX COMPANIES</th>
                                        <th>MAX USERS</th>
                                        <th>PRICE</th>
                                        <th>DISCOUNT</th>
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
            ajax: "{{ route('subscriptions.index') }}",
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
                    data: 'user.name',
                    name: 'user_id',
                },
                {
                    data: 'active_end_date',
                    name: 'active_end_date',
                },
                {
                    data: 'max_companies',
                    name: 'max_companies',
                },
                {
                    data: 'max_users',
                    name: 'max_users',
                },
                {
                    data: 'price',
                    name: 'price',
                },
                {
                    data: 'discount',
                    name: 'discount',
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

        let table = $('.datatable-subscriptions').DataTable(dtOverrideGlobals);
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
