@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush
@extends('layouts.main')
@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <h2 class="card-title">充值管理：</h2>
        </h2>
    </div>

    <div class="card-block row">
        <div class="col-sm-3">
        @include('widgets.forms.input', ['name' => 'from', 'class' => 'search-control', 'title' => 'From', 'value' => $from, 'type' => 'date'])
        </div>
        <div class="col-sm-3">
        @include('widgets.forms.input', ['name' => 'to', 'class' => 'search-control', 'title' => 'To', 'value' => $to, 'type' => 'date'])
        </div>
        <div class="col-sm-3">
        @include('widgets.forms.select', ['name' => 'coin', 'class' => '', 'values' => $coins, 'value' => '', 'title' => '幣別'])
        </div>
        <div class="col-sm-3">
            <button class="btn btn-primary mt-4" id="search-submit" name="submit" value="1">Submit</button>
        </div>
    </div>

    <div class="card-block">
        <div class="table-responsive">
            <table id="deposits" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Coin</th>
                        <th>Amount</th>
                        <th>入帳時間</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th colspan="3">Total </th>
                        <th></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('scripts.data_tables')
<script src="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.js"></script>
<script src="/vendors/bower_components/moment/min/moment.min.js"></script>
<script>
$(function () {
    var timezoneUtcOffset = {{ config('core.timezone_utc_offset.default') }};
    var table = $('#deposits').DataTable({
        order: [[0, 'desc']],
        processing: true,
        serverSide: true,
        ajax: {
            url: '/admin/deposits/search/',
            data: {
                coin: 'All',
            }
        },
        columns: [
            {
                data: 'id',
                render: function (data, type, row) {
                    return $('<a/>')
                        .text(data)
                        .attr('href', '/admin/deposits/' + row.id)
                        .prop('outerHTML');
                },
            },
            {
                data: 'username',
                render: function (data, type, row) {
                    return $('<a/>')
                        .text(data + ' (' + row.user_id + ')')
                        .attr('href', '/admin/users/' + row.user_id)
                        .prop('outerHTML');
                },
            },
            {
                data: 'coin',
            },
            {
                data: 'amount',
            },
            {
                data: 'created_at',
                render: function (data, type, row, meta) {
                    return moment(data).utcOffset(timezoneUtcOffset).format('YYYY-MM-DD HH:mm');
                }
            },
        ],
        footerCallback: function ( row, data, start, end, display ) {
            var api = this.api();

            // Remove the formatting to get integer data for summation
            var intVal = function ( i ) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '')*1 :
                    typeof i === 'number' ?
                        i : 0;
            };

            amountTotal = api
                .column(3, { page: 'current'} )
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );

            // Update footer
            $(api.column(3).footer()).html(
                amountTotal.toFixed(6)
            );

        }
    });

    $('#search-submit').on('click', function (e) {
        var param = {
            from: $('[name="from"]').val(),
            to: $('[name="to"]').val(),
            coin: $('[name="coin"]').val(),
        };

        table.settings()[0].ajax.data = param;
        table
            .ajax
            .url('{{ route('admin.deposits.search') }}')
            .load(null, false);
    });
});
</script>
@endpush
