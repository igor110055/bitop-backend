@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush
@extends('layouts.main')
@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <h2 class="card-title">提現管理：</h2>
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
            <table id="withdrawals" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>發起時間</th>
                        <th>完成時間</th>
                        <th>Coin</th>
                        <th>Amount</th>
                        <th>Fee</th>
                        <th>狀態</th>
                    </tr>
                </thead>
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
    var table = $('#withdrawals').DataTable({
        order: [[0, 'desc']],
        processing: true,
        serverSide: true,
        ajax: {
            url: '/admin/withdrawals/search/',
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
                        .attr('href', '/admin/withdrawals/' + row.id)
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
                data: 'created_at',
                render: function (data, type, row, meta) {
                    return moment(data).utcOffset(timezoneUtcOffset).format('YYYY-MM-DD HH:mm');
                }
            },
            {
                data: 'notified_at',
                render: function (data, type, row, meta) {
                    if (data) {
                        return moment(data).utcOffset(timezoneUtcOffset).format('YYYY-MM-DD HH:mm');
                    } else {
                        return '';
                    }
                }
            },
            {
                data: 'coin',
                render: function (data, type, row) {
                    return $('<a/>')
                        .text(data)
                        .attr('href', '/admin/accounts/' + row.account_id)
                        .prop('outerHTML');
                },
            },
            {
                data: 'amount',
            },
            {
                data: 'fee',
            },
            {
                data: 'status',
                orderable: false,
            },
        ],
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
            .url('{{ route('admin.withdrawals.search') }}')
            .load(null, false);
    });
});
</script>
@endpush
